<?php

namespace App\Models;

use App\Enums\CalculationType;
use App\Enums\SupportedState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Retrato imutável de um cálculo feito no servidor. Vincular à proposta e
 * gerar orçamento recebem só o UUID dele — o resultado nunca volta do
 * navegador. Expira e é removido pela limpeza agendada.
 */
class FeeCalculation extends Model
{
    use HasUuids, Prunable;

    public const LIFETIME_DAYS = 7;

    protected $fillable = [
        'user_id',
        'type',
        'state',
        'municipality_ibge_code',
        'municipality_name',
        'input',
        'api_result',
        'fees_total',
        'itbi_amount',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CalculationType::class,
            'state' => SupportedState::class,
            'municipality_ibge_code' => 'integer',
            'input' => 'array',
            'api_result' => 'array',
            'fees_total' => 'integer',
            'itbi_amount' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>=', now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
