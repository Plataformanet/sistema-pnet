<?php

namespace App\Http\Controllers;

use App\Exceptions\UpdateInstallmentException;
use App\Models\BankAccount;
use App\Models\Contact;
use App\Models\Cost;
use App\Models\FinancialCategory;
use App\Services\AccountPayableService;
use App\Services\BankAccountService;
use App\Services\ContactService;
use App\Services\FinancialCategoryService;
use App\Services\FinancialSubcategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TenantAccountPayableController extends Controller
{
    public function __construct(
        protected AccountPayableService $accountPayableService,
        protected FinancialCategoryService $financialCategoryService,
        protected FinancialSubcategoryService $financialSubcategoryService,
        protected ContactService $contactService,
        protected BankAccountService $bankAccountService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {


        $periodo = $request->get('periodo', now()->format('Y-m'));
        $dias    = 7;

        $contasAPagar = $this->accountPayableService->findAll($request, $periodo, tenant());

        if (!$request->has('conta_id')) {
            $contaBancaria = BankAccount::select('id', 'nome', 'banco', 'saldo_atual')->where('conta_principal', 1)->first();
        }

        if ($request->has('conta_id')) {
            $contaBancaria = BankAccount::select('id', 'nome', 'banco', 'saldo_atual')->where('id', $request->query('conta_id'))->first();
        }

        $totalPeriodo    = $this->accountPayableService->totalPeriod($request, $periodo, $contaBancaria?->id);
        $totalPagos      = $this->accountPayableService->totalPaid($request, $periodo, $contaBancaria?->id);
        $totalVencemHoje = $this->accountPayableService->totalDueToday($request, $periodo, $contaBancaria?->id);
        $totalAVencer    = $this->accountPayableService->totalToDue($request, $dias, $periodo, $contaBancaria?->id);
        $totalVencidos   = $this->accountPayableService->totalOverdue($request, $periodo, $contaBancaria?->id);

        $categoriasFinanceira = $this->financialCategoryService->findAll(tenant());

        $categoriaBuscada = FinancialCategory::select('nome')->find($request->get('categoria_id'));

        $contaBancarias = BankAccount::select('id', 'nome', 'banco', 'saldo_atual', 'conta_principal')->get();

        return view('app.financeiro.conta_a_pagar.index', [
            'contasAPagar'         => $contasAPagar,
            'totalPeriodo'         => $totalPeriodo,
            'totalPagos'           => $totalPagos,
            'totalVencemHoje'      => $totalVencemHoje,
            'totalAVencer'         => $totalAVencer,
            'totalVencidos'        => $totalVencidos,
            'periodo'              => $periodo,
            'quantidade'           => $request->get('quantidade'),
            'inicio'               => $request->get('inicio'),
            'fim'                  => $request->get('fim'),
            'categoria_id'         => $request->get('categoria_id'),
            'categoriasFinanceira' => $categoriasFinanceira,
            'categoriaBuscada'     => $categoriaBuscada,
            'contaBancarias'       => $contaBancarias,
            'contaBancaria'        => $contaBancaria,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {


        $categoriasFinanceira    = $this->financialCategoryService->findCategoriaContasAPagar(tenant());
        $subcategoriasFinanceira = $this->financialSubcategoryService->findAll(tenant());
        $custos                  = Cost::select('id', 'tipo')->get();

        $subcategoriasFinanceira = $subcategoriasFinanceira->map(function ($item) {
            if ($item->ativo === 1) {
                return $item->nome;
            }
        });

        $contatos = collect();
        Contact::select('id', 'nome_razaosocial')
            ->chunkById(500, function ($chunk) use (&$contatos) {
                $contatos = $contatos->merge($chunk);
            });

        $condicoesPagamento = $this->accountPayableService->condicoesPagamento();

        $contasBancarias = $this->contaBancariaService->findAll();

        return view('app.financeiro.conta_a_pagar.create', [
            'categoriasFinanceira'    => $categoriasFinanceira,
            'subcategoriasFinanceira' => $subcategoriasFinanceira,
            'custos'                  => $custos,
            'contatos'                => $contatos,
            'condicoesPagamento'      => $condicoesPagamento,
            'contasBancarias'         => $contasBancarias,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccountPayableRequest $request)
    {


        $contaAPagar = $this->accountPayableService->create($request->validated());

        if ($contaAPagar) {
            return redirect()->route('contas-a-pagar.index')->with('msg', 'Conta a pagar cadastrada com sucesso!');
        }

        return redirect()->route('contas-a-pagar.index')->with('msg_erro', 'Erro ao tentar fazer cadastro!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {


        $contaAPagar = $this->accountPayableService->showById($id, tenant());

        return view(
            'app.financeiro.conta_a_pagar.show',
            [
                'contaAPagar' => $contaAPagar
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {


        $contaAPagar = $this->accountPayableService->findById($id, tenant());

        $categoriasFinanceira    = $this->financialCategoryService->findCategoriaContasAPagar(tenant());
        $subcategoriasFinanceira = $this->financialSubcategoryService->findAll(tenant());
        $custos                  = Cost::select('id', 'tipo')->get();

        $subcategoriasFinanceira = $subcategoriasFinanceira->map(function ($item) {
            if ($item->ativo === 1) {
                return ['id' => $item->id, 'nome' => $item->nome];
            }
        });

        $contatos = collect();
        Contact::select('id', 'nome_razaosocial')
            ->chunkById(500, function ($chunk) use (&$contatos) {
                $contatos = $contatos->merge($chunk);
            });

        $condicoesPagamento = $this->accountPayableService->condicoesPagamento();

        $contasBancarias = $this->contaBancariaService->findAll();

        return view('app.financeiro.conta_a_pagar.edit', [
            'contaAPagar'             => $contaAPagar,
            'categoriasFinanceira'    => $categoriasFinanceira,
            'subcategoriasFinanceira' => $subcategoriasFinanceira,
            'custos'                  => $custos,
            'contatos'                => $contatos,
            'condicoesPagamento'      => $condicoesPagamento,
            'contasBancarias'         => $contasBancarias,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccountPayableRequest $request, string $id)
    {


        $contaAPagar = $this->accountPayableService->update($id, $request->validated());

        if ($contaAPagar) {
            return redirect()->route('contas-a-pagar.edit', ['contas_a_pagar' => $contaAPagar->id])->with('msg', 'Conta a pagar atualizada com sucesso!');
        }

        return redirect()->route('contas-a-pagar.edit', ['contas_a_pagar' => $contaAPagar->id])->with('msg_erro', 'Erro ao tentar atualizar a conta a pagar!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {


        $contaAPagar = $this->accountPayableService->delete($id, $request);

        if ($contaAPagar) {
            return redirect()->route('contas-a-pagar.index')->with('msg', 'Conta a pagar excluída com sucesso!');
        }

        return redirect()->route('contas-a-pagar.index')->with('msg_erro', 'Erro ao tentar excluir a conta a pagar!');
    }

    public function atualizarParcelas(Request $request)
    {


        try {
            $this->accountPayableService->atualizarParcelas($request->get('id'));

            return response()->json([
                'success' => true,
                'message' => 'Parcelas atualizadas com sucesso!',
            ], Response::HTTP_CREATED);
        } catch (\Throwable) {
            throw new UpdateInstallmentException('Erro ao atualizar as parcelas!');
        }
    }

    public function atualizarValorParcela(UpdateValorParcelaRequest $request)
    {


        $parcelasAtualizadas = $this->accountPayableService->atualizarValorParcela($request->validated());

        if ($parcelasAtualizadas) {
            return response()->json(['status' => 200, 'message' => 'Valor da parcela atualizada com sucesso!']);
        }

        return response()->json(['status' => 500, 'message' => 'Erro ao atualizar a parcela!']);
    }
}
