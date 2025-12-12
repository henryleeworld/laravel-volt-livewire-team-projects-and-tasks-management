<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    public function test_task_creation_is_logged()
    {
        $user = User::factory()->create();

        $task = Task::create([
            'name' => 'New Task',
            'description' => 'Task description',
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $exists = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('event', 'created')
            ->exists();

        $this->assertTrue($exists);
    }

    public function test_task_update_is_logged()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $task->update(['name' => 'Updated Task Name']);

        $exists = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('event', 'updated')
            ->exists();

        $this->assertTrue($exists);
    }

    public function test_project_creation_is_logged()
    {
        $user = User::factory()->create();

        $project = Project::create([
            'name' => 'New Project',
            'description' => 'Project description',
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $exists = Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->where('event', 'created')
            ->exists();

        $this->assertTrue($exists);
    }

    public function test_admin_users_can_view_activity_log_page()
    {
        $organization = Organization::factory()->create();
        $adminUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $adminUser->assignRole('admin');

        $this->actingAs($adminUser)
            ->get(route('activity-log.index'))
            ->assertStatus(200)
            ->assertSee(__('Activity Log'));
    }

    public function test_non_admin_users_cannot_view_activity_log_page()
    {
        $user = User::factory()->asViewer()->create();

        $this->actingAs($user)
            ->get(route('activity-log.index'))
            ->assertForbidden();
    }

    public function test_activity_log_displays_task_and_project_activities()
    {
        $organization = Organization::factory()->create();
        $adminUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $adminUser->assignRole('admin');

        Task::create([
            'name' => 'Test Task',
            'description' => 'Task description',
            'user_id' => $adminUser->id,
            'organization_id' => $organization->id,
        ]);

        Project::create([
            'name' => 'Test Project',
            'description' => 'Project description',
            'user_id' => $adminUser->id,
            'organization_id' => $organization->id,
        ]);

        $this->actingAs($adminUser)
            ->get(route('activity-log.index'))
            ->assertStatus(200)
            ->assertSee(__('Task has been :event_name', ['event_name' => __('created')]))
            ->assertSee(__('Project has been :event_name', ['event_name' => __('created')]));
    }

    public function test_task_show_page_displays_recent_activity()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $task->update(['name' => 'Updated Task']);

        $this->actingAs($user)
            ->get(route('tasks.show', $task))
            ->assertStatus(200)
            ->assertSee(__('Recent Activity'))
            ->assertSee(__('Task has been :event_name', ['event_name' => __('updated')]));
    }

    public function test_project_show_page_displays_recent_activity()
    {
        $organization = Organization::factory()->create();
        $organization->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test',
            'stripe_status' => 'active',
            'stripe_price' => config('subscriptions.plans.pro.prices.monthly'),
            'quantity' => 1,
        ]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $project->update(['name' => 'Updated Project']);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertStatus(200)
            ->assertSee(__('Recent Activity'))
            ->assertSee(__('Project has been :event_name', ['event_name' => __('updated')]));
    }

    public function test_task_soft_delete_is_logged()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $task->delete();

        $exists = Activity::where('subject_type', Task::class)
            ->where('subject_id', $task->id)
            ->where('event', 'deleted')
            ->exists();

        $this->assertTrue($exists);
        $this->assertTrue($task->fresh()->trashed());
    }

    public function test_project_soft_delete_is_logged()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $project->delete();

        $exists = Activity::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->where('event', 'deleted')
            ->exists();

        $this->assertTrue($exists);
        $this->assertTrue($project->fresh()->trashed());
    }

    public function test_soft_deleted_task_activities_are_shown_in_activity_log()
    {
        $organization = Organization::factory()->create();
        $adminUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $adminUser->assignRole('admin');

        $task = Task::create([
            'name' => 'Task to Delete',
            'description' => 'Task description',
            'user_id' => $adminUser->id,
            'organization_id' => $organization->id,
        ]);

        $task->delete();

        $this->actingAs($adminUser)
            ->get(route('activity-log.index'))
            ->assertStatus(200)
            ->assertSee(__('Task has been :event_name', ['event_name' => __('deleted')]))
            ->assertSee(__('Deleted'));
    }

    public function test_soft_deleted_project_activities_are_shown_in_activity_log()
    {
        $organization = Organization::factory()->create();
        $organization->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_test',
            'stripe_status' => 'active',
            'stripe_price' => config('subscriptions.plans.pro.prices.monthly'),
            'quantity' => 1,
        ]);

        $adminUser = User::factory()->create([
            'organization_id' => $organization->id,
        ]);
        $adminUser->assignRole('admin');

        $project = Project::create([
            'name' => 'Project to Delete',
            'description' => 'Project description',
            'user_id' => $adminUser->id,
            'organization_id' => $organization->id,
        ]);

        $project->delete();

        $this->actingAs($adminUser)
            ->get(route('activity-log.index'))
            ->assertStatus(200)
            ->assertSee(__('Project has been :event_name', ['event_name' => __('deleted')]))
            ->assertSee(__('Deleted'));
    }

    public function test_tasks_can_be_restored_after_soft_delete()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $task->delete();
        $this->assertTrue($task->fresh()->trashed());

        $task->restore();
        $this->assertFalse($task->fresh()->trashed());
    }

    public function test_projects_can_be_restored_after_soft_delete()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);

        $project->delete();
        $this->assertTrue($project->fresh()->trashed());

        $project->restore();
        $this->assertFalse($project->fresh()->trashed());
    }
}
