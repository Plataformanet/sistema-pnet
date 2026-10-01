<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /**
     * Etapas ativas da timeline. Os flags de exibição substituem o comportamento
     * fixo do legado (dados do imóvel na etapa 2, protocolo de registro na 7).
     * Prazos e obrigatoriedades são ajustados pelo administrador no cadastro.
     */
    public function run(): void
    {
        if (Stage::withTrashed()->exists()) {
            return;
        }

        $stages = [
            ['order' => 1, 'name' => 'Cadastro/Aprovação'],
            ['order' => 2, 'name' => 'Solicitação de Engenharia', 'shows_property_data' => true],
            ['order' => 3, 'name' => 'Saque de FGTS', 'has_date' => true, 'date_required' => true],
            ['order' => 4, 'name' => 'Entrevista'],
            ['order' => 5, 'name' => 'Conformidade'],
            ['order' => 6, 'name' => 'Assinatura de Escritura'],
            ['order' => 7, 'name' => 'Envio para Registro', 'shows_registry_protocol' => true],
            ['order' => 8, 'name' => 'Finalização'],
        ];

        foreach ($stages as $stage) {
            Stage::create($stage);
        }
    }
}
