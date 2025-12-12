<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('projects.viewAny')
            && $user->organization->canAccessProjects();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.view')
            && $project->organization_id === $user->organization_id
            && $user->organization->canAccessProjects();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('projects.create')
            && $user->organization->canAccessProjects();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.update')
            && $project->organization_id === $user->organization_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.delete')
            && $project->organization_id === $user->organization_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.delete')
            && $project->organization_id === $user->organization_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.delete')
            && $project->organization_id === $user->organization_id;
    }
}
