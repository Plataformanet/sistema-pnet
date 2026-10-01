<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Unicidade de nome dos cadastros considerando os registros excluídos: o índice
 * único do banco continua valendo para eles, então em vez de duplicar o
 * cadastro o usuário é orientado a restaurar o registro existente.
 */
class UniqueCatalogName implements ValidationRule
{
    public function __construct(
        private string $table,
        private string $message,
        private int|string|null $ignoreId = null,
        private string $column = 'name',
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $record = DB::table($this->table)
            ->where($this->column, $value)
            ->when($this->ignoreId, fn ($query) => $query->where('id', '!=', $this->ignoreId))
            ->first(['id', 'deleted_at']);

        if ($record === null) {
            return;
        }

        $fail($record->deleted_at === null
            ? $this->message
            : 'Já existe um cadastro excluído com este nome — restaure-o.');
    }
}
