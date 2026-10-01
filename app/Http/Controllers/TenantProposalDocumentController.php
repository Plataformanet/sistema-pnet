<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProposalDocumentRequest;
use App\Services\ProposalDocumentService;
use App\Services\ProposalService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TenantProposalDocumentController extends Controller
{
    public function __construct(
        protected ProposalDocumentService $proposalDocumentService,
        protected ProposalService $proposalService,
    ) {}

    public function store(StoreProposalDocumentRequest $request, string $id)
    {
        Gate::authorize('uploadDocument', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalDocumentService->store(
                $id,
                $request->safe()->except('files'),
                $request->file('files', []),
                $request->user(),
                tenant(),
            );

            return redirect()->back()->with('success', 'Documento enviado com sucesso!');
        } catch (ValidationException $th) {
            throw $th;
        } catch (\Throwable $th) {
            Log::error('Erro ao enviar documento da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao enviar documento!');
        }
    }

    public function download(string $id, string $documentId)
    {
        $proposal = $this->proposalService->findById($id, tenant());
        $document = $this->proposalDocumentService->findById($id, $documentId, tenant());

        Gate::authorize('downloadDocument', [$proposal, $document]);

        return $this->proposalDocumentService->download($document, tenant());
    }

    public function destroy(string $id, string $documentId)
    {
        Gate::authorize('deleteDocument', $this->proposalService->findById($id, tenant()));

        try {
            $this->proposalDocumentService->delete($id, $documentId, tenant());

            return redirect()->back()->with('success', 'Documento excluído com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir documento da proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir documento!');
        }
    }
}
