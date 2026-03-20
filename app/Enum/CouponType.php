<?php

namespace App\Enum;

enum CouponType: string
{
    case PERCENTAGE = 'percentage';   // Desconto em %
    case FIXED = 'fixed';            // Desconto em valor fixo
}
