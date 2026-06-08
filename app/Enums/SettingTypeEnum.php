<?php

namespace App\Enums;

enum SettingTypeEnum: string
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case BOOLEAN = 'boolean';
    case DECIMAL = 'decimal';
    case JSON = 'json';
    case ARRAY = 'array';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case EMAIL = 'email';
    case URL = 'url';
}
