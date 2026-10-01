<?php

namespace App\Http\Controllers;

use App\Exceptions\ReceiptNotAllowedException;
use App\Http\Requests\StoreReceiptRequest;
use App\Services\ProposalService;
use App\Services\ReceiptService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
        protected ProposalService $proposalService,
    ) {}

    public function store(StoreReceiptRequest $request, string $id)
    {
        Gate::authorize('manageFinancial', $this->proposalService->findById($id, tenant()));

        try {
            $this->receiptService->store($id, $request->validated(), tenant());

            return redirect()->back()->with('success', 'Recibo criado com sucesso!');
        } catch (ReceiptNotAllowedException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao criar recibo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar recibo!');
        }
    }

    public function destroy(string $id, string $receiptId)
    {
        Gate::authorize('manageFinancial', $this->proposalService->findById($id, tenant()));

        try {
            $this->receiptService->delete($id, $receiptId, tenant());

            return redirect()->back()->with('success', 'Recibo excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir recibo: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir recibo!');
        }
    }
}
