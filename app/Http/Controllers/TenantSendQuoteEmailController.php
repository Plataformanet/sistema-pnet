<?php

namespace App\Http\Controllers;

use App\Mail\QuoteSummaryMail;
use App\Services\QuoteService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TenantSendQuoteEmailController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService,
    ) {}

    public function __invoke(string $id)
    {
        try {
            $quote = $this->quoteService->findForDisplay($id, tenant());

            Mail::to($quote->email)->queue(new QuoteSummaryMail(
                $quote->id,
                $quote->name,
                $quote->number,
                (string) $quote->feeEstimate?->valid_until?->format('d/m/Y'),
                $this->quoteService->breakdown($quote)->toArray(),
                auth()->id(),
            ));

            return redirect()->back()->with('success', __('fee_calculator.messages.email_sent', [], 'pt_BR'));
        } catch (\Throwable $th) {
            Log::error('Erro ao enviar e-mail do orçamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao enviar o e-mail do orçamento!');
        }
    }
}
