<?php

namespace App\Http\Resources\Api;

use App\Models\Outbreak;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Outbreak */
class OutbreakResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disease' => [
                'id' => $this->disease->id,
                'name' => $this->disease->name,
            ],
            'municipality' => $this->municipality->value,
            'status' => $this->status->value,
            'severity' => $this->alerts
                ->sortByDesc(fn ($alert): int => $alert->severity->rank())
                ->first()?->severity->value,
            'farmers_count' => $this->farmers_count,
            'scans_count' => $this->scans_count,
            'created_at' => $this->created_at,
            'closed_at' => $this->closed_at,
            'closed_by' => $this->closedBy === null ? null : [
                'id' => $this->closedBy->id,
                'name' => $this->closedBy->name,
            ],
            ...($this->resource->relationLoaded('scans') ? [
                'alerts' => $this->alerts
                    ->map(fn ($alert): array => [
                        'id' => $alert->id,
                        'message' => $alert->message,
                        'severity' => $alert->severity->value,
                        'created_at' => $alert->created_at,
                    ])
                    ->values()
                    ->all(),
                'scans' => $this->scans
                    ->map(fn (Scan $scan): array => [
                        'id' => $scan->id,
                        'farmer_name' => $scan->farmer->full_name,
                        'disease' => $scan->disease === null ? null : [
                            'id' => $scan->disease->id,
                            'name' => $scan->disease->name,
                        ],
                        'created_at' => $scan->created_at,
                    ])
                    ->values()
                    ->all(),
            ] : []),
        ];
    }
}
