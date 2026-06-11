<?php

namespace Tests\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Suporta dois modos de tenant nos testes:
 *
 * 1. Tenant compartilhado (sharedTenant): criado e migrado UMA vez por suíte;
 *    cada teste é isolado por uma transação na conexão do tenant (rollback no
 *    fim). Indicado para testar recursos do tenant (rápido).
 *
 * 2. Tenants reais (createTenant): cria um tenant novo por chamada — incluindo
 *    a criação/migração do banco dele. Indicado para testar o provisionamento
 *    em si (lento por natureza). São removidos no afterEach.
 */
class TenantRegistry
{
    protected const SHARED_ID = 'test';

    protected static ?Tenant $shared = null;

    protected static ?string $connection = null;

    /** @var array<int, Tenant> */
    protected static array $created = [];

    /**
     * Cria e migra o banco do tenant compartilhado. Chamado uma única vez por
     * suíte, fora de qualquer transação (em TestCase::refreshTestDatabase).
     */
    public static function migrate(): void
    {
        $central = config('tenancy.database.central_connection');
        $database = config('tenancy.database.prefix').self::SHARED_ID.config('tenancy.database.suffix');

        // Remove resíduo de execuções anteriores e recria o banco do zero.
        DB::connection($central)->statement("DROP DATABASE IF EXISTS `{$database}`");

        static::$shared = Tenant::create([
            'id' => self::SHARED_ID,
            'name' => 'Shared test tenant',
            'is_active' => true,
        ]);
    }

    /**
     * Inicializa a tenancy no tenant compartilhado e abre uma transação na
     * conexão dele, isolando o estado do teste atual.
     */
    public static function beginShared(): Tenant
    {
        tenancy()->initialize(static::$shared);

        static::$connection = config('database.default');
        DB::connection(static::$connection)->beginTransaction();

        return static::$shared;
    }

    /**
     * Registra um tenant real para remoção no fim do teste.
     */
    public static function add(Tenant $tenant): void
    {
        static::$created[] = $tenant;
    }

    /**
     * Limpeza de fim de teste: reverte a transação do tenant compartilhado (se
     * houver) e remove os tenants reais criados durante o teste.
     */
    public static function cleanup(): void
    {
        if (static::$connection !== null) {
            DB::connection(static::$connection)->rollBack();
            static::$connection = null;
        }

        tenancy()->end();

        foreach (static::$created as $tenant) {
            $tenant->delete();
        }

        static::$created = [];
    }
}
