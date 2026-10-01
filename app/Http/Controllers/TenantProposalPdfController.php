<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Http\Requests\IndexProposalRequest;
use App\Services\ProposalPdfService;
use App\Services\ProposalQueryService;
use App\Services\ProposalService;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\Gate;

class TenantProposalPdfController extends Controller
{
    public function __construct(
        protected ProposalPdfService $proposalPdfService,
        protected ProposalQueryService $proposalQueryService,
        protected ProposalService $proposalService,
        protected ReceiptService $receiptService,
    ) {}

    public function info(string $id)
    {
        $proposal = $this->proposalService->findById($id, tenant());

        Gate::authorize('print', $proposal);

        return $this->proposalPdfService->info($id, tenant())->stream("informativo-proposta-{$proposal->number}.pdf");
    }

    public function tracking(string $id)
    {
        $proposal = $this->proposalService->findById($id, tenant());

        Gate::authorize('print', $proposal);

        return $this->proposalPdfService->tracking($id, tenant())->stream("acompanhamento-proposta-{$proposal->number}.pdf");
    }

    /**
     * Lista por status/busca ou, quando há `ids`, só as propostas selecionadas
     * na tabela. Sempre limitada ao que o usuário pode ver.
     */
    public function list(IndexProposalRequest $request)
    {
        $filters = $request->validated();
        $proposals = $this->proposalQueryService->forPrint($filters, $request->user(), tenant());
        $status = empty($filters['ids']) && ! empty($filters['status']) ? ProposalStatus::from($filters['status']) : null;

        return $this->proposalPdfService->list($proposals, $status, tenant())->stream('lista-de-propostas.pdf');
    }

    public function receipt(string $id, string $receiptId)
    {
        Gate::authorize('viewFinancial', $this->proposalService->findById($id, tenant()));

        $receipt = $this->receiptService->findById($id, $receiptId, tenant());

        return $this->proposalPdfService->receipt($receipt, tenant())->stream("recibo-{$receipt->id}.pdf");
    }
}
