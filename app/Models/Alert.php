<?php

namespace App\Models;

use App\Enums\SeverityEnum;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected function casts(): array
    {
        return [
            'severity' => SeverityEnum::class,
        ];
    }
}
