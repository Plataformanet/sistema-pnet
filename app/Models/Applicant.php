<?php

namespace App\Models;

use App\Enums\MaritalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Proponente (comprador) de propostas. Nome, CPF, e-mail e telefone ficam no
 * `Contact`, como nos demais papéis do cadastro unificado.
 */
class Applicant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_id',
        'user_id',
        'birth_date',
        'marital_status',
        'profession',
        'family_income',
        'declared_income',
        'declares_income_tax',
        'income_tax_notes',
        'by_power_of_attorney',
    ];

    /**
     * Rendas em centavos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'marital_status' => MaritalStatus::class,
            'family_income' => 'integer',
            'declared_income' => 'integer',
            'declares_income_tax' => 'boolean',
            'by_power_of_attorney' => 'boolean',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function proposals(): BelongsToMany
    {
        return $this->belongsToMany(Proposal::class)->withTimestamps();
    }

    public function bankAccount(): MorphOne
    {
        return $this->morphOne(HolderBankAccount::class, 'accountable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(ProposalDocument::class, 'documentable');
    }
}
