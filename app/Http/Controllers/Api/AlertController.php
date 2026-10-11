<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request, AlertService $alerts): JsonResponse
    {
        $page = $alerts->forUser($request->user(), $request->boolean('unread'))
            ->paginate(15)
            ->through(fn (Alert $alert): array => $alerts->present($alert, $alert->pivot->read_at));

        return response()->json($page);
    }

    public function markRead(Request $request, int $alert, AlertService $alerts): JsonResponse
    {
        $model = $alerts->markRead($request->user(), $alert);

        return response()->json($alerts->present($model, $model->pivot->read_at));
    }
}
