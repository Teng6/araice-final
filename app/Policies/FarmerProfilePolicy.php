<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;

class FarmerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::LguStaff;
    }

    public function viewAdminList(User $user): bool
    {
        return false;
    }

    public function view(User $user, FarmerProfile $farmer): bool
    {
        return false;
    }

    public function update(User $user, FarmerProfile $farmer): bool
    {
        return false;
    }
}
