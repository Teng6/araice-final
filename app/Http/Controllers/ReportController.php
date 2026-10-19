<?php

namespace App\Http\Controllers;

use App\Enums\MunicipalityEnum;
use App\Http\Requests\StoreReportRequest;
use App\Models\Disease;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Report::class);

        return Inertia::render('reports/index', [
            'reports' => Report::with('generatedBy:id,name')->latest()->paginate(10),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Report::class);

        return Inertia::render('reports/create', [
            'municipalities' => array_column(MunicipalityEnum::cases(), 'value'),
            'diseases' => Disease::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreReportRequest $request, ReportService $service): RedirectResponse
    {
        Gate::authorize('create', Report::class);

        $data = $request->validated();

        $report = Report::create([
            'generated_by' => $request->user()->id,
            'municipality' => $data['municipality'],
            'range_start' => $data['range_start'],
            'range_end' => $data['range_end'],
            'summary_data' => $service->generate(
                $data['municipality'],
                $data['range_start'],
                $data['range_end'],
                $data['disease_id'] ?? null,
            ),
        ]);

        return to_route('reports.show', $report);
    }

    public function show(Report $report): Response
    {
        Gate::authorize('view', $report);

        return Inertia::render('reports/show', [
            'report' => $report->load('generatedBy:id,name'),
        ]);
    }
}
