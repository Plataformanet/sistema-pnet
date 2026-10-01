<?php

namespace App\Http\Controllers;

use App\Actions\FeeCalculator\ConvertQuoteToProposal;
use App\Exceptions\QuoteNotOpenException;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TenantConvertQuoteToProposalController extends Controller
{
    public function __construct(
        protected ConvertQuoteToProposal $convertQuoteToProposal,
    ) {}

    public function __invoke(Request $request, string $id)
    {
        Gate::authorize('create', Proposal::class);

        try {
            $proposal = $this->convertQuoteToProposal->handle($id, $request->user(), tenant());

            return redirect()->route('tenant.documents.proposals.edit', $proposal->id)
                ->with('success', __('fee_calculator.messages.proposal_created', [], 'pt_BR'));
        } catch (QuoteNotOpenException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao converter orçamento em proposta: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao gerar a proposta!');
        }
    }
}
