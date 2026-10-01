<?php

namespace App\Services;

use App\Models\Development;

class DevelopmentService extends CatalogService
{
    protected function getModel(): string
    {
        return Development::class;
    }

    protected function usageRelations(): array
    {
        return ['proposalProperties'];
    }
}
