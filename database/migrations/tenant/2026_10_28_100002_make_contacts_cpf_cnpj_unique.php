<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-CAD-01: o contato é único por documento. Normaliza `contacts.cpf_cnpj`
 * para só dígitos e troca o índice comum por um `unique`. O índice cobre
 * também os contatos com soft delete, por isso um contato excluído é
 * restaurado em vez de duplicado.
 *
 * Se depois de normalizar houver documentos repetidos, a migration falha
 * listando-os, sem alterar nada: a limpeza (ou a recriação do tenant de
 * homologação, V-02) é manual. Idempotente: roda de novo sem efeito.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $documents = DB::table('contacts')
            ->orderBy('id')
            ->get(['id', 'cpf_cnpj'])
            ->map(fn (object $contact): array => [
                'id' => $contact->id,
                'original' => $contact->cpf_cnpj,
                'digits' => (string) preg_replace('/\D/', '', (string) $contact->cpf_cnpj),
            ]);

        $duplicates = $documents->groupBy('digits')->filter(fn ($group): bool => $group->count() > 1);

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates
                ->map(fn ($group, string $digits): string => "'{$digits}' (contatos ".$group->pluck('id')->implode(', ').')')
                ->implode('; ');

            throw new RuntimeException("Documentos repetidos em contacts.cpf_cnpj impedem o índice unique: {$list}.");
        }

        DB::transaction(function () use ($documents) {
            foreach ($documents->where(fn (array $row): bool => $row['original'] !== $row['digits']) as $row) {
                DB::table('contacts')->where('id', $row['id'])->update(['cpf_cnpj' => $row['digits']]);
            }
        });

        if (Schema::hasIndex('contacts', 'contacts_cpf_cnpj_unique', 'unique')) {
            return;
        }

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasIndex('contacts', ['cpf_cnpj'])) {
                $table->dropIndex(['cpf_cnpj']);
            }

            $table->unique('cpf_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Os documentos continuam só com dígitos: a máscara original não é recuperável.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['cpf_cnpj']);
            $table->index('cpf_cnpj');
        });
    }
};
