<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Octane\Facades\Octane;

class HealthCheckController extends Controller
{
    /**
     * Basic health check endpoint.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'enkiflow',
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'octane' => [
                'server' => config('octane.server'),
                'workers' => $this->getOctaneWorkerCount(),
            ],
        ]);
    }

    /**
     * Database health check.
     */
    public function database(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        try {
            // Check central database
            $centralStart = microtime(true);
            DB::connection('central')->select('SELECT 1');
            $checks['central'] = [
                'status' => 'healthy',
                'response_time_ms' => round((microtime(true) - $centralStart) * 1000, 2),
            ];

            // Check if we're in a tenant context
            if (tenancy()->initialized) {
                $tenantStart = microtime(true);
                DB::connection('tenant')->select('SELECT 1');
                $checks['tenant'] = [
                    'status' => 'healthy',
                    'tenant_id' => tenant()->id,
                    'response_time_ms' => round((microtime(true) - $tenantStart) * 1000, 2),
                ];
            }

            // Check connection pool
            $centralConnections = DB::connection('central')
                ->select("SELECT count(*) as count FROM pg_stat_activity WHERE datname = ?", [
                    config('database.connections.central.database')
                ]);
            
            $checks['connection_pool'] = [
                'active_connections' => $centralConnections[0]->count ?? 0,
                'max_connections' => config('database.connections.central.pool_size', 100),
            ];

        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['error'] = $e->getMessage();
            Log::error('Health check database failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $status === 'healthy' ? 200 : 503);
    }

    /**
     * Queue health check.
     */
    public function queue(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        try {
            // Check if we can dispatch to queue
            $testKey = 'health_check_queue_' . uniqid();
            Cache::put($testKey, true, 60);
            
            // Check queue sizes
            $defaultQueueSize = DB::table('jobs')
                ->where('queue', 'default')
                ->count();
                
            $highPriorityQueueSize = DB::table('jobs')
                ->where('queue', 'high')
                ->count();

            $checks['queues'] = [
                'default' => [
                    'size' => $defaultQueueSize,
                    'status' => $defaultQueueSize < 1000 ? 'healthy' : 'warning',
                ],
                'high_priority' => [
                    'size' => $highPriorityQueueSize,
                    'status' => $highPriorityQueueSize < 100 ? 'healthy' : 'warning',
                ],
            ];

            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();
            $checks['failed_jobs'] = [
                'count' => $failedJobs,
                'status' => $failedJobs < 100 ? 'healthy' : 'warning',
            ];

            // Clean up test key
            Cache::forget($testKey);

            // Determine overall status
            if ($defaultQueueSize > 5000 || $highPriorityQueueSize > 500 || $failedJobs > 500) {
                $status = 'unhealthy';
            } elseif ($defaultQueueSize > 1000 || $highPriorityQueueSize > 100 || $failedJobs > 100) {
                $status = 'degraded';
            }

        } catch (\Exception $e) {
            $status = 'unhealthy';
            $checks['error'] = $e->getMessage();
            Log::error('Health check queue failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $status === 'healthy' ? 200 : ($status === 'degraded' ? 200 : 503));
    }

    /**
     * Comprehensive health check.
     */
    public function full(): JsonResponse
    {
        $overallStatus = 'healthy';
        $checks = [];

        // Basic health
        $healthResponse = $this->health();
        $checks['application'] = json_decode($healthResponse->content(), true);

        // Database health
        $dbResponse = $this->database();
        $dbData = json_decode($dbResponse->content(), true);
        $checks['database'] = $dbData;
        if ($dbData['status'] !== 'healthy') {
            $overallStatus = 'unhealthy';
        }

        // Queue health
        $queueResponse = $this->queue();
        $queueData = json_decode($queueResponse->content(), true);
        $checks['queue'] = $queueData;
        if ($queueData['status'] === 'unhealthy') {
            $overallStatus = 'unhealthy';
        } elseif ($queueData['status'] === 'degraded' && $overallStatus === 'healthy') {
            $overallStatus = 'degraded';
        }

        // Redis health
        try {
            $redisStart = microtime(true);
            Cache::store('redis')->put('health_check', true, 10);
            Cache::store('redis')->get('health_check');
            $checks['redis'] = [
                'status' => 'healthy',
                'response_time_ms' => round((microtime(true) - $redisStart) * 1000, 2),
            ];
        } catch (\Exception $e) {
            $checks['redis'] = [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
            $overallStatus = 'unhealthy';
        }

        // Disk space
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
        
        $checks['disk'] = [
            'free_gb' => round($diskFree / 1073741824, 2),
            'total_gb' => round($diskTotal / 1073741824, 2),
            'usage_percent' => $diskUsagePercent,
            'status' => $diskUsagePercent < 85 ? 'healthy' : ($diskUsagePercent < 95 ? 'warning' : 'critical'),
        ];

        if ($diskUsagePercent > 95) {
            $overallStatus = 'unhealthy';
        }

        return response()->json([
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $overallStatus === 'healthy' ? 200 : ($overallStatus === 'degraded' ? 200 : 503));
    }

    /**
     * Get Octane worker count.
     */
    private function getOctaneWorkerCount(): int
    {
        // This is a placeholder - in production, you'd get this from Octane's actual state
        return (int) env('OCTANE_WORKERS', 'auto');
    }
}