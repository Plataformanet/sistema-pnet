<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountPayable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_financial_id',
        'financial_subcategory_id',
        'cost_id',
        'account_bank_id',
        'contact_financial_id',
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
        'total'              => 'integer',
        'total_installments' => 'integer',
        'bank_account_out'   => 'integer',
        'payment_method'     => FinancialCategoryEnum::class,
    ];

    protected $appends = ['type'];

    public function getTypeAttribute()
    {
        return 'accounts_payable';
    }

    public function categoryFinancial(): BelongsTo
    {
        return $this->belongsTo(CategoryFinancial::class);
    }

    public function financialSubcategory(): BelongsTo
    {
        return $this->belongsTo(FinancialSubcategory::class);
    }

    public function cost(): BelongsTo
    {
        return $this->belongsTo(Cost::class);
    }

    public function accountBank(): BelongsTo
    {
        return $this->belongsTo(AccountBank::class);
    }

    public function contactFinancial(): BelongsTo
    {
        return $this->belongsTo(ContactFinancial::class);
    }

    public function installments(): MorphMany
    {
        return $this->morphMany(Installment::class, 'installmentable');
    }
}
