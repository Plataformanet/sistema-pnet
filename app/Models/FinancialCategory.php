<?php

namespace App\Models;

use App\Enums\FinancialCategoryEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'observations',
        'active',
    ];

    protected $casts = [
        'type' => FinancialCategoryEnum::class,
        'active' => 'boolean',
    ];

    public function subcategories(): HasMany
    {
        return $this->hasMany(FinancialSubcategory::class);
    }

    public function accountsPayable(): HasMany
    {
        return $this->hasMany(AccountPayable::class);
    }

    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountReceivable::class);
    }
}
