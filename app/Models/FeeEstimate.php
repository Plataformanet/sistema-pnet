<?php

namespace App\Models;

use App\Enums\CalculationType;
use App\Enums\SupportedState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Resultado do cálculo "congelado" num orçamento ou numa proposta. A validade
 * só é obrigatória no orçamento.
 */
class FeeEstimate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quote_id',
        'proposal_id',
        'type',
        'state',
        'municipality_name',
        'valid_until',
        'api_result',
        'fees_total',
        'itbi_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CalculationType::class,
            'state' => SupportedState::class,
            'valid_until' => 'date:Y-m-d',
            'api_result' => 'array',
            'fees_total' => 'integer',
            'itbi_amount' => 'integer',
        ];
    }

    /**
     * Atributos do cálculo copiados para a estimativa.
     *
     * @return array<string, mixed>
     */
    public static function attributesFrom(FeeCalculation $calculation): array
    {
        return [
            'type' => $calculation->type,
            'state' => $calculation->state,
            'municipality_name' => $calculation->municipality_name,
            'api_result' => $calculation->api_result,
            'fees_total' => $calculation->fees_total,
            'itbi_amount' => $calculation->itbi_amount,
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }
}
