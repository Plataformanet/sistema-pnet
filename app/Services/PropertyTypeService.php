<?php

namespace App\Services;

use App\Models\PropertyType;

class PropertyTypeService extends CatalogService
{
    protected function getModel(): string
    {
        return PropertyType::class;
    }

    protected function usageRelations(): array
    {
        return ['proposalProperties'];
    }
}
