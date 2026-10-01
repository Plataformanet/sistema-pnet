<?php

namespace App\Services;

use App\Models\CostType;

class CostTypeService extends CatalogService
{
    protected function getModel(): string
    {
        return CostType::class;
    }

    protected function usageRelations(): array
    {
        return ['costItems', 'accountsPayable', 'accountsReceivable'];
    }
}
