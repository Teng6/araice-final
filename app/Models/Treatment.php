<?php

namespace App\Models;

use App\Enums\TreatmentTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Treatments extends Model
{
    protected function casts(): array {
        return [
            'type' => TreatmentTypeEnum::class,
        ];
    }
}
