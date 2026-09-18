<?php

namespace App\Models;

use App\Enums\OutbreakStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Outbreak extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OutbreakStatusEnum::class,
        ];
    }
}
