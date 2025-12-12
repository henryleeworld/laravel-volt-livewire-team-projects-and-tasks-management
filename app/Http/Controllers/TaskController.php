<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->where('organization_id', auth()->user()->organization_id)
            ->orderByDesc('created_at')
            ->get();

        return view('tasks.index', [
            'tasks' => $tasks,
        ]);
    }

    /**
     * Display the resource.
     */
    public function show(Task $task): View
    {
        Gate::authorize('view', $task);

        $activities = $task->activities()
            ->with('causer')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('tasks.show', [
            'task' => $task,
            'activities' => $activities,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', Task::class);

        return view('tasks.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request): RedirectResponse
    {
        if (! $request->user()->organization->canCreateTask()) {
            $limit = $request->user()->organization->getTaskLimit();

            return redirect()
                ->route('billing.index')
                ->with('error', __('You\'ve reached your limit of :limit tasks. Upgrade to create more.', ['limit' => $limit]));
        }

        $validated = $request->validated();

        Task::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
        ]);

        return redirect()
            ->route('tasks.index')
            ->with('success', __('Task created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task): View
    {
        Gate::authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return redirect()
            ->route('tasks.index')
            ->with('success', __('Task updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('tasks.index')
            ->with('success', __('Task deleted successfully.'));
    }
}
