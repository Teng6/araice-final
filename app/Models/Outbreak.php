<?php

namespace App\Models;

use App\Enums\StatusEnum;
use Illuminate\Database\Eloquent\Model;

class Outbreak extends Model
{
    protected function casts(): array
    {
        return [
            'status' => StatusEnum::class,
        ];
    }
}
