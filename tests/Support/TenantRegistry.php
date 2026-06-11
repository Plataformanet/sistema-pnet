<?php

namespace Tests\Support;

use App\Models\Tenant;

/**
 * Rastreia os tenants criados durante um teste para que a suíte possa
 * dropar os bancos físicos no afterEach (a transação do RefreshDatabase só
 * reverte a conexão central, não os bancos de tenant).
 */
class TenantRegistry
{
    /** @var array<int, Tenant> */
    protected static array $tenants = [];

    public static function add(Tenant $tenant): void
    {
        static::$tenants[] = $tenant;
    }

    /**
     * Retorna os tenants registrados e limpa o registro.
     *
     * @return array<int, Tenant>
     */
    public static function flush(): array
    {
        $tenants = static::$tenants;
        static::$tenants = [];

        return $tenants;
    }
}
