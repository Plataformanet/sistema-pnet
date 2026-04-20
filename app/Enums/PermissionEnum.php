<?php

namespace App\Enums;

enum PermissionEnum: string
{
    // case NAMEINAPP = 'name-in-database';

    case WRITER = 'writer';
    case EDITOR = 'editor';
    case VIEW = 'view';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string
    {
        return match ($this) {
            static::WRITER => 'Writers',
            static::EDITOR => 'Editors',
            static::VIEW => 'Viewers',
        };
    }
}
