<?php

namespace App\Http\Controllers;

use App\Models\RiceVariety;
use App\Models\Scan;
use App\Policies\ScanPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ScanController extends Controller
{
    public static function create(){
        Gate::authorize('create', Scan::class);
    }
}
