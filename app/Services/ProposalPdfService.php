<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Collection;

/**
 * PDFs da proposta, com os dados da empresa das configurações do tenant.
 */
class ProposalPdfService
{
    public function __construct(
        protected CompanySettingService $companySettingService,
        protected ProposalQueryService $proposalQueryService,
    ) {}

    public function info(string $proposalId, Tenant $tenant): DomPdf
    {
        $proposal = $this->proposalQueryService->findForDisplay($proposalId, $tenant);

        return $this->make('pdf.proposals.info', ['proposal' => $proposal], $tenant);
    }

    public function tracking(string $proposalId, Tenant $tenant): DomPdf
    {
        $proposal = $this->proposalQueryService->findForDisplay($proposalId, $tenant);

        return $this->make('pdf.proposals.tracking', ['proposal' => $proposal], $tenant);
    }

    /**
     * @param  Collection<int, Proposal>  $proposals
     */
    public function list(Collection $proposals, ?ProposalStatus $status, Tenant $tenant): DomPdf
    {
        return $this->make('pdf.proposals.list', [
            'proposals' => $proposals,
            'title' => $status !== null ? 'Propostas — '.$status->label() : 'Propostas selecionadas',
        ], $tenant, 'landscape');
    }

    public function receipt(Receipt $receipt, Tenant $tenant): DomPdf
    {
        $proposal = $this->proposalQueryService->findForDisplay((string) $receipt->proposal_id, $tenant);
        $feesTotal = $tenant->run(fn () => $proposal->feesTotal());

        return $this->make('pdf.proposals.receipt', [
            'receipt' => $receipt,
            'proposal' => $proposal,
            'feesTotal' => $feesTotal,
        ], $tenant);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function make(string $view, array $data, Tenant $tenant, string $orientation = 'portrait'): DomPdf
    {
        $pdf = Pdf::loadView($view, array_merge($data, $this->companySettingService->pdfBranding($tenant)));
        $pdf->setOption(['dpi' => 140, 'defaultFont' => 'sans-serif']);
        $pdf->setPaper('A4', $orientation);

        return $pdf;
    }
}
