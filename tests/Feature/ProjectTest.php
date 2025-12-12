<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    public function test_guests_cannot_access_projects_index()
    {
        $this->get(route('projects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_users_can_view_projects_index()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();

        $this->actingAs($adminUser)
            ->get(route('projects.index'))
            ->assertSuccessful()
            ->assertSee(__('Projects'));
    }

    public function test_regular_users_can_view_projects_index()
    {
        $regularUser = User::factory()->withProjectAccess()->asUser()->create();

        $this->actingAs($regularUser)
            ->get(route('projects.index'))
            ->assertSuccessful()
            ->assertSee(__('Projects'));
    }

    public function test_viewers_can_view_projects_index()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();

        $this->actingAs($viewerUser)
            ->get(route('projects.index'))
            ->assertSuccessful()
            ->assertSee(__('Projects'));
    }

    public function test_users_can_only_see_projects_from_their_organization()
    {
        $organization1 = Organization::factory()->withProSubscription()->create(['name' => 'Org 1']);
        $organization2 = Organization::factory()->withProSubscription()->create(['name' => 'Org 2']);

        $user1 = User::factory()->asAdmin()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->asAdmin()->create(['organization_id' => $organization2->id]);

        $project1 = Project::factory()->create([
            'name' => 'Org 1 Project',
            'organization_id' => $organization1->id,
            'user_id' => $user1->id,
        ]);

        $project2 = Project::factory()->create([
            'name' => 'Org 2 Project',
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->get(route('projects.index'))
            ->assertSuccessful()
            ->assertSee('Org 1 Project')
            ->assertDontSee('Org 2 Project');
    }

    public function test_admin_users_can_view_create_project_page()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();

        $this->actingAs($adminUser)
            ->get(route('projects.create'))
            ->assertSuccessful()
            ->assertSee(__('Create New Project'));
    }

    public function test_regular_users_can_view_create_project_page()
    {
        $regularUser = User::factory()->withProjectAccess()->asUser()->create();

        $this->actingAs($regularUser)
            ->get(route('projects.create'))
            ->assertSuccessful()
            ->assertSee(__('Create New Project'));
    }

    public function test_viewers_cannot_view_create_project_page()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();

        $this->actingAs($viewerUser)
            ->get(route('projects.create'))
            ->assertForbidden();
    }

    public function test_admin_users_can_create_a_project()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();

        $this->actingAs($adminUser)
            ->post(route('projects.store'), [
                'name' => 'New Project',
                'description' => 'Project description',
            ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', __('Project created successfully.'));

        $this->assertDatabaseHas('projects', [
            'name' => 'New Project',
            'description' => 'Project description',
            'user_id' => $adminUser->id,
            'organization_id' => $adminUser->organization_id,
        ]);
    }

    public function test_viewers_cannot_create_a_project()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();

        $this->actingAs($viewerUser)
            ->post(route('projects.store'), [
                'name' => 'New Project',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('projects', [
            'name' => 'New Project',
        ]);
    }

    public function test_project_name_is_required_when_creating()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();

        $this->actingAs($adminUser)
            ->post(route('projects.store'), [
                'name' => '',
                'description' => 'Description without name',
            ])
            ->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('projects', [
            'description' => 'Description without name',
        ]);
    }

    public function test_admin_users_can_view_project_show_page()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();
        $project = Project::factory()->create([
            'organization_id' => $adminUser->organization_id,
            'user_id' => $adminUser->id,
        ]);

        $this->actingAs($adminUser)
            ->get(route('projects.show', $project))
            ->assertSuccessful()
            ->assertSee($project->name);
    }

    public function test_viewers_can_view_project_show_page()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();
        $project = Project::factory()->create([
            'organization_id' => $viewerUser->organization_id,
            'user_id' => $viewerUser->id,
        ]);

        $this->actingAs($viewerUser)
            ->get(route('projects.show', $project))
            ->assertSuccessful()
            ->assertSee($project->name);
    }

    public function test_users_cannot_view_projects_from_other_organizations()
    {
        $organization1 = Organization::factory()->withProSubscription()->create();
        $organization2 = Organization::factory()->withProSubscription()->create();

        $user1 = User::factory()->asAdmin()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->asAdmin()->create(['organization_id' => $organization2->id]);

        $project = Project::factory()->create([
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->get(route('projects.show', $project))
            ->assertForbidden();
    }

    public function test_admin_users_can_view_edit_project_page()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();
        $project = Project::factory()->create([
            'organization_id' => $adminUser->organization_id,
            'user_id' => $adminUser->id,
        ]);

        $this->actingAs($adminUser)
            ->get(route('projects.edit', $project))
            ->assertSuccessful()
            ->assertSee(__('Edit Project'))
            ->assertSee($project->name);
    }

    public function test_viewers_cannot_view_edit_project_page()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();
        $project = Project::factory()->create([
            'organization_id' => $viewerUser->organization_id,
            'user_id' => $viewerUser->id,
        ]);

        $this->actingAs($viewerUser)
            ->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    public function test_admin_users_can_update_projects()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();
        $project = Project::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description',
            'organization_id' => $adminUser->organization_id,
            'user_id' => $adminUser->id,
        ]);

        $this->actingAs($adminUser)
            ->put(route('projects.update', $project), [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
            ])
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', __('Project updated successfully.'));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);
    }

    public function test_viewers_cannot_update_projects()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();
        $project = Project::factory()->create([
            'name' => 'Original Name',
            'organization_id' => $viewerUser->organization_id,
            'user_id' => $viewerUser->id,
        ]);

        $this->actingAs($viewerUser)
            ->put(route('projects.update', $project), [
                'name' => 'Updated Name',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_users_cannot_update_projects_from_other_organizations()
    {
        $organization1 = Organization::factory()->withProSubscription()->create();
        $organization2 = Organization::factory()->withProSubscription()->create();

        $user1 = User::factory()->asAdmin()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->asAdmin()->create(['organization_id' => $organization2->id]);

        $project = Project::factory()->create([
            'name' => 'Original Name',
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->put(route('projects.update', $project), [
                'name' => 'Hacked Name',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_admin_users_can_delete_projects()
    {
        $adminUser = User::factory()->withProjectAccess()->asAdmin()->create();
        $project = Project::factory()->create([
            'organization_id' => $adminUser->organization_id,
            'user_id' => $adminUser->id,
        ]);

        $this->actingAs($adminUser)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'))
            ->assertSessionHas('success', __('Project deleted successfully.'));

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_regular_users_cannot_delete_projects()
    {
        $regularUser = User::factory()->withProjectAccess()->asUser()->create();
        $project = Project::factory()->create([
            'organization_id' => $regularUser->organization_id,
            'user_id' => $regularUser->id,
        ]);

        $this->actingAs($regularUser)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_viewers_cannot_delete_projects()
    {
        $viewerUser = User::factory()->withProjectAccess()->asViewer()->create();
        $project = Project::factory()->create([
            'organization_id' => $viewerUser->organization_id,
            'user_id' => $viewerUser->id,
        ]);

        $this->actingAs($viewerUser)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_users_cannot_delete_projects_from_other_organizations()
    {
        $organization1 = Organization::factory()->withProSubscription()->create();
        $organization2 = Organization::factory()->withProSubscription()->create();

        $user1 = User::factory()->asAdmin()->create(['organization_id' => $organization1->id]);
        $user2 = User::factory()->asAdmin()->create(['organization_id' => $organization2->id]);

        $project = Project::factory()->create([
            'organization_id' => $organization2->id,
            'user_id' => $user2->id,
        ]);

        $this->actingAs($user1)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }
}