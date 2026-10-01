<?php

namespace App\Models;

use App\Enums\MaritalStatus;
use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Orçamento a partir de um cálculo. Status derivado: convertido quando a
 * estimativa já está vinculada a uma proposta; expirado quando passou da
 * validade; senão em aberto.
 */
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'name',
        'cpf',
        'email',
        'phone',
        'profession',
        'marital_status',
        'bank_id',
    ];

    protected $appends = ['number'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'marital_status' => MaritalStatus::class,
        ];
    }

    public function getNumberAttribute(): string
    {
        return str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function status(): QuoteStatus
    {
        $estimate = $this->feeEstimate;

        return match (true) {
            $estimate?->proposal_id !== null => QuoteStatus::CONVERTED,
            $estimate?->valid_until !== null && $estimate->valid_until->lt(today()) => QuoteStatus::EXPIRED,
            default => QuoteStatus::OPEN,
        };
    }

    /**
     * Orçamentos ainda conversíveis: dentro da validade e sem proposta.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereHas('feeEstimate', fn (Builder $query) => $query
            ->whereNull('proposal_id')
            ->whereDate('valid_until', '>=', today()));
    }

    public function feeEstimate(): HasOne
    {
        return $this->hasOne(FeeEstimate::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BillableService::class, 'quote_billable_service')->withPivot('amount')->withTrashed();
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
