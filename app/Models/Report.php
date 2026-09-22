<?php

namespace App\Models;

use App\Enums\MunicipalityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = ['summary_data', 'municipality', 'range_start', 'range_end'];

    protected function casts(): array
    {
        return [
            'municipality' => MunicipalityEnum::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
