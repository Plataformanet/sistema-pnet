<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct()
    {
    }

    public function dashboard()
    {
        return Inertia::render('tenant/Dashboard', [
            'message' => 'Bem-vindo ao dashboard do tenant!',
        ]);
    }
}
