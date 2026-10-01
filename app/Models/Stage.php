<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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

    public function proposalStages(): HasMany
    {
        return $this->hasMany(ProposalStage::class);
    }
}
