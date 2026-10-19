<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateFarmerRegistration
{
    /**
     * @param  array{name: string, email: string, password: string, barangay: string, municipality: string, contact_number: string, farm_lat: float, farm_long: float}  $validated
     */
    public function create(array $validated): User
    {
        if (! $this->insideMunicipality(
            $validated['municipality'],
            (float) $validated['farm_lat'],
            (float) $validated['farm_long'],
        )) {
            throw ValidationException::withMessages([
                'farm_lat' => 'These coordinates are not inside the selected municipality.',
            ]);
        }

        return DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->forceFill(['role' => UserRole::Farmer])->save();

            if (! config('auth.require_email_verification')) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $user->farmerProfile()->create([
                'full_name' => $validated['name'],
                'barangay' => $validated['barangay'],
                'municipality' => $validated['municipality'],
                'contact_number' => $validated['contact_number'],
                'farm_lat' => $validated['farm_lat'],
                'farm_long' => $validated['farm_long'],
            ]);

            return $user;
        });
    }

    private function insideMunicipality(string $slug, float $lat, float $lng): bool
    {
        $json = file_get_contents(public_path('geo/bataan-municipalities.geojson'));

        if ($json === false) {
            return false;
        }

        /** @var array{features: list<array{properties: array<string, mixed>, geometry: array{type: string, coordinates: array<mixed>}}>} $collection */
        $collection = json_decode($json, true);

        foreach ($collection['features'] as $feature) {
            if (($feature['properties']['municipality'] ?? null) !== $slug) {
                continue;
            }

            $geometry = $feature['geometry'];

            /** @var list<list<list<array{0: float, 1: float}>>> $polygons */
            $polygons = $geometry['type'] === 'Polygon'
                ? [$geometry['coordinates']]
                : $geometry['coordinates'];

            foreach ($polygons as $rings) {
                $outer = array_shift($rings);

                if (! $this->insideRing($outer, $lat, $lng)) {
                    continue;
                }

                foreach ($rings as $hole) {
                    if ($this->insideRing($hole, $lat, $lng)) {
                        continue 2;
                    }
                }

                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * @param  array<int, array{0: float, 1: float}>  $ring  GeoJSON ring of [lng, lat] points
     */
    private function insideRing(array $ring, float $lat, float $lng): bool
    {
        $inside = false;

        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];

            if (($yi > $lat) !== ($yj > $lat)
                && $lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
