<?php

namespace App\Models;

use App\Enums\CostItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Linha de custo da proposta, compartilhada com a calculadora: emolumentos,
 * taxas extras, serviços e ITBI vêm do cálculo; "Manual" é o lançamento feito
 * na aba Pagamentos e Taxas. `amount` em centavos.
 */
class ProposalCostItem extends Model
{
    protected $fillable = [
        'type',
        'description',
        'extra_fee_description',
        'service_description',
        'generates_receipt',
        'amount',
        'cost_type_id',
        'notary_id',
        'date',
        'notes',
        'bill_path',
        'proof_path',
    ];

    protected $hidden = [
        'bill_path',
        'proof_path',
    ];

    protected $appends = [
        'has_bill',
        'has_proof',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CostItemType::class,
            'generates_receipt' => 'boolean',
            'amount' => 'integer',
            'date' => 'date:Y-m-d',
        ];
    }

    public function getHasBillAttribute(): bool
    {
        return $this->bill_path !== null;
    }

    public function getHasProofAttribute(): bool
    {
        return $this->proof_path !== null;
    }

    /**
     * Linha que pode gerar recibo: lançamento manual cujo tipo de custo gera
     * recibo (assessoria/motoboy) ou serviço marcado para gerar recibo.
     */
    public function canGenerateReceipt(): bool
    {
        if ($this->type === CostItemType::MANUAL) {
            return (bool) $this->costType?->generatesReceipt();
        }

        return $this->generates_receipt;
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class)->withTrashed();
    }

    public function notary(): BelongsTo
    {
        return $this->belongsTo(Notary::class)->withTrashed();
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
