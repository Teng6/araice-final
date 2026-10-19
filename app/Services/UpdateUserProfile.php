<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserProfile
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if ($user->isDirty('email') && config('auth.require_email_verification')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($user->role === UserRole::Farmer && $user->farmerProfile !== null) {
                $profileData = array_intersect_key($data, array_flip([
                    'barangay',
                    'municipality',
                    'contact_number',
                    'farm_lat',
                    'farm_long',
                    'plot_count',
                ]));
                $profileData['full_name'] = $user->name;

                $user->farmerProfile->fill($profileData)->save();
            }

            return $user->load('farmerProfile');
        });
    }
}
