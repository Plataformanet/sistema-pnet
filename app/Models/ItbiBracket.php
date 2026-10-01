<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Faixa de valor do imóvel (módulo 02). Limites inclusivos e em centavos.
 */
class ItbiBracket extends Model
{
    protected $fillable = [
        'min_value',
        'max_value',
        'rate',
        'discount_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_value' => 'integer',
            'max_value' => 'integer',
            'rate' => 'decimal:4',
            'discount_amount' => 'integer',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(ItbiMunicipality::class, 'itbi_municipality_id');
    }

    public function contains(int $value): bool
    {
        return $this->min_value <= $value && $value <= $this->max_value;
    }
}
