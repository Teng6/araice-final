<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class FarmerProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::LguStaff;
    }
}
