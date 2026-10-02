<?php

namespace App\Services;

use App\Enums\DocumentOwner;
use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Receipt;
use App\Models\Tenant;
use App\Models\User;
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

    public function info(string $proposalId, User $viewer, Tenant $tenant): DomPdf
    {
        return $this->make('pdf.proposals.info', $this->infoViewData($proposalId, $viewer, $tenant), $tenant);
    }

    /**
     * Dados do "Informativo da Proposta". Contato e documento completos de
     * cada lado só aparecem para quem pode ver os documentos daquele lado
     * (`DocumentOwner::visibleTo`): o cliente vê o vendedor mascarado e o
     * vendedor do imóvel vê o comprador mascarado; a equipe vê tudo.
     *
     * @return array{proposal: Proposal, showApplicantContacts: bool, showSellerContacts: bool}
     */
    public function infoViewData(string $proposalId, User $viewer, Tenant $tenant): array
    {
        $visibleOwners = $tenant->run(fn () => DocumentOwner::visibleTo($viewer));

        return [
            'proposal' => $this->proposalQueryService->findForDisplay($proposalId, $tenant),
            'showApplicantContacts' => in_array(DocumentOwner::BUYER, $visibleOwners, true),
            'showSellerContacts' => in_array(DocumentOwner::SELLER, $visibleOwners, true),
        ];
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
