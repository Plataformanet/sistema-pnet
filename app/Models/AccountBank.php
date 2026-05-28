<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountBank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'bank',
        'agency',
        'account_number',
        'account_type',
        'initial_balance',
        'current_balance',
        'active',
        'main_account',
    ];

    protected $casts = [
        'initial_balance' => 'integer',
        'current_balance' => 'integer',
        'active'          => 'boolean',
        'main_account'    => 'boolean',
    ];

    public function accountsPayable(): HasMany
    {
        return $this->hasMany(AccountsPayable::class);
    }

    public function accountsReceivable(): HasMany
    {
        return $this->hasMany(AccountsReceivable::class);
    }
}
