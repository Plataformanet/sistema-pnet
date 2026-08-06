<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanySettingService
{
    /**
     * Retorna as configurações da empresa e a URL do logotipo no escopo do tenant.
     */
    public function getCompanySettings(Tenant $tenant): array
    {
        return $tenant->run(function () {
            $settings = TenantSetting::where('module', 'company')
                ->get()
                ->mapWithKeys(function (TenantSetting $setting) {
                    $cleanKey = str_replace('company.', '', $setting->key);

                    return [$cleanKey => $setting->value];
                })
                ->toArray();

            $logoPath = TenantSetting::where('key', 'company.logo_path')->value('value');
            $logoUrl = null;

            if ($logoPath && Storage::disk(config('bucket.disk'))->exists($logoPath)) {
                $logoUrl = route('tenant.settings.company.logo').'?v='.time();
            }

            return [
                'company' => $settings,
                'logoUrl' => $logoUrl,
            ];
        });
    }

    /**
     * Atualiza os campos cadastrais e gerencia o upload/remoção de logotipo no MinIO com limpeza de cache e transação.
     */
    public function updateCompanySettings(array $data, Tenant $tenant, ?UploadedFile $logoFile = null, bool $removeLogo = false): void
    {
        $tenant->run(function () use ($data, $logoFile, $removeLogo) {
            DB::transaction(function () use ($data, $logoFile, $removeLogo) {
                $disk = Storage::disk(config('bucket.disk'));

                // Ignora campos referentes a arquivos e flags de controle
                $fieldsToSave = collect($data)->except(['logo', 'remove_logo', 'logo_path']);

                // 1. Salva/Atualiza dinamicamente qualquer campo enviado
                foreach ($fieldsToSave as $field => $value) {
                    $key = "company.{$field}";

                    TenantSetting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => (string) ($value ?? ''),
                            'module' => 'company',
                            'type' => 'string',
                            'user_id' => auth()->id(),
                        ]
                    );

                    $this->forgetCache("settings.{$key}");
                }

                $currentLogoPath = TenantSetting::where('key', 'company.logo_path')->value('value');

                // 2. Remoção do logotipo
                if ($removeLogo && $currentLogoPath) {
                    if ($disk->exists($currentLogoPath)) {
                        $disk->delete($currentLogoPath);
                    }
                    TenantSetting::where('key', 'company.logo_path')->delete();
                    $this->forgetCache('settings.company.logo_path');
                }

                // 3. Upload de novo logotipo
                if ($logoFile) {
                    if ($currentLogoPath && $disk->exists($currentLogoPath)) {
                        $disk->delete($currentLogoPath);
                    }

                    $newLogoPath = $logoFile->store('company', config('bucket.disk'));

                    TenantSetting::updateOrCreate(
                        ['key' => 'company.logo_path'],
                        [
                            'value' => $newLogoPath,
                            'module' => 'company',
                            'type' => 'string',
                            'user_id' => auth()->id(),
                        ]
                    );

                    $this->forgetCache('settings.company.logo_path');
                }

                $this->forgetCache('settings.public');
            });
        });
    }

    /**
     * Transmite o arquivo de logotipo armazenado no MinIO para o navegador.
     */
    public function getLogoStream(Tenant $tenant): StreamedResponse|Response
    {
        return $tenant->run(function () {
            $logoPath = TenantSetting::where('key', 'company.logo_path')->value('value');

            if (! $logoPath || ! Storage::disk(config('bucket.disk'))->exists($logoPath)) {
                abort(404);
            }

            return Storage::disk(config('bucket.disk'))->response($logoPath);
        });
    }

    /**
     * Limpa o cache de forma segura ignorando exceções de tagging em drivers não-compatíveis.
     */
    private function forgetCache(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (\Throwable $e) {
            // Ignora exceções de tagging em drivers sem suporte a tags (ex: file/database)
        }
    }
}
