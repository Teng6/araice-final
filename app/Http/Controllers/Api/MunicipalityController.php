<?php

namespace App\Http\Controllers\Api;

use App\Enums\MunicipalityEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class MunicipalityController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => array_map(
                fn (MunicipalityEnum $municipality): array => [
                    'slug' => $municipality->value,
                    'label' => Str::headline($municipality->value),
                ],
                MunicipalityEnum::cases(),
            ),
        ]);
    }
}
