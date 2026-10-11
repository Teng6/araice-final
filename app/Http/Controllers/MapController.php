<?php

namespace App\Http\Controllers;

use App\Enums\MunicipalityEnum;
use App\Enums\OutbreakStatusEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\Outbreak;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        if ($isFarmer) {
            $farmer = $user->farmerProfile;
            $farms = $farmer === null
                ? collect()
                : collect([$farmer->load([
                    'scans' => fn ($query) => $query
                        ->select(['id', 'farmer_id', 'disease_id', 'scan_date'])
                        ->with('disease:id,name')
                        ->latest('scan_date')
                        ->latest('id')
                        ->limit(1),
                ])]);
        } else {
            $farms = FarmerProfile::query()
                ->select([
                    'id',
                    'full_name',
                    'barangay',
                    'municipality',
                    'contact_number',
                    'farm_lat',
                    'farm_long',
                ])
                ->whereHas('scans.outbreak', fn ($query) => $query->where('status', OutbreakStatusEnum::Active))
                ->with([
                    'scans' => fn ($query) => $query
                        ->select(['id', 'farmer_id', 'disease_id', 'scan_date'])
                        ->whereHas('outbreak', fn ($outbreakQuery) => $outbreakQuery
                            ->where('status', OutbreakStatusEnum::Active))
                        ->with('disease:id,name')
                        ->latest('scan_date')
                        ->latest('id')
                        ->limit(1),
                ])
                ->get();
        }

        $farms = $farms
            ->values()
            ->map(function (FarmerProfile $farm) use ($isFarmer) {
                /** @var Scan|null $scan */
                $scan = $farm->scans->first();

                return [
                    'id' => $farm->id,
                    ...($isFarmer ? [] : [
                        'name' => $farm->full_name,
                        'contact_number' => $farm->contact_number,
                    ]),
                    'barangay' => $farm->barangay,
                    'municipality' => $farm->municipality->value,
                    'lat' => (float) $farm->farm_lat,
                    'lng' => (float) $farm->farm_long,
                    'scan' => $scan === null ? null : [
                        'id' => $scan->id,
                        'disease' => $scan->disease?->name,
                        'scan_date' => $scan->scan_date === null
                            ? null
                            : Carbon::parse($scan->scan_date)->toDateString(),
                    ],
                ];
            });

        return Inertia::render('map/index', [
            'outbreaks' => $outbreaks,
            'municipalityStatuses' => $municipalityStatuses,
            'ownMunicipality' => $ownMunicipality?->value,
            'farms' => $farms,
        ]);
    }
}
