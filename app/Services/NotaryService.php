<?php

namespace App\Services;

use App\Models\Notary;

class NotaryService extends CatalogService
{
    protected function getModel(): string
    {
        return Notary::class;
    }

    protected function searchableColumns(): array
    {
        return ['name', 'city'];
    }

    protected function usageRelations(): array
    {
        return ['costItems'];
    }
}
