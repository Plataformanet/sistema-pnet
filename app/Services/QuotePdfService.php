<?php

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\Tenant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

class QuotePdfService
{
    public function __construct(
        protected QuoteService $quoteService,
        protected CompanySettingService $companySettingService,
    ) {}

    /**
     * "Informativo do Orçamento". O bloco "Administrador ou Analista" mostra
     * quem gerou o PDF quando é administrador ou analista.
     */
    public function make(string $quoteId, ?User $viewer, Tenant $tenant): DomPdf
    {
        $quote = $this->quoteService->findForDisplay($quoteId, $tenant);
        $showViewer = $viewer !== null && $tenant->run(
            fn () => $viewer->hasAnyRole(RolesEnum::proposalStaffLabels())
        );

        $pdf = Pdf::loadView('pdf.quote', array_merge($this->companySettingService->pdfBranding($tenant), [
            'quote' => $quote,
            'breakdown' => $this->quoteService->breakdown($quote)->toArray(),
            'viewer' => $showViewer ? $viewer : null,
        ]));
        $pdf->setOption(['dpi' => 140, 'defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4', 'portrait');

        return $pdf;
    }
}
