<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantClientController extends Controller
{
    public function clientList()
    {
        return Inertia::render('tenant/registrations/clients/List');
    }
}
