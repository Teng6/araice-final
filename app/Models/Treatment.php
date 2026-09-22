<?php

namespace App\Models;

use App\Enums\TreatmentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Treatment extends Model
{
    protected $fillable = ['title', 'description', 'type'];

    protected function casts(): array
    {
        return [
            'type' => TreatmentTypeEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Disease, $this>
     */
    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }
}
