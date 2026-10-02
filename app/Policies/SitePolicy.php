<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMealOfficer();
    }

    public function view(User $user, Site $site): bool
    {
        return $user->isMealOfficer();
    }

    public function create(User $user): bool
    {
        return $user->isMealOfficer();
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isMealOfficer();
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isMealOfficer();
    }
}
