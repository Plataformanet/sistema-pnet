<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'duration',
        'duration_in_months',
        'max_redemptions',
        'times_redeemed',
        'valid_from',
        'valid_until',
        'is_active',
        'applies_to_plans',
    ];

    protected $casts = [
        'type' => 'integer',
        'value' => 'integer',
        'duration' => 'integer',
        'duration_in_months' => 'integer',
        'max_redemptions' => 'integer',
        'times_redeemed' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'applies_to_plans' => 'array',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }
}
