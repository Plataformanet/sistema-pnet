<?php

namespace App\Enum;

enum CouponTypeEnum: string
{
    case PERCENTAGE = 'percentage';   // Desconto em %
    case FIXED = 'fixed';            // Desconto em valor fixo
}
