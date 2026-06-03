<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct() {}

    public function dashboard()
    {
        return Inertia::render('tenant/Dashboard');
    }
}
