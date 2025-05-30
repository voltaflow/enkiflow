<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PreDeployCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:check {--fix : Attempt to fix issues automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run pre-deployment checks for Laravel Cloud';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Running pre-deployment checks for Laravel Cloud...');
        $this->newLine();

        $allPassed = true;

        // Check 1: Environment Configuration
        $this->info('1. Checking environment configuration...');
        if ($this->checkEnvironment()) {
            $this->line('   ✅ Environment configuration is valid');
        } else {
            $this->error('   ❌ Environment configuration has issues');
            $allPassed = false;
        }

        // Check 2: Database Connectivity
        $this->info('2. Checking database connectivity...');
        if ($this->checkDatabase()) {
            $this->line('   ✅ Database connection successful');
        } else {
            $this->error('   ❌ Database connection failed');
            $allPassed = false;
        }

        // Check 3: Redis Connectivity
        $this->info('3. Checking Redis connectivity...');
        if ($this->checkRedis()) {
            $this->line('   ✅ Redis connection successful');
        } else {
            $this->error('   ❌ Redis connection failed');
            $allPassed = false;
        }

        // Check 4: Multi-tenancy Configuration
        $this->info('4. Checking multi-tenancy configuration...');
        if ($this->checkTenancy()) {
            $this->line('   ✅ Multi-tenancy properly configured');
        } else {
            $this->error('   ❌ Multi-tenancy configuration issues');
            $allPassed = false;
        }

        // Check 5: File Permissions
        $this->info('5. Checking file permissions...');
        if ($this->checkPermissions()) {
            $this->line('   ✅ File permissions are correct');
        } else {
            $this->error('   ❌ File permission issues');
            if ($this->option('fix')) {
                $this->fixPermissions();
            }
            $allPassed = false;
        }

        // Check 6: Compiled Assets
        $this->info('6. Checking compiled assets...');
        if ($this->checkAssets()) {
            $this->line('   ✅ Assets are compiled');
        } else {
            $this->error('   ❌ Assets need to be compiled');
            $allPassed = false;
        }

        // Check 7: Configuration Cache
        $this->info('7. Checking configuration cache...');
        if ($this->checkConfigCache()) {
            $this->line('   ✅ Configuration is cached');
        } else {
            $this->warn('   ⚠️  Configuration is not cached (will be done during deployment)');
        }

        // Check 8: Health Check Endpoints
        $this->info('8. Testing health check endpoints...');
        if ($this->checkHealthEndpoints()) {
            $this->line('   ✅ Health check endpoints are working');
        } else {
            $this->error('   ❌ Health check endpoints have issues');
            $allPassed = false;
        }

        // Check 9: Octane Configuration
        $this->info('9. Checking Octane configuration...');
        if ($this->checkOctane()) {
            $this->line('   ✅ Octane is properly configured');
        } else {
            $this->error('   ❌ Octane configuration issues');
            $allPassed = false;
        }

        // Check 10: Queue Configuration
        $this->info('10. Checking queue configuration...');
        if ($this->checkQueues()) {
            $this->line('   ✅ Queue system is properly configured');
        } else {
            $this->error('   ❌ Queue configuration issues');
            $allPassed = false;
        }

        $this->newLine();

        if ($allPassed) {
            $this->info('✅ All pre-deployment checks passed! Ready for Laravel Cloud deployment.');

            return Command::SUCCESS;
        } else {
            $this->error('❌ Some checks failed. Please fix the issues before deploying.');

            return Command::FAILURE;
        }
    }

    private function checkEnvironment(): bool
    {
        $required = [
            'APP_NAME',
            'APP_ENV',
            'APP_KEY',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME',
            'CACHE_STORE',
            'SESSION_DRIVER',
            'QUEUE_CONNECTION',
        ];

        foreach ($required as $key) {
            if (empty(env($key))) {
                $this->warn("   Missing required environment variable: {$key}");

                return false;
            }
        }

        // Check for production-specific settings
        if (app()->environment('production')) {
            if (config('app.debug') === true) {
                $this->warn('   APP_DEBUG should be false in production');

                return false;
            }
        }

        return true;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection('central')->getPdo();

            // Check if we can run a simple query
            DB::connection('central')->select('SELECT 1');

            return true;
        } catch (\Exception $e) {
            $this->warn('   Database error: '.$e->getMessage());

            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Cache::store('redis')->put('deploy_check', true, 10);
            $value = Cache::store('redis')->get('deploy_check');
            Cache::store('redis')->forget('deploy_check');

            return $value === true;
        } catch (\Exception $e) {
            $this->warn('   Redis error: '.$e->getMessage());

            return false;
        }
    }

    private function checkTenancy(): bool
    {
        try {
            // Check if tenancy tables exist
            $tables = ['tenants', 'domains'];
            foreach ($tables as $table) {
                if (! DB::connection('central')->getSchemaBuilder()->hasTable($table)) {
                    $this->warn("   Missing tenancy table: {$table}");

                    return false;
                }
            }

            // Check if tenancy is properly configured
            if (empty(config('tenancy.central_domains'))) {
                $this->warn('   Central domains not configured');

                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->warn('   Tenancy check error: '.$e->getMessage());

            return false;
        }
    }

    private function checkPermissions(): bool
    {
        $directories = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $allGood = true;

        foreach ($directories as $directory) {
            if (! is_writable($directory)) {
                $this->warn("   Directory not writable: {$directory}");
                $allGood = false;
            }
        }

        return $allGood;
    }

    private function fixPermissions(): void
    {
        $this->info('   Attempting to fix permissions...');

        $directories = [
            storage_path(),
            base_path('bootstrap/cache'),
        ];

        foreach ($directories as $directory) {
            $this->line("   Setting permissions for: {$directory}");
            exec("chmod -R 775 {$directory}");
        }
    }

    private function checkAssets(): bool
    {
        $manifestPath = public_path('build/manifest.json');

        if (! file_exists($manifestPath)) {
            $this->warn('   Asset manifest not found. Run: npm run build');

            return false;
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);

        if (empty($manifest)) {
            $this->warn('   Asset manifest is empty');

            return false;
        }

        return true;
    }

    private function checkConfigCache(): bool
    {
        return file_exists(base_path('bootstrap/cache/config.php'));
    }

    private function checkHealthEndpoints(): bool
    {
        try {
            // Try direct controller call first (more reliable for local testing)
            $controller = app(\App\Http\Controllers\HealthCheckController::class);
            $response = $controller->health();

            $data = json_decode($response->content(), true);

            return $data['status'] === 'healthy';
        } catch (\Exception $e) {
            $this->warn('   Health check error: '.$e->getMessage());

            // Try HTTP as fallback
            try {
                $url = config('app.url').'/health';
                // Handle both HTTP and HTTPS
                $response = Http::withOptions([
                    'verify' => false, // For local development with self-signed certs
                ])->get($url);

                if ($response->successful() && $response->json('status') === 'healthy') {
                    return true;
                }
            } catch (\Exception $httpError) {
                $this->warn('   HTTP health check error: '.$httpError->getMessage());
            }
        }

        return false;
    }

    private function checkOctane(): bool
    {
        // Check if Octane is installed
        if (! class_exists(\Laravel\Octane\Octane::class)) {
            $this->warn('   Laravel Octane is not installed');

            return false;
        }

        // Check Octane configuration
        if (config('octane.server') !== 'swoole') {
            $this->warn('   Octane should use Swoole server for Laravel Cloud');

            return false;
        }

        // Check if tenancy is in flush array
        $flushArray = config('octane.flush', []);
        if (! in_array('tenancy', $flushArray)) {
            $this->warn('   Octane should flush tenancy between requests');

            return false;
        }

        return true;
    }

    private function checkQueues(): bool
    {
        // Check if Horizon is installed
        if (! class_exists(\Laravel\Horizon\Horizon::class)) {
            $this->warn('   Laravel Horizon is not installed');

            return false;
        }

        // Check queue connection
        if (! in_array(config('queue.default'), ['redis', 'database'])) {
            $this->warn('   Queue connection should be redis or database for production');

            return false;
        }

        return true;
    }
}
