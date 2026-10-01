<?php

namespace App\Services;

use App\Models\Bank;

class BankService extends CatalogService
{
    protected function getModel(): string
    {
        return Bank::class;
    }

    protected function usageRelations(): array
    {
        return ['proposals', 'quotes'];
    }
}
