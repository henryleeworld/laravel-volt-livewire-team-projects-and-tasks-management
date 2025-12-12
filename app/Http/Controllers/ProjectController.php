<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Project::class);

        $projects = Project::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->orderByDesc('created_at')
            ->get();

        return view('projects.index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Project::class);

        return view('projects.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', __('Project created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): View
    {
        Gate::authorize('view', $project);

        $activities = $project->activities()
            ->with('causer')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('projects.show', [
            'project' => $project,
            'activities' => $activities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): View
    {
        Gate::authorize('update', $project);

        return view('projects.edit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return redirect()
            ->route('projects.index')
            ->with('success', __('Project updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', __('Project deleted successfully.'));
    }
}
