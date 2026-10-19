<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexReportsRequest;
use App\Http\Requests\StoreReportRequest;
use App\Http\Resources\Api\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(IndexReportsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Report::class);

        $filters = $request->validated();
        $reports = Report::query()
            ->with('generatedBy:id,name')
            ->latest()
            ->latest('id')
            ->paginate($filters['per_page'] ?? 15);

        return ReportResource::collection($reports);
    }

    public function store(StoreReportRequest $request, ReportService $service): JsonResponse
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
        ])->load('generatedBy:id,name');

        return (new ReportResource($report))
            ->withSummaryData()
            ->response()
            ->setStatusCode(201);
    }

    public function show(Report $report): ReportResource
    {
        Gate::authorize('view', $report);

        return (new ReportResource($report->load('generatedBy:id,name')))
            ->withSummaryData();
    }
}
