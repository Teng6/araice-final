<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UpdateUserPassword
{
    public function update(User $user, string $password, bool $revokeOtherTokens = false): void
    {
        $user->update(['password' => Hash::make($password)]);

        if (! $revokeOtherTokens) {
            return;
        }

        $currentToken = $user->currentAccessToken();
        $this->revokeOtherTokens($user, $currentToken);
    }

    private function revokeOtherTokens(User $user, mixed $currentToken): void
    {
        if ($currentToken instanceof PersonalAccessToken) {
            $user->tokens()->whereKeyNot($currentToken->getKey())->delete();
        }
    }
}
