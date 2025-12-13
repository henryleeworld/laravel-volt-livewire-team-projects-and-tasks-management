<?php

namespace Tests\Feature;

use App\Livewire\Notifications\NotificationBell;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_task_assignment_creates_database_notification()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id, 'name' => 'Assigner User']);
        $assignee = User::factory()->create(['organization_id' => $organization->id, 'email' => 'assignee@example.com']);

        $this->actingAs($assigner)
            ->post(route('tasks.store'), [
                'name' => 'Notification Test Task',
                'description' => 'This is a test task description',
                'assigned_to_user_id' => $assignee->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertCount(1, $assignee->unreadNotifications);

        $notification = $assignee->unreadNotifications->first();

        $this->assertEquals(TaskAssigned::class, $notification->type);
        $this->assertEquals('Notification Test Task', $notification->data['task_name']);
        $this->assertEquals('This is a test task description', $notification->data['task_description']);
        $this->assertEquals('Assigner User', $notification->data['assigner_name']);
        $this->assertStringContainsString('tasks', $notification->data['action_url']);
        $this->assertNull($notification->read_at);
    }

    public function test_notification_bell_shows_correct_unread_count()
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        Task::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ])->each(fn($task) => $assignee->notify(new TaskAssigned($task)));

        $this->actingAs($assignee);

        Livewire::test(NotificationBell::class)
            ->assertSee('3');
    }

    public function test_notification_bell_displays_unread_notifications()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id, 'name' => 'John Doe']);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        $task = Task::factory()->create([
            'name' => 'Test Notification Task',
            'description' => 'Task description for notification',
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        $this->actingAs($assignee);

        Livewire::test(NotificationBell::class)
            ->assertSee('Test Notification Task')
            ->assertSee('John Doe');
    }

    public function test_mark_all_as_read_works_correctly()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        Task::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ])->each(fn($task) => $assignee->notify(new TaskAssigned($task)));

        $this->actingAs($assignee);

        $this->assertCount(3, $assignee->unreadNotifications);

        Livewire::test(NotificationBell::class)->call('markAllAsRead');

        $assignee->refresh();

        $this->assertCount(0, $assignee->unreadNotifications);
    }

    public function test_notifications_link_to_correct_task()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        $this->actingAs($assignee);

        $notification = $assignee->unreadNotifications->first();

        $this->assertEquals(route('tasks.show', $task), $notification->data['action_url']);
    }

    public function test_empty_state_displays_when_no_notifications()
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(NotificationBell::class)
            ->assertSee(__('No new notifications'));
    }

    public function test_only_unread_notifications_appear_in_bell_dropdown()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        $task1 = Task::factory()->create([
            'name' => 'Unread Task',
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $task2 = Task::factory()->create([
            'name' => 'Read Task',
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task1));
        $assignee->notify(new TaskAssigned($task2));

        $assignee->notifications()
            ->where('data->task_name', 'Read Task')
            ->first()
            ->markAsRead();

        $this->actingAs($assignee);

        Livewire::test(NotificationBell::class)
            ->assertSee('Unread Task')
            ->assertDontSee('Read Task');
    }

    public function test_notification_bell_limits_to_5_most_recent_notifications()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        $tasks = Task::factory()->count(7)->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        foreach ($tasks as $task) {
            $assignee->notify(new TaskAssigned($task));
        }

        $this->actingAs($assignee);

        $this->assertCount(7, $assignee->unreadNotifications);

        $component = Livewire::test(NotificationBell::class);

        $this->assertEquals(7, $component->unreadCount);

        $this->assertCount(5, $component->notifications);
    }

    public function test_notification_contains_all_required_data_fields()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id, 'name' => 'Jane Doe']);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        $task = Task::factory()->create([
            'name' => 'Data Test Task',
            'description' => 'Task description',
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        $notification = $assignee->notifications->first();

        $this->assertArrayHasKey('task_id', $notification->data);
        $this->assertArrayHasKey('task_name', $notification->data);
        $this->assertArrayHasKey('task_description', $notification->data);
        $this->assertArrayHasKey('assigner_name', $notification->data);
        $this->assertArrayHasKey('action_url', $notification->data);

        $this->assertEquals($task->id, $notification->data['task_id']);
        $this->assertEquals('Data Test Task', $notification->data['task_name']);
        $this->assertEquals('Task description', $notification->data['task_description']);
        $this->assertEquals('Jane Doe', $notification->data['assigner_name']);
    }

    public function test_notification_badge_shows_9_plus_for_more_than_9_unread()
    {
        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create(['organization_id' => $organization->id]);

        Task::factory()->count(12)->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ])->each(fn($task) => $assignee->notify(new TaskAssigned($task)));

        $this->actingAs($assignee);

        Livewire::test(NotificationBell::class)
            ->assertSee('9+');
    }

    public function test_email_notification_is_sent_when_enabled()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);
        $assignee = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'assignee@example.com',
            'email_notifications' => true,
        ]);

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        Notification::assertSentTo(
            $assignee,
            TaskAssigned::class,
            fn($notification, $channels) =>
                in_array('mail', $channels) && in_array('database', $channels)
        );
    }

    public function test_email_notification_not_sent_when_disabled()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);

        $assignee = User::factory()->create([
            'organization_id' => $organization->id,
            'email' => 'assignee@example.com',
            'email_notifications' => false,
        ]);

        $task = Task::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assignee->id,
        ]);

        $assignee->notify(new TaskAssigned($task));

        Notification::assertSentTo(
            $assignee,
            TaskAssigned::class,
            fn($notification, $channels) =>
                in_array('database', $channels) && ! in_array('mail', $channels)
        );
    }

    public function test_database_notification_sent_regardless_of_email_preference()
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $assigner = User::factory()->create(['organization_id' => $organization->id]);

        $assigneeWithEmail = User::factory()->create([
            'organization_id' => $organization->id,
            'email_notifications' => true,
        ]);

        $assigneeWithoutEmail = User::factory()->create([
            'organization_id' => $organization->id,
            'email_notifications' => false,
        ]);

        $task1 = Task::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assigneeWithEmail->id,
        ]);

        $task2 = Task::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $assigner->id,
            'assigned_to_user_id' => $assigneeWithoutEmail->id,
        ]);

        $assigneeWithEmail->notify(new TaskAssigned($task1));
        $assigneeWithoutEmail->notify(new TaskAssigned($task2));

        Notification::assertSentTo(
            $assigneeWithEmail,
            TaskAssigned::class,
            fn($notification, $channels) => in_array('database', $channels)
        );

        Notification::assertSentTo(
            $assigneeWithoutEmail,
            TaskAssigned::class,
            fn($notification, $channels) => in_array('database', $channels)
        );
    }
}
