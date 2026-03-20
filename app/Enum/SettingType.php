<?php

namespace App\Enum;

enum SettingType: int
{
    case STRING = 0;
    case INTEGER = 1;
    case BOOLEAN = 2;
    case DECIMAL = 3;
    case JSON = 4;
    case ARRAY = 5;
    case DATE = 6;
    case DATETIME = 7;
    case EMAIL = 8;
    case URL = 9;

    public function getType(): string
    {
        return match ($this) {
            self::STRING => 'string',
            self::INTEGER => 'integer',
            self::BOOLEAN => 'boolean',
            self::DECIMAL => 'decimal',
            self::JSON => 'json',
            self::ARRAY => 'array',
            self::DATE => 'date',
            self::DATETIME => 'datetime',
            self::EMAIL => 'email',
            self::URL => 'url',
            default => 'Tipo não encontrado',
        };
    }
}
