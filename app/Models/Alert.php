<?php

namespace App\Models;

use App\Enums\SeverityEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alert extends Model
{
    protected $fillable = ['message', 'severity'];

    protected function casts(): array
    {
        return [
            'severity' => SeverityEnum::class,
        ];
    }

    /**
     * @return BelongsTo<Outbreak, $this>
     */
    public function outbreak(): BelongsTo
    {
        return $this->belongsTo(Outbreak::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'alert_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
