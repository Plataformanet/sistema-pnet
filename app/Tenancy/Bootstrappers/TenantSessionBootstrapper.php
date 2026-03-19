<?php

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;
use Illuminate\Support\Facades\Config;

class TenantSessionBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        // Prefixo único por tenant no Redis
        Config::set('database.redis.options.prefix', "tenant_{$tenant->id}:");

        // Recria o store de sessão com o novo prefixo
        app('session')->driver()->setId(app('session')->getId());
    }

    public function revert(): void
    {
        Config::set('database.redis.options.prefix', config('database.redis.default.prefix', ''));
    }
}
