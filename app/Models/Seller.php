<?php

namespace App\Models;

use App\Enums\MaritalStatus;
use App\Enums\PersonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vendedor do imóvel (PF ou PJ). Nome/razão social, documento e contatos ficam
 * no `Contact`; o tipo de pessoa é o `contacts.type`.
 */
class Seller extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_id',
        'user_id',
        'creator_id',
        'marital_status',
        'profession',
        'declared_income',
        'declares_income_tax',
        'income_tax_notes',
        'by_power_of_attorney',
    ];

    /**
     * Renda em centavos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marital_status' => MaritalStatus::class,
            'declared_income' => 'integer',
            'declares_income_tax' => 'boolean',
            'by_power_of_attorney' => 'boolean',
        ];
    }

    public function personType(): PersonType
    {
        return PersonType::tryFrom((string) $this->contact?->type) ?? PersonType::INDIVIDUAL;
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
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
