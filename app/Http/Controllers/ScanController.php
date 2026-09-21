<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Support\Facades\Gate;

class ScanController extends Controller
{
    public static function create()
    {
        Gate::authorize('create', Scan::class);
    }
}
