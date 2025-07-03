<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Space;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationStatsController extends Controller
{
    use HasSpacePermissions;

    /**
     * Get the current space from domain.
     */
    protected function getCurrentSpaceFromDomain(): Space
    {
        $domain = \Stancl\Tenancy\Database\Models\Domain::where('domain', request()->getHost())->first();
        if (!$domain) {
            abort(404);
        }
        $space = Space::find($domain->tenant_id);
        if (!$space) {
            abort(404);
        }
        return $space;
    }

    /**
     * Get invitation statistics for the current space.
     */
    public function index(Request $request)
    {
        $space = $this->getCurrentSpaceFromDomain();
        
        // Check permissions
        if (!$this->userHasPermission(Auth::user(), \App\Enums\SpacePermission::VIEW_STATISTICS)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);

        // Get basic stats
        $stats = [
            'total_sent' => Invitation::where('tenant_id', $space->id)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'pending' => Invitation::where('tenant_id', $space->id)
                ->where('status', 'pending')
                ->count(),
            
            'accepted' => Invitation::where('tenant_id', $space->id)
                ->where('status', 'accepted')
                ->where('accepted_at', '>=', $startDate)
                ->count(),
            
            'expired' => Invitation::where('tenant_id', $space->id)
                ->where('status', 'expired')
                ->count(),
            
            'revoked' => Invitation::where('tenant_id', $space->id)
                ->where('status', 'revoked')
                ->count(),
        ];

        // Calculate acceptance rate
        $stats['acceptance_rate'] = $stats['total_sent'] > 0 
            ? round(($stats['accepted'] / $stats['total_sent']) * 100, 2) 
            : 0;

        // Get daily breakdown
        $dailyStats = Invitation::where('tenant_id', $space->id)
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as sent'),
                DB::raw('SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get top inviters
        $topInviters = Invitation::where('tenant_id', $space->id)
            ->where('created_at', '>=', $startDate)
            ->select('invited_by', DB::raw('COUNT(*) as count'))
            ->with('inviter:id,name,email')
            ->groupBy('invited_by')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'user' => $item->inviter ? [
                        'id' => $item->inviter->id,
                        'name' => $item->inviter->name,
                        'email' => $item->inviter->email,
                    ] : null,
                    'count' => $item->count,
                ];
            });

        // Get average time to accept
        $avgTimeToAccept = Invitation::where('tenant_id', $space->id)
            ->where('status', 'accepted')
            ->whereNotNull('accepted_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, accepted_at)) as avg_hours')
            ->value('avg_hours');

        return response()->json([
            'period_days' => $period,
            'stats' => $stats,
            'daily_breakdown' => $dailyStats,
            'top_inviters' => $topInviters,
            'avg_time_to_accept_hours' => round($avgTimeToAccept ?? 0, 2),
        ]);
    }

    /**
     * Get activity logs for invitations.
     */
    public function logs(Request $request)
    {
        $space = $this->getCurrentSpaceFromDomain();
        
        // Check permissions
        if (!$this->userHasPermission(Auth::user(), \App\Enums\SpacePermission::VIEW_STATISTICS)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logs = \App\Models\InvitationLog::whereHas('invitation', function ($query) use ($space) {
                $query->where('tenant_id', $space->id);
            })
            ->with(['invitation:id,email,role', 'actor:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json($logs);
    }
}