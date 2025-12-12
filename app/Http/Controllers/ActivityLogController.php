<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        $currentUser = auth()->user();

        abort_unless($currentUser->hasPermissionTo('users.viewAny'), 403);

        $activities = Activity::query()
            ->whereIn('subject_type', [Task::class, Project::class])
            ->with(['subject' => function ($query) {
                $query->withTrashed();
            }, 'causer'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('activity-log.index', [
            'activities' => $activities,
        ]);
    }
}
