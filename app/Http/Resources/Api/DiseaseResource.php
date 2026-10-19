<?php

namespace App\Http\Resources\Api;

use App\Models\Disease;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Disease */
class DiseaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_path === null
                ? null
                : url('/'.ltrim($this->image_path, '/')),
            ...($this->resource->relationLoaded('treatments') ? [
                'causes' => $this->causes,
                'symptoms' => $this->symptoms,
                'history' => $this->history,
                'prevention_tips' => $this->prevention_tips,
                'sources' => $this->sources,
                'treatments' => $this->treatments
                    ->map(fn (Treatment $treatment): array => [
                        'id' => $treatment->id,
                        'title' => $treatment->title,
                        'description' => $treatment->description,
                        'type' => $treatment->type->value,
                    ])
                    ->values()
                    ->all(),
            ] : []),
        ];
    }
}
