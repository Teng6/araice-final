<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected function casts(): array
    {
        return [
            'municipality' => MunicipalityEnum::class,
        ];
    }
}
