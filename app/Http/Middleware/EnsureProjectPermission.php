<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\ProjectPermission;
use App\Models\Project;
use App\Services\ProjectPermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectPermission
{
    public function __construct(
        protected ProjectPermissionResolver $permissionResolver
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Only check if feature is enabled
        if (!config('features.project_permissions', false)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Find project in route parameters
        $project = $this->findProject($request);
        if (!$project) {
            abort(404, 'Project not found');
        }

        // Check if user has any of the required permissions
        $requiredPermissions = [];
        foreach ($permissions as $permission) {
            try {
                $requiredPermissions[] = ProjectPermission::from($permission);
            } catch (\ValueError $e) {
                abort(500, "Invalid permission: {$permission}");
            }
        }

        if (empty($requiredPermissions)) {
            abort(500, 'No permissions specified');
        }

        $hasPermission = $this->permissionResolver->userHasAnyPermission(
            $user,
            $project,
            $requiredPermissions
        );

        if (!$hasPermission) {
            abort(403, 'You do not have permission to perform this action');
        }

        return $next($request);
    }

    /**
     * Find project from request.
     */
    protected function findProject(Request $request): ?Project
    {
        // Check route parameters
        $project = $request->route('project');
        if ($project instanceof Project) {
            return $project;
        }

        // Check for project ID in route
        $projectId = $request->route('project_id') ?? $request->route('projectId');
        if ($projectId) {
            return Project::find($projectId);
        }

        // Check request data
        $projectId = $request->input('project_id') ?? $request->input('projectId');
        if ($projectId) {
            return Project::find($projectId);
        }

        // Check JSON payload
        if ($request->isJson()) {
            $projectId = $request->json('project_id') ?? $request->json('projectId');
            if ($projectId) {
                return Project::find($projectId);
            }
        }

        return null;
    }
}