<?php

namespace App\Models;

use App\Enums\ItbiModule;
use App\Enums\SupportedState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Município com cadastro de ITBI. Usa exatamente um módulo de cálculo e é
 * casado com a API de emolumentos pelo código IBGE (nunca pelo nome).
 */
class ItbiMunicipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'state',
        'ibge_code',
        'module',
        'full_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => SupportedState::class,
            'ibge_code' => 'integer',
            'module' => ItbiModule::class,
            'full_rate' => 'decimal:4',
        ];
    }

    public function rate(): HasOne
    {
        return $this->hasOne(ItbiRate::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(ItbiBracket::class)->orderBy('min_value');
    }
}
