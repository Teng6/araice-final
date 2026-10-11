<?php

namespace App\Http\Controllers;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isFarmer = $user->role === UserRole::Farmer;
        $ownMunicipality = $isFarmer ? $user->farmerProfile?->municipality : null;

        $activeOutbreaks = Outbreak::query()
            ->select(['id', 'municipality'])
            ->where('status', OutbreakStatusEnum::Active)
            ->with('alerts:id,outbreak_id,severity')
            ->get()
            ->groupBy(fn (Outbreak $outbreak) => $outbreak->municipality->value)
            ->map(fn ($outbreaks) => $outbreaks
                ->flatMap(fn (Outbreak $outbreak) => $outbreak->alerts)
                ->sortByDesc(fn ($alert) => $alert->severity->rank())
                ->first()?->severity->value ?? 'low');

        $municipalityStatuses = collect(MunicipalityEnum::cases())
            ->map(fn (MunicipalityEnum $municipality) => [
                'municipality' => $municipality->value,
                'severity' => $activeOutbreaks->get($municipality->value),
            ]);

        $outbreaks = Outbreak::query()
            ->with(['disease:id,name', 'alerts'])
            ->where('status', OutbreakStatusEnum::Active)
            ->when($isFarmer, fn ($query) => $query->where('municipality', $ownMunicipality))
            ->get()
            ->map(fn (Outbreak $outbreak) => [
                'id' => $outbreak->id,
                'disease' => $outbreak->disease->name,
                'municipality' => $outbreak->municipality->value,
                'created_at' => $outbreak->created_at?->toDateString(),
                'severity' => $outbreak->alerts
                    ->sortByDesc(fn ($alert) => $alert->severity->rank())
                    ->first()?->severity->value ?? 'low',
            ]);

        $farms = ($user->role === UserRole::Farmer
            ? collect([$user->farmerProfile])->filter()
            : FarmerProfile::query()
                ->whereHas('scans.outbreak', fn ($query) => $query->where('status', OutbreakStatusEnum::Active))
                ->get())
            ->values()
            ->map(fn (FarmerProfile $farm) => [
                'id' => $farm->id,
                'name' => $farm->full_name,
                'lat' => (float) $farm->farm_lat,
                'lng' => (float) $farm->farm_long,
            ]);

        return Inertia::render('map/index', [
            'outbreaks' => $outbreaks,
            'municipalityStatuses' => $municipalityStatuses,
            'ownMunicipality' => $ownMunicipality?->value,
            'farms' => $farms,
        ]);
    }
}
