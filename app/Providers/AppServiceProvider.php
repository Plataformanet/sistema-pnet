<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenancyServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureTenantRateLimiters();

        // Schema::defaultStringLength(191);
    }

    /**
     * Limitadores das rotas do tenant. O store do limitador é compartilhado
     * entre os tenants, e os ids de usuário se repetem de um banco para outro;
     * por isso a chave leva o domínio do tenant além do usuário.
     */
    private function configureTenantRateLimiters(): void
    {
        $byTenantUser = fn (Request $request): string => $request->getHost().'|'.($request->user()?->getAuthIdentifier() ?? $request->ip());

        RateLimiter::for('documents-lookup', fn (Request $request): Limit => Limit::perMinute(30)->by($byTenantUser($request)));
        RateLimiter::for('fee-calculator', fn (Request $request): Limit => Limit::perMinute(20)->by($byTenantUser($request)));
    }
}
