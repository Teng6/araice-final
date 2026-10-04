<?php

namespace App\Http\Controllers;

use App\Enums\OutbreakStatusEnum;
use App\Enums\ScanStatusEnum;
use App\Enums\ScanTypeEnum;
use App\Models\Outbreak;
use App\Models\Scan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScanReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('review', Scan::class);

        $scans = Scan::query()
            ->with('disease:id,name')
            ->where('scan_type', ScanTypeEnum::Leaf)
            ->whereNull('farmer_id')
            ->whereNotNull('disease_id')
            ->where('status', ScanStatusEnum::Completed)
            ->whereNull('outbreak_id')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $outbreaks = Outbreak::query()
            ->where('status', OutbreakStatusEnum::Active)
            ->with('disease:id,name')
            ->latest()
            ->get(['id', 'municipality', 'disease_id'])
            ->map(fn (Outbreak $outbreak) => [
                'id' => $outbreak->id,
                'municipality' => $outbreak->municipality,
                'disease_id' => $outbreak->disease_id,
                'disease' => $outbreak->disease->name,
            ]);

        return Inertia::render('scans/unlinked', [
            'scans' => $scans,
            'outbreaks' => $outbreaks,
        ]);
    }

    public function store(Request $request, Scan $scan): RedirectResponse
    {
        Gate::authorize('review', $scan);

        $validated = $request->validate([
            'outbreak_id' => [
                'required',
                'integer',
                Rule::exists('outbreaks', 'id')->where('status', OutbreakStatusEnum::Active->value),
            ],
        ]);

        DB::transaction(function () use ($scan, $validated): void {
            $currentScan = Scan::query()->lockForUpdate()->findOrFail($scan->id);

            if (
                $currentScan->farmer_id !== null
                || $currentScan->outbreak_id !== null
                || $currentScan->scan_type !== ScanTypeEnum::Leaf
                || $currentScan->status !== ScanStatusEnum::Completed
                || $currentScan->disease_id === null
            ) {
                throw ValidationException::withMessages([
                    'outbreak_id' => 'This scan is no longer eligible to be linked.',
                ]);
            }

            $outbreak = Outbreak::query()
                ->whereKey($validated['outbreak_id'])
                ->where('status', OutbreakStatusEnum::Active)
                ->lockForUpdate()
                ->first();

            if ($outbreak === null) {
                throw ValidationException::withMessages([
                    'outbreak_id' => 'The selected outbreak is no longer active.',
                ]);
            }

            if ($outbreak->disease_id !== $currentScan->disease_id) {
                throw ValidationException::withMessages([
                    'outbreak_id' => 'The selected outbreak must match the scan disease.',
                ]);
            }

            $currentScan->update(['outbreak_id' => $outbreak->id]);
        });

        return back();
    }
}
