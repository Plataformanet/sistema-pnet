<?php

namespace App\Enum;

enum CouponDuration: string
{
    case ONCE = 'once';              // Aplica apenas uma vez
    case REPEATING = 'repeating';    // Repete por X meses
    case FOREVER = 'forever';        // Desconto permanente
}
