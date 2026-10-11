<?php

namespace Database\Seeders;

use App\Enums\MunicipalityEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiTestSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = $this->user('API Farmer One', 'api-farmer@test.local', UserRole::Farmer);
        FarmerProfile::query()->updateOrCreate(
            ['user_id' => $farmer->id],
            [
                'full_name' => $farmer->name,
                'barangay' => 'Tuyo',
                'municipality' => MunicipalityEnum::Balanga,
                'contact_number' => '09170000001',
                'farm_lat' => 14.6760,
                'farm_long' => 120.5360,
            ],
        );

        $farmer2 = $this->user('API Farmer Two', 'api-farmer2@test.local', UserRole::Farmer);
        FarmerProfile::query()->updateOrCreate(
            ['user_id' => $farmer2->id],
            [
                'full_name' => $farmer2->name,
                'barangay' => 'Wawa',
                'municipality' => MunicipalityEnum::Orion,
                'contact_number' => '09170000002',
                'farm_lat' => 14.6200,
                'farm_long' => 120.5800,
            ],
        );

        $this->user('API LGU Staff', 'api-lgu@test.local', UserRole::LguStaff);
        $this->user('API Admin', 'api-admin@test.local', UserRole::Admin);
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ],
        );

        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
