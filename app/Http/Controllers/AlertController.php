<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(Request $request): Response
    {
        $alerts = $request->user()
            ->alerts()
            ->with('outbreak.disease')
            ->latest('alerts.created_at')
            ->paginate(15)
            ->through(function ($alert) {
                /** @var Alert&object{pivot: Pivot&object{read_at: string|null}} $alert */
                return $this->present($alert);
            });

        return Inertia::render('alerts/index', ['alerts' => $alerts]);
    }

    public function show(Request $request, int $alert): Response
    {
        $user = $request->user();

        /** @var Alert&object{pivot: Pivot&object{read_at: string|null}} $model */
        $model = $user->alerts()->with('outbreak.disease')->findOrFail($alert);

        if ($model->pivot->read_at === null) {
            $user->alerts()->updateExistingPivot($model->id, ['read_at' => now()]);
        }

        return Inertia::render('alerts/show', ['alert' => $this->present($model)]);
    }

    /**
     * @param  Alert&object{pivot: Pivot&object{read_at: string|null}}  $alert
     * @return array<string, mixed>
     */
    private function present(Alert $alert): array
    {
        return [
            'id' => $alert->id,
            'message' => $alert->message,
            'severity' => $alert->severity,
            'disease' => $alert->outbreak->disease->name ?? null,
            'municipality' => $alert->outbreak->municipality,
            'created_at' => $alert->created_at,
            'read_at' => $alert->pivot->read_at,
        ];
    }
}
