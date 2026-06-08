<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proponents extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'user_id',
        'income_tax_return',
        'reported_income',
        'income_tax_observation',
        'birth_date',
        'family_income',
        'marital_status',
        'profession',
        'out_of_obligation',
    ];

    protected $casts = [
        'income_tax_return' => 'boolean',
        'out_of_obligation' => 'boolean',
        'reported_income' => 'decimal:2',
        'family_income' => 'decimal:2',
        'birth_date' => 'date',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
