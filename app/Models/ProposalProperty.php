<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalProperty extends Model
{
    protected $fillable = [
        'property_type_id',
        'development_id',
        'address',
        'number',
        'complement',
        'block',
        'unit',
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class)->withTrashed();
    }

    public function development(): BelongsTo
    {
        return $this->belongsTo(Development::class)->withTrashed();
    }
}
