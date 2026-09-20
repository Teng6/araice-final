<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmerProfile extends Model
{
    protected function casts(): array
    {
        return [
            'municipality' => MunicipalityEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
