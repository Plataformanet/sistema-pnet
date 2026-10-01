<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'shows_number',
        'shows_complement',
        'requires_development',
        'shows_unit',
        'shows_block',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shows_number' => 'boolean',
            'shows_complement' => 'boolean',
            'requires_development' => 'boolean',
            'shows_unit' => 'boolean',
            'shows_block' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function proposalProperties(): HasMany
    {
        return $this->hasMany(ProposalProperty::class);
    }
}
