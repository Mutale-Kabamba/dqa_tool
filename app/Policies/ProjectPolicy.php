<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any projects.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the project.
     * Project officers can only see their designated projects.
     */
    public function view(User $user, Project $project): bool
    {
        if ($user->isMealOfficer()) {
            return true;
        }

        if ($user->isProjectOfficer()) {
            return (int) $project->project_officer_id === (int) $user->id;
        }

        return true;
    }

    /**
     * Determine whether the user can create projects.
     * Only MEAL Officers can create/manage projects.
     */
    public function create(User $user): bool
    {
        return $user->isMealOfficer();
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isMealOfficer();
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isMealOfficer();
    }
}
