<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionLimitsTest extends TestCase
{
    public function test_free_plan_organization_has_10_task_limit()
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $this->assertEquals(10, $organization->getTaskLimit());
        $this->assertTrue($organization->canCreateTask());
    }

    public function test_free_plan_cannot_access_projects()
    {
        $organization = Organization::factory()->create();

        $this->assertFalse($organization->canAccessProjects());
    }

    public function test_task_creation_is_blocked_when_free_plan_reaches_limit()
    {
        $organization = Organization::factory()->create();

        $userRole = Role::firstOrCreate(['name' => RoleEnum::User->value]);
        Permission::firstOrCreate(['name' => 'tasks.create']);
        $userRole->givePermissionTo('tasks.create');

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($userRole);

        Task::factory()->count(10)->create([
            'organization_id' => $organization->id
        ]);

        $response = $this->actingAs($user)->post(route('tasks.store'), [
            'name' => 'Test Task',
            'description' => 'Test Description',
        ]);

        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('error');

        $this->assertEquals(
            10,
            Task::where('organization_id', $organization->id)->count()
        );
    }

    public function test_free_plan_users_can_view_existing_tasks()
    {
        $organization = Organization::factory()->create();

        $userRole = Role::firstOrCreate(['name' => RoleEnum::User->value]);
        Permission::firstOrCreate(['name' => 'tasks.viewAny']);
        $userRole->givePermissionTo('tasks.viewAny');

        $user = User::factory()->create(['organization_id' => $organization->id]);
        $user->assignRole($userRole);

        Task::factory()->count(5)->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk();
    }
}
