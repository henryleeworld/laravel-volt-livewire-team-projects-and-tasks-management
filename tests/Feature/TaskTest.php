<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskTest extends TestCase
{
    public function test_guests_cannot_access_tasks_index()
    {
        $this->get(route('tasks.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_tasks_index()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertSuccessful()
            ->assertSee(__('Tasks'));
    }

    public function test_users_can_only_see_tasks_from_their_organization()
    {
        $organization1 = Organization::factory()->create(['name' => 'Org 1']);
        $organization2 = Organization::factory()->create(['name' => 'Org 2']);

        $user1 = User::factory()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->create(['organization_id' => $organization2->id]);

        Task::factory()->create([
            'name' => 'Org 1 Task',
            'organization_id' => $organization1->id,
            'user_id' => $user1->id,
        ]);

        Task::factory()->create([
            'name' => 'Org 2 Task',
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->get(route('tasks.index'))
            ->assertSuccessful()
            ->assertSee('Org 1 Task')
            ->assertDontSee('Org 2 Task');
    }

    public function test_authenticated_users_can_view_create_task_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tasks.create'))
            ->assertSuccessful()
            ->assertSee(__('Create New Task'));
    }

    public function test_users_can_create_task_with_name_only()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'name' => 'New Task',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', __('Task created successfully.'));

        $this->assertDatabaseHas('tasks', [
            'name' => 'New Task',
            'description' => null,
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);
    }

    public function test_users_can_create_task_with_name_and_description()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'name' => 'Complete Task',
                'description' => 'This is a detailed description',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', __('Task created successfully.'));

        $this->assertDatabaseHas('tasks', [
            'name' => 'Complete Task',
            'description' => 'This is a detailed description',
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
        ]);
    }

    public function test_task_name_is_required_when_creating()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'name' => '',
                'description' => 'Description without name',
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('tasks', [
            'description' => 'Description without name',
        ]);
    }

    public function test_task_name_cannot_exceed_255_characters()
    {
        $user = User::factory()->create();
        $longName = str_repeat('a', 256);

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'name' => $longName,
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_users_can_view_edit_task_page_for_their_organization_tasks()
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.edit', $task))
            ->assertSuccessful()
            ->assertSee(__('Edit Task'))
            ->assertSee($task->name);
    }

    public function test_users_cannot_view_edit_page_for_tasks_from_other_organizations()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $user1 = User::factory()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->create(['organization_id' => $organization2->id]);

        $task = Task::factory()->create([
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->get(route('tasks.edit', $task))
            ->assertForbidden();
    }

    public function test_users_can_update_their_organization_tasks()
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', __('Task updated successfully.'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);
    }

    public function test_users_cannot_update_tasks_from_other_organizations()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $user1 = User::factory()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->create(['organization_id' => $organization2->id]);

        $task = Task::factory()->create([
            'name' => 'Original Name',
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->put(route('tasks.update', $task), [
                'name' => 'Hacked Name',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_task_name_is_required_when_updating()
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('tasks.update', $task), [
                'name' => '',
            ])
            ->assertSessionHasErrors(['name']);
    }

    public function test_users_can_delete_their_organization_tasks()
    {
        $user = User::factory()->create();

        $task = Task::factory()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', __('Task deleted successfully.'));

        $this->assertSoftDeleted('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_users_cannot_delete_tasks_from_other_organizations()
    {
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();

        $user1 = User::factory()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->create(['organization_id' => $organization2->id]);

        $task = Task::factory()->create([
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
        ]);
    }
}
