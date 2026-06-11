<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountReceivable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'financial_category_id',
        'financial_subcategory_id',
        'cost_id',
        'bank_account_id',
        'financial_contact_id',
        'description',
        'total',
        'payment_method',
        'payment_condition',
        'total_installments',
        'bank_account_out',
        'observations',
        'receipt',
    ];

    protected $casts = [
        'total' => 'integer',
        'payment_method' => 'integer',
        'total_installments' => 'integer',
        'bank_account_out' => 'integer',
    ];


    public function financialCategory(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    public function financialSubcategory(): BelongsTo
    {
        return $this->belongsTo(FinancialSubcategory::class);
    }

    public function cost(): BelongsTo
    {
        return $this->belongsTo(Cost::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function financialContact(): BelongsTo
    {
        return $this->belongsTo(FinancialContact::class);
    }

    public function installments(): MorphMany
    {
        return $this->morphMany(Installment::class, 'installmentable');
    }
}
