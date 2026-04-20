<?php

namespace App\Enums;

enum CouponDurationEnum: string
{
    case ONCE = 'once';              // Aplica apenas uma vez
    case REPEATING = 'repeating';    // Repete por X meses
    case FOREVER = 'forever';        // Desconto permanente
}
