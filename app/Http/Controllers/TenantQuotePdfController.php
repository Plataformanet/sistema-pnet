<?php

namespace App\Http\Controllers;

use App\Services\QuotePdfService;
use Illuminate\Http\Request;

class TenantQuotePdfController extends Controller
{
    public function __construct(
        protected QuotePdfService $quotePdfService,
    ) {}

    public function __invoke(Request $request, string $id)
    {
        $number = str_pad($id, 5, '0', STR_PAD_LEFT);

        return $this->quotePdfService->make($id, $request->user(), tenant())->stream("orcamento-{$number}.pdf");
    }
}
