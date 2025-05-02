<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreProjectRequest;
use App\Http\Requests\Tenant\UpdateProjectRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * The project service instance.
     *
     * @var ProjectService
     */
    protected ProjectService $projectService;
    
    /**
     * Create a new controller instance.
     *
     * @param ProjectService $projectService
     */
    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $projects = $this->projectService->getAllProjects();

        return Inertia::render('Tenant/Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        $project = $this->projectService->createProject($validated);
        
        if ($request->has('tags')) {
            $this->projectService->syncTags($project->id, $request->tags);
        }

        return redirect()->route('tenant.projects.show', $project->id)
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        $project->load('user:id,name,email', 'tags', 'tasks');

        return Inertia::render('Tenant/Projects/Show', [
            'project' => $project,
            'is_owner' => Auth::id() === $project->user_id,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        // Only the project owner can edit it
        if (Auth::id() !== $project->user_id) {
            abort(403, 'You do not have permission to edit this project.');
        }

        $project->load('tags');

        return Inertia::render('Tenant/Projects/Edit', [
            'project' => $project,
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
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        // Only the project owner can delete it
        if (Auth::id() !== $project->user_id) {
            abort(403, 'You do not have permission to delete this project.');
        }

        $this->projectService->deleteProject($project->id);

        return redirect()->route('tenant.projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Mark the project as completed.
     */
    public function complete(Project $project): RedirectResponse
    {
        // Only the project owner can mark it as completed
        if (Auth::id() !== $project->user_id) {
            abort(403, 'You do not have permission to modify this project.');
        }

        $this->projectService->markProjectAsCompleted($project->id);

        return back()->with('success', 'Project marked as completed.');
    }

    /**
     * Mark the project as active.
     */
    public function reactivate(Project $project): RedirectResponse
    {
        // Only the project owner can mark it as active
        if (Auth::id() !== $project->user_id) {
            abort(403, 'You do not have permission to modify this project.');
        }

        $this->projectService->markProjectAsActive($project->id);

        return back()->with('success', 'Project reactivated.');
    }
}
