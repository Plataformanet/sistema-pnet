<?php

namespace App\Http\Requests;

use App\Enums\DocumentOwner;
use App\Enums\DocumentType;
use App\Models\Seller;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProposalDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'owner' => ['required', Rule::enum(DocumentOwner::class)->only([
                DocumentOwner::BUYER,
                DocumentOwner::SELLER,
                DocumentOwner::PROPERTY,
                DocumentOwner::GENERAL,
            ])],
            'person_id' => ['nullable', 'required_if:owner,'.DocumentOwner::BUYER->value.','.DocumentOwner::SELLER->value, 'integer'],
            'type' => ['nullable', Rule::enum(DocumentType::class)],
            'title' => ['nullable', 'string', 'max:191'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'mimes:pdf,doc,docx,jpg,jpeg,png,bmp,zip', 'max:'.config('bucket.proposal_document_max_kb')],
        ];
    }

    /**
     * O tipo precisa ser aceito para o dono (no vendedor, conforme PF/PJ).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty() || blank($this->input('type'))) {
                    return;
                }

                $owner = DocumentOwner::from($this->input('owner'));
                $personType = $owner === DocumentOwner::SELLER
                    ? Seller::with('contact')->find($this->input('person_id'))?->personType()
                    : null;

                if (! in_array(DocumentType::from($this->input('type')), DocumentType::forOwner($owner, $personType), true)) {
                    $validator->errors()->add('type', 'Este tipo de documento não se aplica ao dono selecionado.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'owner.required' => 'Selecione a quem o documento pertence.',
            'owner.enum' => 'O dono do documento é inválido.',
            'person_id.required_if' => 'Selecione a pessoa dona do documento.',
            'type.enum' => 'O tipo de documento é inválido.',
            'files.required' => 'Selecione ao menos um arquivo.',
            'files.max' => 'Envie no máximo 20 arquivos por vez.',
            'files.*.mimes' => 'Os arquivos devem ser PDF, DOC, DOCX, JPG, PNG, BMP ou ZIP.',
            'files.*.max' => 'Um dos arquivos excede o tamanho máximo permitido.',
        ];
    }
}
