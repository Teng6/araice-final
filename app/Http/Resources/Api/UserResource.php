<?php

namespace App\Http\Resources\Api;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userData = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
        ];

        if ($this->role === UserRole::Farmer && $this->farmerProfile !== null) {
            $profile = $this->farmerProfile;
            $userData['farmer_profile'] = [
                'municipality' => $profile->municipality->value,
                'barangay' => $profile->barangay,
                'contact_number' => $profile->contact_number,
                'farm_lat' => $profile->farm_lat,
                'farm_long' => $profile->farm_long,
                'plot_count' => $profile->plot_count,
            ];
        }

        return $userData;
    }
}
