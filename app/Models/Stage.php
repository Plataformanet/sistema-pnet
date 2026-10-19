<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_id',
        'order',
        'name',
        'has_date',
        'date_required',
        'has_upload',
        'upload_required',
        'title_required',
        'notes_required',
        'completion_deadline_hours',
        'alert_deadline_hours',
        'shows_property_data',
        'shows_registry_protocol',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bank_id' => 'integer',
            'order' => 'integer',
            'has_date' => 'boolean',
            'date_required' => 'boolean',
            'has_upload' => 'boolean',
            'upload_required' => 'boolean',
            'title_required' => 'boolean',
            'notes_required' => 'boolean',
            'completion_deadline_hours' => 'integer',
            'alert_deadline_hours' => 'integer',
            'shows_property_data' => 'boolean',
            'shows_registry_protocol' => 'boolean',
        ];
    }

    /**
     * Ordem da timeline; o `id` desempata etapas excluídas que repetem a ordem.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order')->orderBy('id');
    }

    /**
     * Etapas de uma timeline: a padrão do sistema (`null`) ou a do banco.
     */
    public function scopeForTimeline(Builder $query, ?int $bankId): Builder
    {
        return $bankId === null
            ? $query->whereNull('bank_id')
            : $query->where('bank_id', $bankId);
    }

    /**
     * Banco dono da timeline; `null` na timeline padrão do sistema.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class)->withTrashed();
    }

    public function proposalStages(): HasMany
    {
        return $this->hasMany(ProposalStage::class);
    }
}
