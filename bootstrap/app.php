<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\HandleInertiaRequests;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
        $centralDomains = config('tenancy.central_domains');

        foreach ($centralDomains as $domain) {
            Route::middleware('web')
                ->domain($domain)
                ->group(base_path('routes/web.php'));
        }

        Route::middleware('web')->group(base_path('routes/tenant.php'));
    })->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (
            TenantCouldNotBeIdentifiedOnDomainException $e,
            $request
        ) {
            $centralDomain = "http://localhost:8005/cadastro";
            $invalidDomain = $request->getHost();

            return response()->view('errors.tenant-not-found', [
                'domain'        => $invalidDomain,
                'central_url'   => $centralDomain,
            ], 404);
        });
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
    ]);
    })->create();
