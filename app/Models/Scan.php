<?php

namespace App\Models;

use App\Enums\ScanStatusEnum;
use Illuminate\Database\Eloquent\Model;

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
        ];
    }
}
