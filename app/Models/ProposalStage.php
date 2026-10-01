<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Etapa instanciada na timeline de uma proposta. O status é derivado:
 * `completed_at` ⇒ concluída; `started_at` ⇒ em andamento; senão bloqueada.
 */
class ProposalStage extends Model
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'stage_id',
        'position',
        'proposal_document_id',
        'date',
        'notes',
        'is_current',
        'started_at',
        'completed_at',
    ];

    protected $appends = ['status'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'date' => 'date:Y-m-d',
            'is_current' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->completed_at !== null => self::STATUS_COMPLETED,
            $this->started_at !== null => self::STATUS_IN_PROGRESS,
            default => self::STATUS_LOCKED,
        };
    }

    /**
     * Horas restantes até o prazo de conclusão da etapa (0 = vence hoje,
     * negativo = atrasada). Nulo quando a etapa não tem prazo ou não começou.
     */
    public function remainingHours(): ?int
    {
        $deadline = $this->stage?->completion_deadline_hours ?? 0;

        if ($deadline <= 0 || $this->started_at === null || $this->completed_at !== null) {
            return null;
        }

        return $deadline - (int) $this->started_at->diffInHours(now());
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereNotNull('started_at')->whereNull('completed_at');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class)->withTrashed();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ProposalDocument::class, 'proposal_document_id');
    }
}
