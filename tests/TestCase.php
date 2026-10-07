<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TenantRegistry;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Uma vez por suíte (tudo committed, fora de transação): migra o banco
     * central (path default + central), o que já cria os dados de referência
     * centrais (módulos, permissões, planos), e cria/migra o banco do tenant
     * compartilhado. Em seguida cada teste roda dentro de uma transação na
     * conexão central; testes que usam sharedTenant() abrem também uma transação
     * na conexão do tenant. Por isso os testes NÃO devem recriar os dados de
     * referência: eles já existem e persistem por toda a suíte.
     */
    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->artisan('migrate', ['--path' => 'database/migrations/central']);

            $this->app[Kernel::class]->setArtisan(null);

            TenantRegistry::migrate();

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
