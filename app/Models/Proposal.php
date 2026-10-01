<?php

namespace App\Models;

use App\Enums\AmortizationTable;
use App\Enums\CostItemType;
use App\Enums\PropertyCondition;
use App\Enums\ProposalStatus;
use App\Enums\RolesEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Proposta de financiamento/documentação imobiliária. Todos os valores
 * monetários estão em centavos.
 */
class Proposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'analyst_id',
        'bank_id',
        'contract_type_id',
        'amortization_table',
        'status',
        'property_condition',
        'has_other_property',
        'purchase_value',
        'down_payment_value',
        'financing_value',
        'expenses_value',
        'subsidy_value',
        'financed_value',
        'intended_installment_value',
        'fgts_value',
        'uses_fgts',
        'is_first_financing',
        'finance_documentation_fee',
        'documentation_fee_to_finance',
        'payment_term',
        'declares_income_tax',
        'declared_income',
        'expected_delivery_month',
        'expected_delivery_year',
        'cancellation_reason',
        'restriction_reason',
        'contract_notes',
        'purchase_value_notes',
        'down_payment_notes',
        'fgts_notes',
        'documentation_fee_notes',
        'documentation_financing_notes',
        'income_tax_notes',
        'general_notes',
        'particularities',
        'finished_at',
    ];

    protected $appends = ['number'];

    /**
     * Cache de `isVisibleTo` por id de usuário.
     *
     * @var array<int|string, bool>
     */
    protected array $visibilityByUser = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amortization_table' => AmortizationTable::class,
            'status' => ProposalStatus::class,
            'property_condition' => PropertyCondition::class,
            'has_other_property' => 'boolean',
            'purchase_value' => 'integer',
            'down_payment_value' => 'integer',
            'financing_value' => 'integer',
            'expenses_value' => 'integer',
            'subsidy_value' => 'integer',
            'financed_value' => 'integer',
            'intended_installment_value' => 'integer',
            'fgts_value' => 'integer',
            'uses_fgts' => 'boolean',
            'is_first_financing' => 'boolean',
            'finance_documentation_fee' => 'boolean',
            'documentation_fee_to_finance' => 'integer',
            'payment_term' => 'integer',
            'declares_income_tax' => 'boolean',
            'declared_income' => 'integer',
            'expected_delivery_month' => 'integer',
            'expected_delivery_year' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Número exibido da proposta: id com 5 dígitos e zeros à esquerda.
     */
    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Restringe às propostas que o usuário pode ver. Parceiro, vendedor do
     * imóvel e cliente só enxergam as propostas a que estão vinculados; quem
     * tem qualquer outro cargo enxerga todas.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->hasOnlyProposalRestrictedRoles()) {
            return $query;
        }

        $roles = $user->getRoleNames();

        return $query->where(function (Builder $query) use ($roles, $user) {
            if ($roles->contains(RolesEnum::PARTNER->label())) {
                $query->orWhere('creator_id', $user->id)
                    ->orWhereHas('partners', fn (Builder $query) => $query->where('users.id', $user->id));
            }

            if ($roles->contains(RolesEnum::PROPERTY_SELLER->label())) {
                $query->orWhereHas('sellers', fn (Builder $query) => $query->where('sellers.user_id', $user->id));
            }

            if ($roles->contains(RolesEnum::CLIENT->label())) {
                $query->orWhereHas('applicants', fn (Builder $query) => $query->where('applicants.user_id', $user->id));
            }
        });
    }

    /**
     * O resultado é guardado por usuário nesta instância: uma mesma tela
     * consulta várias abilities da policy, e todas dependem desta checagem.
     */
    public function isVisibleTo(User $user): bool
    {
        return $this->visibilityByUser[$user->getKey()] ??= static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    /**
     * Propostas que ainda não têm emolumento vinculado (seleção do "Vincular à proposta").
     */
    public function scopeWithoutFeeEstimate(Builder $query): Builder
    {
        return $query->whereDoesntHave('feeEstimate');
    }

    public function scopeWithStatus(Builder $query, ProposalStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Soma das linhas de emolumento e taxa extra, em centavos.
     */
    public function feesTotal(): int
    {
        return (int) $this->costItems()
            ->whereIn('type', array_map(fn (CostItemType $type) => $type->value, CostItemType::fees()))
            ->sum('amount');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class)->withTrashed();
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class)->withTrashed();
    }

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'proposal_partner')->withTimestamps();
    }

    public function applicants(): BelongsToMany
    {
        return $this->belongsToMany(Applicant::class)->withTimestamps();
    }

    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(Seller::class)->withTimestamps();
    }

    public function billableServices(): BelongsToMany
    {
        return $this->belongsToMany(BillableService::class, 'proposal_billable_service')->withPivot('amount')->withTrashed();
    }

    public function feeEstimate(): HasOne
    {
        return $this->hasOne(FeeEstimate::class);
    }

    public function property(): HasOne
    {
        return $this->hasOne(ProposalProperty::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProposalStage::class)->orderBy('position');
    }

    public function currentStage(): HasOne
    {
        return $this->hasOne(ProposalStage::class)->where('is_current', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProposalDocument::class);
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProposalCostItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
