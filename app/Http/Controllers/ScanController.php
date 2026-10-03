<?php

namespace App\Http\Controllers;

use App\Enums\ScanStatusEnum;
use App\Enums\ScanTypeEnum;
use App\Enums\UserRole;
use App\Models\FarmerProfile;
use App\Models\RiceVariety;
use App\Models\Scan;
use App\Services\GrainClassifierService;
use App\Services\OutbreakDetectionService;
use App\Services\ViTService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ScanController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize('create', Scan::class);

        return Inertia::render('scans/create', [
            'varieties' => RiceVariety::orderBy('name')->get(['id', 'name']),
            'farmers' => $request->user()->role === UserRole::LguStaff
                ? FarmerProfile::orderBy('full_name')->get(['id', 'full_name', 'barangay'])
                : [],
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Scan::class);

        $user = $request->user();

        $query = Scan::with(['disease:id,name', 'variety:id,name'])->latest();

        if ($user->role === UserRole::Farmer) {
            $profileId = $user->farmerProfile?->id;
            abort_unless($profileId !== null, 403);

            $query->where('farmer_id', $profileId);
        } else {
            $query->with('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/index', [
            'scans' => $query->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request, ViTService $vit, GrainClassifierService $grain, OutbreakDetectionService $outbreaks): RedirectResponse
    {

        set_time_limit(120);

        Gate::authorize('create', Scan::class);

        $user = $request->user();
        $isLgu = $user->role === UserRole::LguStaff;

        $rules = [
            'image' => ['required', 'image', 'max:5120'],
            'scan_type' => ['required', Rule::enum(ScanTypeEnum::class)],
            'variety_id' => ['nullable', 'exists:rice_varieties,id'],
        ];

        if ($isLgu) {
            $rules['farmer_id'] = ['required', 'exists:farmer_profiles,id'];
            $rules['gps_lat'] = ['required', 'numeric'];
            $rules['gps_long'] = ['required', 'numeric'];
        }

        $validated = $request->validate($rules);

        if ($isLgu) {
            $farmerId = $validated['farmer_id'];
            $gpsLat = $validated['gps_lat'];
            $gpsLong = $validated['gps_long'];
        } else {
            $profile = $user->farmerProfile;
            $farmerId = $profile->id;
            $gpsLat = $profile->farm_lat;
            $gpsLong = $profile->farm_long;
        }

        $isLeaf = $validated['scan_type'] === ScanTypeEnum::Leaf->value;

        $path = $request->file('image')->store('scans', 'public');
        abort_unless(is_string($path), 500, 'Failed to store image.');

        $scan = Scan::create([
            'farmer_id' => $farmerId,
            'uploaded_by_id' => $user->id,
            'scan_type' => $validated['scan_type'],
            'variety_id' => $isLeaf ? ($validated['variety_id'] ?? null) : null,
            'image_url' => $path,
            'status' => ScanStatusEnum::Processing,
            'gps_lat' => $gpsLat,
            'gps_long' => $gpsLong,
        ]);

        try {
            $fullPath = Storage::disk('public')->path($path);
            $attributes = [];

            if ($isLeaf) {
                $result = $vit->predict($fullPath);
                $attributes['disease_id'] = $result['disease']?->id;
            } else {
                $result = $grain->classify($fullPath);
                $attributes['variety_id'] = $result['variety']?->id;
            }

            $scan->fill([
                ...$attributes,
                'confidence_score' => $result['confidence'],
                'raw_predictions' => $result['raw'],
                'status' => ScanStatusEnum::Completed,
            ])->save();
        } catch (Throwable $e) {
            report($e);
            $scan->update(['status' => ScanStatusEnum::Failed]);
        }

        if ($scan->status === ScanStatusEnum::Completed) {
            try {
                $outbreaks->handle($scan);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return to_route('scans.show', $scan);
    }

    public function show(Request $request, Scan $scan): Response
    {
        Gate::authorize('view', $scan);

        $scan->load(['disease', 'variety']);

        if ($request->user()->role !== UserRole::Farmer) {
            $scan->load('farmer:id,full_name,contact_number');
        }

        return Inertia::render('scans/show', ['scan' => $scan]);
    }
}
