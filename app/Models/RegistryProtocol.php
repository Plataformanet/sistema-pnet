<?php

namespace App\Models;

use App\Enums\RegistryProtocolPositionEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Protocolo de registro do imóvel no cartório. Comprador e vendedor são
 * gravados como texto: é o registro histórico do que foi protocolado, sem
 * sincronização posterior com a proposta.
 */
class RegistryProtocol extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'proposal_id',
        'protocol_number',
        'buyer_name',
        'seller_name',
        'entered_at',
        'exited_at',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entered_at' => 'date:Y-m-d',
            'exited_at' => 'date:Y-m-d',
            'position' => RegistryProtocolPositionEnum::class,
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('position', RegistryProtocolPositionEnum::closedValues());
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('position', RegistryProtocolPositionEnum::closedValues());
    }

    /**
     * Busca por número do protocolo, comprador, vendedor ou nº da proposta.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $digits = preg_replace('/\D/', '', $term);

        return $query->where(function (Builder $query) use ($term, $digits) {
            $query->where('protocol_number', 'like', '%'.$term.'%')
                ->orWhere('buyer_name', 'like', '%'.$term.'%')
                ->orWhere('seller_name', 'like', '%'.$term.'%');

            if ($digits !== '' && strlen($digits) <= 9) {
                $query->orWhere('proposal_id', (int) $digits);
            }
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }
}
