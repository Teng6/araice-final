<?php

namespace App\Models;

use App\Enums\TreatmentTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    protected function casts(): array
    {
        return [
            'type' => TreatmentTypeEnum::class,
        ];
    }
}
