<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Notifications\TaskAssigned;
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
            ->with('assignedToUser', 'media')
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

        $task->load('media');

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

        $users = auth()->user()->organization->users()->orderBy('name')->get();

        return view('tasks.create', [
            'users' => $users,
        ]);
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

        $task = Task::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'user_id' => $request->user()->id,
            'organization_id' => $request->user()->organization_id,
            'assigned_to_user_id' => $validated['assigned_to_user_id'] ?? null,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $task->addMedia($image)->toMediaCollection('images');
            }
        }

        if ($task->assigned_to_user_id) {
            $task->assignedToUser->notify(new TaskAssigned($task));
        }

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

        $task->load('media');

        $users = auth()->user()->organization->users()->orderBy('name')->get();

        return view('tasks.edit', [
            'task' => $task,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $validated = $request->validated();
        $previousAssignedUserId = $task->assigned_to_user_id;

        $task->update($validated);

        if (! empty($validated['delete_images'])) {
            $task->media()
                ->whereIn('id', $validated['delete_images'])
                ->get()
                ->each(fn ($media) => $media->delete());
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $task->addMedia($image)->toMediaCollection('images');
            }
        }

        if (isset($validated['assigned_to_user_id']) && $validated['assigned_to_user_id'] !== $previousAssignedUserId && $validated['assigned_to_user_id'] !== null) {
            $task->assignedToUser->notify(new TaskAssigned($task));
        }

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
