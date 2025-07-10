<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    /**
     * Display the reports dashboard.
     */
    public function index(Request $request): Response
    {
        // Ensure we're in a tenant context
        $currentSpace = tenant();
        if (!$currentSpace) {
            abort(404, 'Space not found');
        }
        
        // Get initial data for filters
        $projects = Project::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
            
        // Get users who have access to the current tenant/space
        $users = $currentSpace->users()
            ->select('users.id', 'users.name', 'users.email')
            ->orderBy('users.name')
            ->get();
            
        return Inertia::render('Reports/index', [
            'projects' => $projects,
            'users' => $users,
            'initialFilters' => [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfDay()->toDateString(),
            ],
        ]);
    }
}