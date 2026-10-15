<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Seleção do ZIP: ids marcados na tela ou "todos os resultados do filtro"
 * (`all_matching`), que reaplica os mesmos filtros do relatório.
 */
class DownloadFinishedProposalsRequest extends FinishedProposalsReportRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxFiles = (int) config('reports.zip_max_files');

        return [
            ...parent::rules(),
            'all_matching' => ['nullable', 'boolean'],
            'proposal_ids' => ['exclude_if:all_matching,true', 'required_unless:all_matching,true', 'array', 'min:1', 'max:'.$maxFiles],
            'proposal_ids.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        $maxFiles = (int) config('reports.zip_max_files');

        return [
            ...parent::messages(),
            'all_matching.boolean' => 'A seleção informada é inválida.',
            'proposal_ids.required_unless' => 'Selecione ao menos uma proposta.',
            'proposal_ids.array' => 'A seleção informada é inválida.',
            'proposal_ids.min' => 'Selecione ao menos uma proposta.',
            'proposal_ids.max' => "Selecione no máximo {$maxFiles} documentos.",
            'proposal_ids.*.integer' => 'Proposta selecionada inválida.',
            'proposal_ids.*.distinct' => 'Há propostas repetidas na seleção.',
        ];
    }
}
