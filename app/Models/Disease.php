<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disease extends Model
{
    protected $fillable = ['name', 'description', 'prevention_tips'];

    /**
     * @return HasMany<Scan, $this>
     */
    public function scans(): HasMany{
        return $this->hasMany(Scan::class);     
    }

    /**
     * @return HasMany<Outbreak, $this>
     */
    public function outbreaks(): HasMany{
        return $this->hasMany(Outbreak::class);
    }
    
    /**
     * @return HasMany<Treatment, $this>
     */
    public function treatments(): HasMany{
        return $this->hasMany(Treatment::class);
    }

}
