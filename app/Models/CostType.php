<?php

namespace App\Models;

use App\Enums\ReceiptType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'requires_notary',
        'receipt_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_notary' => 'boolean',
            'receipt_type' => ReceiptType::class,
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Substitui os ids fixos 3 (Assessoria) e 5 (Motoboy) do legado: quem decide
     * se a cobrança gera recibo é o atributo do catálogo.
     */
    public function generatesReceipt(): bool
    {
        return $this->receipt_type !== null;
    }

    public function accountsPayable(): HasMany
    {
        return $this->hasMany(AccountPayable::class, 'cost_id');
    }

    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountReceivable::class, 'cost_id');
    }

    public function costItems(): HasMany
    {
        return $this->hasMany(ProposalCostItem::class);
    }
}
