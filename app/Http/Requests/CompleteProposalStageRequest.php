<?php

namespace App\Http\Requests;

use App\Models\ProposalStage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * As obrigatoriedades vêm do catálogo da etapa e são lidas ao vivo: alterar a
 * etapa no cadastro vale também para as timelines em andamento.
 */
class CompleteProposalStageRequest extends FormRequest
{
    private ?ProposalStage $proposalStage = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $proposalStage = $this->proposalStage();
        $stage = $proposalStage?->stage;
        $hasDocument = $proposalStage?->proposal_document_id !== null;

        return [
            'date' => [$stage?->date_required ? 'required' : 'nullable', 'date'],
            'notes' => [$stage?->notes_required ? 'required' : 'nullable', 'string', 'max:5000'],
            'title' => [$stage?->title_required && ! $hasDocument ? 'required' : 'nullable', 'string', 'max:191'],
            'file' => [
                $stage?->upload_required && ! $hasDocument ? 'required' : 'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,bmp,zip',
                'max:'.config('bucket.proposal_document_max_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'A data é obrigatória nesta etapa.',
            'date.date' => 'Informe uma data válida.',
            'notes.required' => 'A observação é obrigatória nesta etapa.',
            'title.required' => 'O título do arquivo é obrigatório nesta etapa.',
            'file.required' => 'O envio de arquivo é obrigatório nesta etapa.',
            'file.mimes' => 'O arquivo deve ser PDF, DOC, DOCX, JPG, PNG, BMP ou ZIP.',
            'file.max' => 'O arquivo excede o tamanho máximo permitido.',
        ];
    }

    private function proposalStage(): ?ProposalStage
    {
        return $this->proposalStage ??= ProposalStage::with('stage')
            ->where('proposal_id', $this->route('id'))
            ->find($this->route('proposalStageId'));
    }
}
