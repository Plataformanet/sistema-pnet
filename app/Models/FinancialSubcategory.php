<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialSubcategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_financial_id',
        'name',
        'observations',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function categoryFinancial(): BelongsTo
    {
        return $this->belongsTo(CategoryFinancial::class);
    }

    public function accountsPayable(): HasMany
    {
        return $this->hasMany(AccountsPayable::class);
    }

    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountsReceivable::class);
    }
}
