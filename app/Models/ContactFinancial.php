<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactFinancial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'type',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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
