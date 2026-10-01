<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Alíquotas (percentuais, ex.: 3 = 3%) do município; `financed_cap_amount` em centavos.
 */
class ItbiRate extends Model
{
    protected $fillable = [
        'own_funds_rate',
        'financed_rate',
        'financed_cap_amount',
        'first_property_financed_rate',
        'other_property_financed_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'own_funds_rate' => 'decimal:4',
            'financed_rate' => 'decimal:4',
            'financed_cap_amount' => 'integer',
            'first_property_financed_rate' => 'decimal:4',
            'other_property_financed_rate' => 'decimal:4',
        ];
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(ItbiMunicipality::class, 'itbi_municipality_id');
    }
}
