<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Padroniza todo valor monetário do tenant em `unsignedBigInteger` (centavos),
 * repetindo os atributos originais de cada coluna (D-06, V-10).
 *
 * Ficam de fora os saldos de `bank_accounts`, que continuam `bigInteger` com
 * sinal porque podem ficar negativos. Se alguma coluna tiver valor negativo, a
 * migration falha listando onde, para a limpeza manual (V-02).
 */
return new class extends Migration
{
    /**
     * Colunas monetárias que passam a ser sem sinal: tabela → coluna → anulável.
     *
     * @var array<string, array<string, bool>>
     */
    private array $columns = [
        'products' => ['cost_value' => false, 'sell_value' => false],
        'services' => ['cost_value' => false, 'sell_value' => false, 'fees' => false],
        'employees' => ['salary' => false],
        'account_payables' => ['total' => true],
        'account_receivables' => ['total' => true],
        'installments' => ['value' => false],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureNoNegativeValues();

        foreach ($this->columns as $tableName => $tableColumns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableColumns) {
                foreach ($tableColumns as $column => $nullable) {
                    $definition = $table->unsignedBigInteger($column);

                    if ($nullable) {
                        $definition->nullable();
                    }

                    $definition->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Volta ao tipo anterior de cada coluna: `integer` no catálogo e no salário,
     * `bigInteger` com sinal no financeiro.
     */
    public function down(): void
    {
        $signedBigInteger = ['account_payables', 'account_receivables', 'installments'];

        foreach ($this->columns as $tableName => $tableColumns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $tableColumns, $signedBigInteger) {
                foreach ($tableColumns as $column => $nullable) {
                    $definition = in_array($tableName, $signedBigInteger, true)
                        ? $table->bigInteger($column)
                        : $table->integer($column);

                    if ($nullable) {
                        $definition->nullable();
                    }

                    $definition->change();
                }
            });
        }
    }

    /**
     * Interrompe a migration quando algum valor monetário é negativo.
     */
    private function ensureNoNegativeValues(): void
    {
        $found = [];

        foreach ($this->columns as $tableName => $tableColumns) {
            foreach (array_keys($tableColumns) as $column) {
                $count = DB::table($tableName)->where($column, '<', 0)->count();

                if ($count > 0) {
                    $found[] = "{$tableName}.{$column} ({$count} registro(s))";
                }
            }
        }

        if ($found !== []) {
            throw new RuntimeException(
                'Valores monetários negativos impedem a conversão para unsigned: '.implode(', ', $found).'.'
            );
        }
    }
};
