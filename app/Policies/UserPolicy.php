<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * MEAL Officers have full system administration.
     */
    public function viewAny(User $user): bool
    {
        return $user->isMealOfficer();
    }

    /**
     * Determine whether the user can view the user model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isMealOfficer() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isMealOfficer();
    }

    /**
     * Determine whether the user can update the user model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isMealOfficer() || $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isMealOfficer() && $user->id !== $model->id;
    }
}
