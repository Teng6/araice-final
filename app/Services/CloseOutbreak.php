<?php

namespace App\Services;

use App\Enums\OutbreakStatusEnum;
use App\Models\Outbreak;
use App\Models\User;

class CloseOutbreak
{
    public function close(User $user, Outbreak $outbreak): ?Outbreak
    {
        if ($outbreak->status === OutbreakStatusEnum::Resolved) {
            return null;
        }

        $outbreak->update([
            'status' => OutbreakStatusEnum::Resolved,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        return $outbreak->refresh();
    }
}
