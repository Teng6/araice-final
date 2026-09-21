<?php

namespace App\Models;

use App\Enums\ScanStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scan extends Model
{
    protected $fillable = ['farmer_id', 'uploaded_by', 'scan_type', 'disease_id', 'variety_id',
        'outbreak_id', 'image_url', 'confidence_score', 'raw_predictions', 'status', 'gps_lat',
        'gps_long', 'scan_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScanStatusEnum::class,
            'raw_predictions' => 'array',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(FarmerProfile::class, 'farmer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(RiceVariety::class, 'variety_id');
    }
}
