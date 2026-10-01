<?php

namespace App\Services;

use App\Enums\CostItemType;
use App\Exceptions\CostItemNotRemovableException;
use App\Mail\ProposalCostItemCreatedMail;
use App\Models\Proposal;
use App\Models\ProposalCostItem;
use App\Models\Tenant;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Linhas de custo da proposta. Este módulo só cria lançamentos manuais; as
 * linhas da calculadora (emolumento, taxa extra, serviço e ITBI) vêm do
 * `CostItemsBuilder` e aqui têm apenas o valor editável.
 */
class ProposalCostItemService
{
    public function __construct(
        protected ProposalDocumentService $proposalDocumentService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeManual(string $proposalId, array $data, ?UploadedFile $bill, Tenant $tenant): ProposalCostItem
    {
        return $tenant->run(function () use ($proposalId, $data, $bill) {
            $proposal = Proposal::with(['partners:id,name,email', 'applicants.contact:id,name_corporatereason,email'])->findOrFail($proposalId);
            $billPath = $bill !== null ? $this->storeFile($proposal, $bill, 'bill') : null;

            try {
                $costItem = DB::transaction(fn () => $proposal->costItems()->create(array_merge(
                    Arr::only($data, ['cost_type_id', 'notary_id', 'description', 'date', 'amount', 'notes']),
                    [
                        'type' => CostItemType::MANUAL,
                        'generates_receipt' => false,
                        'bill_path' => $billPath,
                    ],
                )));
            } catch (Throwable $exception) {
                if ($billPath !== null) {
                    $this->proposalDocumentService->disk()->delete($billPath);
                }

                throw $exception;
            }

            $this->notify($proposal, $costItem, (bool) ($data['notify_partners'] ?? false), (bool) ($data['notify_applicants'] ?? false));

            return $costItem;
        });
    }

    /**
     * Edita o valor de qualquer linha. A linha precisa pertencer à proposta
     * (equivalente ao scoped binding da rota).
     */
    public function updateAmount(string $proposalId, string $costItemId, int $amount, Tenant $tenant): bool
    {
        return $tenant->run(fn () => $this->findForProposal($proposalId, $costItemId)->update(['amount' => $amount]));
    }

    /**
     * @throws CostItemNotRemovableException quando a linha veio da calculadora.
     */
    public function delete(string $proposalId, string $costItemId, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposalId, $costItemId) {
            $costItem = $this->findForProposal($proposalId, $costItemId);

            if ($costItem->type !== CostItemType::MANUAL) {
                throw new CostItemNotRemovableException;
            }

            $paths = array_filter([$costItem->bill_path, $costItem->proof_path]);

            DB::transaction(fn () => $costItem->delete());

            $this->proposalDocumentService->disk()->delete($paths);
        });
    }

    /**
     * Comprovante de pagamento anexado (inclusive pelo cliente, na própria
     * proposta — a checagem de quem pode enviar é da policy). Substitui o anterior.
     */
    public function uploadProof(string $proposalId, string $costItemId, UploadedFile $proof, Tenant $tenant): void
    {
        $tenant->run(function () use ($proposalId, $costItemId, $proof) {
            $costItem = $this->findForProposal($proposalId, $costItemId);
            $previous = $costItem->proof_path;
            $path = $this->storeFile($costItem->proposal, $proof, 'proof');

            try {
                $costItem->update(['proof_path' => $path]);
            } catch (Throwable $exception) {
                $this->proposalDocumentService->disk()->delete($path);

                throw $exception;
            }

            if ($previous !== null) {
                $this->proposalDocumentService->disk()->delete($previous);
            }
        });
    }

    /**
     * @param  'bill'|'proof'  $attachment
     */
    public function download(string $proposalId, string $costItemId, string $attachment, Tenant $tenant): StreamedResponse
    {
        return $tenant->run(function () use ($proposalId, $costItemId, $attachment) {
            $path = $this->findForProposal($proposalId, $costItemId)->{$attachment.'_path'};

            abort_if($path === null, 404);

            return $this->proposalDocumentService->disk()->download($path);
        });
    }

    public function findForProposal(string $proposalId, string $costItemId): ProposalCostItem
    {
        return ProposalCostItem::where('proposal_id', $proposalId)->findOrFail($costItemId);
    }

    private function storeFile(Proposal $proposal, UploadedFile $file, string $prefix): string
    {
        return $this->proposalDocumentService->disk()->putFileAs(
            ProposalDocumentService::basePath($proposal->id).'/cost-items',
            $file,
            $prefix.'-'.Str::uuid().'.'.($file->getClientOriginalExtension() ?: $file->extension()),
        );
    }

    private function notify(Proposal $proposal, ProposalCostItem $costItem, bool $partners, bool $applicants): void
    {
        $recipients = collect();

        if ($partners) {
            $recipients = $recipients->merge($proposal->partners->map(fn ($partner) => [$partner->email, $partner->name]));
        }

        if ($applicants) {
            $recipients = $recipients->merge($proposal->applicants->map(fn ($applicant) => [
                $applicant->contact->email,
                $applicant->contact->name_corporatereason,
            ]));
        }

        $recipients->unique(fn (array $recipient) => $recipient[0])->each(fn (array $recipient) => Mail::to($recipient[0])->queue(
            new ProposalCostItemCreatedMail(
                $recipient[1],
                $proposal->number,
                (string) ($costItem->description ?? $costItem->costType?->name),
                Money::format($costItem->amount),
                $costItem->date?->format('d/m/Y'),
            )
        ));
    }
}
