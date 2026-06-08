<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'mp_card_id',
        'type',
        'brand',
        'last_four',
        'holder_name',
        'expiration_month',
        'expiration_year',
        'is_default',
    ];

    protected $casts = [
        'type' => 'integer',
        'expiration_month' => 'integer',
        'expiration_year' => 'integer',
        'is_default' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
