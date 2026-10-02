<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view any settings.
     * Only MEAL Officers can manage RAG benchmark thresholds.
     */
    public function viewAny(User $user): bool
    {
        return $user->isMealOfficer();
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->isMealOfficer();
    }

    public function create(User $user): bool
    {
        return $user->isMealOfficer();
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->isMealOfficer();
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->isMealOfficer();
    }
}
