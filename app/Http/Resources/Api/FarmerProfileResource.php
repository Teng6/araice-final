<?php

namespace App\Http\Resources\Api;

use App\Models\FarmerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FarmerProfile */
class FarmerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'barangay' => $this->barangay,
            'municipality' => $this->municipality->value,
            'contact_number' => $this->contact_number,
        ];
    }
}
