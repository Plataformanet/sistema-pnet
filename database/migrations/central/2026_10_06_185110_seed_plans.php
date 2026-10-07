<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registra os planos comerciais e os módulos incluídos no plano básico.
 * Substitui o PlanSeeder e o PlanModuleSeeder; depende dos módulos de
 * seed_core_modules. Idempotente: só cria os planos que faltam, e só vincula
 * módulos aos planos criados aqui, para não reativar um módulo que tenha sido
 * retirado de um plano já existente.
 */
return new class extends Migration
{
    /**
     * Preços em centavos.
     *
     * @var array<int, array{name: string, slug: string, price: int}>
     */
    private array $plans = [
        ['name' => 'Plano Básico', 'slug' => 'basic', 'price' => 2999],
        ['name' => 'Plano Padrão', 'slug' => 'standard', 'price' => 5999],
        ['name' => 'Plano Premium', 'slug' => 'premium', 'price' => 9999],
    ];

    /**
     * Planos que nascem com todos os módulos core incluídos, sem custo adicional.
     *
     * @var array<int, string>
     */
    private array $plansWithCoreModules = ['basic'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $existing = DB::table('plans')->whereIn('slug', array_column($this->plans, 'slug'))->pluck('slug')->all();

        $now = now();

        $missing = collect($this->plans)
            ->reject(fn (array $plan) => in_array($plan['slug'], $existing, true))
            ->values();

        if ($missing->isEmpty()) {
            return;
        }

        DB::table('plans')->insert(
            $missing->map(fn (array $plan) => [...$plan, 'created_at' => $now, 'updated_at' => $now])->all()
        );

        $newPlanIds = DB::table('plans')
            ->whereIn('slug', $missing->pluck('slug')->intersect($this->plansWithCoreModules)->all())
            ->pluck('id');

        $coreModuleIds = DB::table('modules')->where('is_core', true)->pluck('id');

        $rows = $newPlanIds
            ->crossJoin($coreModuleIds)
            ->map(fn (array $pair) => [
                'plan_id' => $pair[0],
                'module_id' => $pair[1],
                'is_included' => true,
                'additional_price' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table('plan_modules')->insert($rows);
        }
    }

    /**
     * Não remove os planos: tenants (`plan_id` sem FK) e assinaturas apontam
     * para eles, e apagá-los deixaria referências órfãs.
     */
    public function down(): void
    {
        //
    }
};
