<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DiseaseResource;
use App\Models\Disease;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DiseaseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Disease::class);

        return DiseaseResource::collection(
            Disease::query()
                ->select(['id', 'name', 'description', 'image_path'])
                ->orderBy('name')
                ->get(),
        );
    }

    public function show(Disease $disease): DiseaseResource
    {
        Gate::authorize('view', $disease);

        $disease->load(['treatments' => fn ($query) => $query->orderBy('title')]);

        return new DiseaseResource($disease);
    }
}
