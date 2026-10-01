<?php

namespace App\Models;

use App\Enums\BankAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Conta bancária de um proponente ou vendedor (não confundir com a conta
 * bancária do financeiro, `bank_accounts`). O banco é gravado pelo nome.
 */
class HolderBankAccount extends Model
{
    protected $fillable = [
        'bank_name',
        'account_type',
        'branch',
        'number',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'account_type' => BankAccountType::class,
        ];
    }

    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }
}
