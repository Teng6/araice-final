<?php

namespace App\Models;

use App\Enums\ScanStatusEnum;
use Illuminate\Database\Eloquent\Model;

class Scan extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ScanStatusEnum::class,
        ];
    }
}
