<?php

namespace App\Http\Controllers;

use App\Actions\FeeCalculator\CalculateFees;
use App\Enums\CalculationType;
use App\Enums\SupportedState;
use App\Exceptions\Itbi\ItbiNotConfiguredException;
use App\Exceptions\RegistryFee\RegistryFeeRejectedException;
use App\Exceptions\RegistryFee\RegistryFeeUnavailableException;
use App\Http\Requests\CalculateFeesRequest;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TenantFeeCalculatorController extends Controller
{
    public function __construct(
        protected CalculateFees $calculateFees,
        protected FeeCalculationService $feeCalculationService,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('tenant/documents/fee-calculator/index/Index', [
            'states' => SupportedState::options(),
            'types' => CalculationType::options(),
            'presentation' => __('fee_calculator.presentation', [], 'pt_BR'),
            'selected' => $request->only(['state', 'ibge', 'name']),
        ]);
    }

    /**
     * UF, município e tipo vêm da URL para permitir voltar e recarregar sem
     * perder a escolha.
     */
    /**
     * `{type}` chega como número e é convertido aqui: o binding implícito do
     * Laravel só resolve enums de string, e `CalculationType` é de inteiro.
     */
    public function create(Request $request, int $type)
    {
        $type = CalculationType::tryFrom($type) ?? abort(404);

        return Inertia::render('tenant/documents/fee-calculator/calculate/Calculate', $this->feeCalculationService->formProps(
            $type,
            SupportedState::tryFrom((string) $request->query('state')),
            $request->integer('ibge') ?: null,
            $request->query('name'),
            tenant(),
        ));
    }

    public function store(CalculateFeesRequest $request)
    {
        try {
            $calculation = $this->calculateFees->handle($request->validated(), $request->user(), tenant());

            return redirect()->route('tenant.documents.fee-calculator.results.show', $calculation->id);
        } catch (RegistryFeeRejectedException $th) {
            throw ValidationException::withMessages(['property_value' => $th->getMessage()]);
        } catch (ItbiNotConfiguredException $th) {
            throw ValidationException::withMessages(['municipality_name' => $th->getMessage()]);
        } catch (RegistryFeeUnavailableException $th) {
            return redirect()->back()->with('error', __('fee_calculator.messages.api_unavailable', [], 'pt_BR'));
        } catch (\Throwable $th) {
            Log::error('Erro ao calcular emolumentos: '.$th->getMessage());

            return redirect()->back()->with('error', 'Erro ao calcular emolumentos!');
        }
    }
}
