<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RiceVariety extends Model
{
    protected $fillable = ['name'];

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany{
        return $this->hasMany(Scan::class, 'variety_id');
    }
}
