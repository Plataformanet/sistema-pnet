<?php

namespace App\Models;

use App\Enums\DocumentOwner;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Documento da proposta, sempre em disco privado e baixado por rota autorizada.
 */
class ProposalDocument extends Model
{
    protected $fillable = [
        'uploaded_by',
        'owner',
        'documentable_type',
        'documentable_id',
        'proposal_stage_id',
        'type',
        'title',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected $hidden = [
        'disk',
        'path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'owner' => DocumentOwner::class,
            'type' => DocumentType::class,
            'size' => 'integer',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function proposalStage(): BelongsTo
    {
        return $this->belongsTo(ProposalStage::class);
    }
}
