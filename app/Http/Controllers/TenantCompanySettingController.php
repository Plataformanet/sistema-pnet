<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingRequest;
use App\Services\CompanySettingService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantCompanySettingController extends Controller
{
    public function __construct(protected CompanySettingService $companySettingService) {}

    /**
     * Renderiza a tela de edição das configurações da empresa.
     */
    public function edit()
    {
        $data = $this->companySettingService->getCompanySettings(tenant());

        return Inertia::render('tenant/settings/company/Edit', $data);
    }

    /**
     * Atualiza as configurações da empresa e armazena o logotipo no MinIO.
     */
    public function update(UpdateCompanySettingRequest $request)
    {
        try {
            $this->companySettingService->updateCompanySettings(
                $request->validated(),
                tenant(),
                $request->file('logo'),
                $request->boolean('remove_logo')
            );

            return redirect()->back()->with('success', 'Configurações da empresa salvas com sucesso!');
        } catch (\Throwable $th) {
            Log::error('Erro ao atualizar configurações da empresa: '.$th->getMessage(), [
                'exception' => $th,
            ]);

            return redirect()->back()->with('error', 'Ocorreu um erro ao salvar as configurações da empresa.');
        }
    }

    /**
     * Transmite a imagem do logotipo armazenada no MinIO para o navegador.
     */
    public function showLogo(): StreamedResponse|Response
    {
        return $this->companySettingService->getLogoStream(tenant());
    }
}
