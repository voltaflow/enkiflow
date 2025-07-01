<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreProjectRequest;
use App\Http\Requests\Tenant\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use App\Models\Tag;
use App\Services\ProjectService;
use App\Traits\HasSpacePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    use HasSpacePermissions;

    /**
     * The project service instance.
     */
    protected ProjectService $projectService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
        $this->authorizeResource(Project::class, 'project');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'term' => $request->get('search'),
            'status' => $request->get('status'),
            'client_id' => $request->get('client_id'),
            'include_archived' => $request->boolean('archived'),
        ];

        $projects = $this->projectService->search($filters, 15);
        
        // Get active clients for filter dropdown
        $clients = Client::active()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Tenant/Projects/Index', [
            'projects' => $projects,
            'filters' => $filters,
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $clients = Client::active()->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'color']);

        return Inertia::render('Tenant/Projects/Create', [
            'clients' => $clients,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Add the current user as the project owner if not specified
        if (! isset($validated['user_id'])) {
            $validated['user_id'] = Auth::id();
        }

        $project = $this->projectService->createProject($validated);

        if ($request->has('tags')) {
            $this->projectService->syncTags($project->id, $request->tags);
        }

        return redirect()->route('tenant.projects.show', $project->id)
            ->with('success', 'Proyecto creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        $project->load([
            'user:id,name,email', 
            'client:id,name', 
            'tags', 
            'tasks' => function($query) {
                $query->with('user:id,name')
                      ->orderBy('created_at', 'desc');
            }
        ]);

        $currentUser = Auth::user();
        
        // Calcular estadísticas
        $stats = [
            'total_tasks' => $project->tasks->count(),
            'completed_tasks' => $project->tasks->where('status', 'completed')->count(),
            'total_hours' => $project->timeEntries->sum('duration'),
            'billable_hours' => $project->timeEntries->where('is_billable', true)->sum('duration'),
        ];

        return Inertia::render('Tenant/Projects/Show', [
            'project' => $project,
            'is_owner' => $currentUser->id === $project->user_id,
            'can_edit' => $currentUser->can('update', $project),
            'can_delete' => $currentUser->can('delete', $project),
            'can_complete' => $currentUser->can('complete', $project),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        $project->load('tags');
        $clients = Client::active()->orderBy('name')->get(['id', 'name']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'color']);

        return Inertia::render('Tenant/Projects/Edit', [
            'project' => $project,
            'clients' => $clients,
            'tags' => $tags,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $this->projectService->updateProject($project->id, $validated);

        if ($request->has('tags')) {
            $this->projectService->syncTags($project->id, $request->tags);
        }

        return redirect()->route('tenant.projects.show', $project->id)
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->projectService->deleteProject($project->id);

        return redirect()->route('tenant.projects.index')
            ->with('success', 'Proyecto eliminado correctamente.');
    }

    /**
     * Mark the project as completed.
     */
    public function complete(Project $project): RedirectResponse
    {
        $this->authorize('complete', $project);

        $this->projectService->markProjectAsCompleted($project->id);

        return back()->with('success', 'Proyecto marcado como completado.');
    }

    /**
     * Mark the project as active.
     */
    public function reactivate(Project $project): RedirectResponse
    {
        $this->authorize('complete', $project);

        $this->projectService->markProjectAsActive($project->id);

        return back()->with('success', 'Proyecto reactivado correctamente.');
    }
}
