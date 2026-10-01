<?php

namespace App\Http\Controllers;

use App\Actions\FeeCalculator\CreateQuote;
use App\Actions\FeeCalculator\UpdateQuote;
use App\Enums\MaritalStatus;
use App\Exceptions\QuoteNotOpenException;
use App\Http\Requests\IndexCatalogRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Services\BankService;
use App\Services\BillableServiceService;
use App\Services\FeeCalculationService;
use App\Services\QuoteService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TenantQuoteController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService,
        protected FeeCalculationService $feeCalculationService,
        protected BankService $bankService,
        protected BillableServiceService $billableServiceService,
        protected CreateQuote $createQuote,
        protected UpdateQuote $updateQuote,
    ) {}

    public function index(IndexCatalogRequest $request)
    {
        return Inertia::render('tenant/documents/quotes/list/List', [
            'quotes' => $this->quoteService->paginate($request->validated(), tenant()),
            'filters' => $request->validated(),
        ]);
    }

    public function create(string $feeCalculationId)
    {
        $calculation = $this->feeCalculationService->findById($feeCalculationId, tenant());

        Gate::authorize('view', $calculation);

        return Inertia::render('tenant/documents/quotes/create/Create', array_merge($this->formOptions(), [
            'calculation' => $this->feeCalculationService->present($calculation),
        ]));
    }

    public function store(StoreQuoteRequest $request, string $feeCalculationId)
    {
        $calculation = $this->feeCalculationService->findById($feeCalculationId, tenant());

        Gate::authorize('view', $calculation);

        try {
            $quote = $this->createQuote->handle($request->validated(), $calculation, $request->user(), tenant());

            return redirect()->route('tenant.documents.quotes.show', $quote->id)
                ->with('success', __('fee_calculator.messages.quote_created', [], 'pt_BR'));
        } catch (\Throwable $th) {
            Log::error('Erro ao criar orçamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao criar orçamento!');
        }
    }

    public function show(string $id)
    {
        return Inertia::render('tenant/documents/quotes/show/Show', [
            'quote' => $this->quoteService->present($this->quoteService->findForDisplay($id, tenant())),
        ]);
    }

    public function edit(string $id)
    {
        $quote = $this->quoteService->findForDisplay($id, tenant());

        return Inertia::render('tenant/documents/quotes/edit/Edit', array_merge($this->formOptions($quote->bank_id), [
            'quote' => $this->quoteService->present($quote),
        ]));
    }

    public function update(UpdateQuoteRequest $request, string $id)
    {
        try {
            $this->updateQuote->handle($id, $request->validated(), tenant());

            return redirect()->route('tenant.documents.quotes.show', $id)
                ->with('success', __('fee_calculator.messages.quote_updated', [], 'pt_BR'));
        } catch (QuoteNotOpenException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar orçamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao atualizar orçamento!');
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->quoteService->delete($id, tenant());

            return redirect()->route('tenant.documents.quotes.list')
                ->with('success', __('fee_calculator.messages.quote_deleted', [], 'pt_BR'));
        } catch (QuoteNotOpenException $th) {
            return redirect()->back()->with('warning', $th->getMessage());
        } catch (\Throwable $th) {
            Log::error('Erro ao excluir orçamento: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao excluir orçamento!');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?int $currentBankId = null): array
    {
        return [
            'banks' => $this->bankService->options(tenant(), $currentBankId)->map->only(['id', 'name']),
            'services' => $this->billableServiceService->options(tenant())->map->only(['id', 'name', 'description', 'price', 'generates_receipt']),
            'maritalStatuses' => MaritalStatus::options(),
        ];
    }
}
