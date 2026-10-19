<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->role === UserRole::Admin
            && in_array($target->role, [UserRole::Admin, UserRole::LguStaff], true);
    }

    public function deactivate(User $user, User $target): bool
    {
        return $user->role === UserRole::Admin && ! $user->is($target);
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->role === UserRole::Admin && ! $user->is($target);
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->role === UserRole::Admin && ! $user->is($target);
    }
}
