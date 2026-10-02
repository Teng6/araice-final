<?php

namespace App\Http\Controllers;

use App\Enums\OutbreakStatusEnum;
use App\Models\Outbreak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OutbreakController extends Controller
{
    public function close(Request $request, Outbreak $outbreak): RedirectResponse
    {
        Gate::authorize('close', $outbreak);

        abort_if($outbreak->status === OutbreakStatusEnum::Resolved, 409, 'Outbreak is already resolved.');

        $outbreak->update([
            'status' => OutbreakStatusEnum::Resolved,
            'closed_by' => $request->user()->id,
            'closed_at' => now(),
        ]);

        return back();
    }
}
