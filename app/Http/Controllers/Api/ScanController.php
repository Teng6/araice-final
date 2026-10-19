<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexScansRequest;
use App\Http\Requests\StoreScanRequest;
use App\Models\FarmerProfile;
use App\Models\Scan;
use App\Models\Treatment;
use App\Services\CreateScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ScanController extends Controller
{
    public function index(IndexScansRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Scan::class);

        $user = $request->user();
        $filters = $request->validated();

        $staffFilters = array_intersect(
            array_keys($filters),
            ['farmer_id', 'municipality', 'disease_id', 'status'],
        );
        if ($staffFilters !== []) {
            Gate::authorize('filter', Scan::class);
        }

        $query = Scan::query()
            ->with('disease:id,name')
            ->latest();

        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;
            abort_unless($profileId !== null, 403);

            $query->where('farmer_id', $profileId);
        } else {
            $query->with('farmer:id,full_name,contact_number');
            $query
                ->when(
                    $filters['farmer_id'] ?? null,
                    fn ($query, int $farmerId) => $query->where('farmer_id', $farmerId),
                )
                ->when(
                    $filters['municipality'] ?? null,
                    fn ($query, string $municipality) => $query->whereHas(
                        'farmer',
                        fn ($farmerQuery) => $farmerQuery->where('municipality', $municipality),
                    ),
                )
                ->when(
                    $filters['disease_id'] ?? null,
                    fn ($query, int $diseaseId) => $query->where('disease_id', $diseaseId),
                )
                ->when(
                    $filters['status'] ?? null,
                    fn ($query, string $status) => $query->where('status', $status),
                );
        }

        $scans = $query->paginate($filters['per_page'] ?? 15)->through(
            fn (Scan $scan): array => $this->scanData(
                $scan,
                $user->role !== UserRole::Farmer,
                false,
                false,
            ),
        );

        return response()->json($scans);
    }

    public function store(StoreScanRequest $request, CreateScan $createScan): JsonResponse
    {
        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $validated = $request->validated();
        $farmer = $user->role === UserRole::Farmer
            ? $user->farmerProfile
            : FarmerProfile::findOrFail((int) $validated['farmer_id']);

        abort_unless($farmer !== null, 403);

        $scan = $createScan->create(
            $user,
            $farmer,
            $request->file('image'),
            isset($validated['gps_lat']) ? (float) $validated['gps_lat'] : null,
            isset($validated['gps_long']) ? (float) $validated['gps_long'] : null,
        );

        return response()->json($this->scanData($scan, false, false, false), 201);
    }

    public function show(Scan $scan): JsonResponse
    {
        Gate::authorize('view', $scan);

        $scan->load([
            'disease.treatments' => fn ($query) => $query->orderBy('title'),
            'outbreak',
        ]);

        return response()->json($this->scanData($scan, false, true, true));
    }

    /**
     * @return array<string, mixed>
     */
    private function scanData(
        Scan $scan,
        bool $includeFarmer,
        bool $includeDiseaseDetails,
        bool $includeOutbreak,
    ): array {
        $data = [
            'id' => $scan->id,
            'created_at' => $scan->created_at,
            'image_url' => url('/storage/'.ltrim($scan->image_url, '/')),
            'top_3' => $this->topPredictions($scan),
            'status' => $scan->status->value,
            'disease' => $scan->disease === null ? null : [
                'id' => $scan->disease->id,
                'name' => $scan->disease->name,
                ...($includeDiseaseDetails ? [
                    'description' => $scan->disease->description,
                    'causes' => $scan->disease->causes,
                    'symptoms' => $scan->disease->symptoms,
                    'prevention_tips' => $scan->disease->prevention_tips,
                    'treatments' => $scan->disease->treatments
                        ->map(fn (Treatment $treatment): array => [
                            'id' => $treatment->id,
                            'title' => $treatment->title,
                            'description' => $treatment->description,
                            'type' => $treatment->type->value,
                        ])
                        ->values()
                        ->all(),
                ] : []),
            ],
            'confidence' => $scan->confidence_score === null
                ? null
                : (float) $scan->confidence_score,
            'gps_lat' => (float) $scan->gps_lat,
            'gps_lng' => (float) $scan->gps_long,
            'outbreak_id' => $scan->outbreak_id,
        ];

        if ($includeFarmer) {
            $data['farmer'] = [
                'id' => $scan->farmer->id,
                'name' => $scan->farmer->full_name,
                'contact_number' => $scan->farmer->contact_number,
            ];
        }

        if ($includeOutbreak) {
            $data['outbreak'] = $scan->outbreak === null ? null : [
                'id' => $scan->outbreak->id,
                'municipality' => $scan->outbreak->municipality->value,
                'status' => $scan->outbreak->status->value,
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array{label: string, confidence: float|int}>
     */
    private function topPredictions(Scan $scan): array
    {
        $raw = $scan->raw_predictions ?? [];
        $predictions = data_get($raw, 'data.top_3')
            ?? data_get($raw, 'data.top_3_predictions')
            ?? data_get($raw, 'data.predictions')
            ?? data_get($raw, 'data.top_predictions')
            ?? data_get($raw, 'top_3')
            ?? data_get($raw, 'top_3_predictions')
            ?? data_get($raw, 'predictions');

        if (! is_array($predictions)) {
            $predictions = array_is_list($raw) ? $raw : null;
        }

        if (! is_array($predictions) || ! array_is_list($predictions)) {
            $prediction = data_get($raw, 'data.prediction')
                ?? data_get($raw, 'prediction');
            $predictions = is_array($prediction) ? [$prediction] : [];
        }

        return collect($predictions)
            ->filter(fn ($prediction): bool => is_array($prediction))
            ->map(function (array $prediction): ?array {
                $label = $prediction['label'] ?? $prediction['class_name'] ?? null;
                $confidence = $prediction['confidence'] ?? null;

                if (! is_string($label) || $label === '' || ! is_numeric($confidence)) {
                    return null;
                }

                return [
                    'label' => $label,
                    'confidence' => is_int($confidence) || is_float($confidence)
                        ? $confidence
                        : (float) $confidence,
                ];
            })
            ->filter()
            ->sortByDesc('confidence')
            ->take(3)
            ->values()
            ->all();
    }
}
