<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Passa `plans.price` de reais para centavos, como os demais valores
 * monetários. A coluna já era `integer`, então os preços com centavos do
 * antigo PlanSeeder (29.99, 59.99, 99.99) foram gravados arredondados
 * (30, 60, 100); para esses planos o valor exato é restaurado. Os demais são
 * só multiplicados por 100.
 *
 * Roda antes de seed_plans, que já grava em centavos: numa instalação nova a
 * tabela ainda está vazia aqui.
 */
return new class extends Migration
{
    /**
     * Preço arredondado gravado pelo antigo PlanSeeder → preço correto em centavos.
     *
     * @var array<string, array{rounded: int, cents: int}>
     */
    private array $seededPlans = [
        'basic' => ['rounded' => 30, 'cents' => 2999],
        'standard' => ['rounded' => 60, 'cents' => 5999],
        'premium' => ['rounded' => 100, 'cents' => 9999],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')->orderBy('id')->each(function (object $plan) {
            $seeded = $this->seededPlans[$plan->slug] ?? null;

            $cents = $seeded !== null && (int) $plan->price === $seeded['rounded']
                ? $seeded['cents']
                : (int) $plan->price * 100;

            DB::table('plans')->where('id', $plan->id)->update(['price' => $cents]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('plans')->update(['price' => DB::raw('ROUND(price / 100)')]);
    }
};
