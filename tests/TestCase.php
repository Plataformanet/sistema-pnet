<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Migra o banco central (path default + path central) uma vez por suíte e
     * envolve cada teste numa transação na conexão central. As operações em
     * bancos de tenant rodam em outras conexões, por isso cada teste que criar
     * um tenant deve apagá-lo (o que dropa o banco físico do tenant).
     */
    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->artisan('migrate', ['--path' => 'database/migrations/central']);

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }
}
