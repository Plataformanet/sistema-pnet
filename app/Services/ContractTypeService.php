<?php

namespace App\Services;

use App\Models\ContractType;

class ContractTypeService extends CatalogService
{
    protected function getModel(): string
    {
        return ContractType::class;
    }

    protected function usageRelations(): array
    {
        return ['proposals'];
    }
}
