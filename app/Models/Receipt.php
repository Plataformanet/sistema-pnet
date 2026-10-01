<?php

namespace App\Models;

use App\Enums\ReceiptType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Recibo da proposta. `notary_name` é um retrato do nome do cartório na
 * emissão: alterar o cadastro do cartório não muda recibos emitidos.
 */
class Receipt extends Model
{
    protected $fillable = [
        'proposal_cost_item_id',
        'type',
        'name',
        'document',
        'registration_number',
        'notary_name',
        'total_spent',
        'amount_deposited',
        'date',
    ];

    /**
     * Valores em centavos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReceiptType::class,
            'total_spent' => 'integer',
            'amount_deposited' => 'integer',
            'date' => 'date:Y-m-d',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function costItem(): BelongsTo
    {
        return $this->belongsTo(ProposalCostItem::class, 'proposal_cost_item_id');
    }
}
