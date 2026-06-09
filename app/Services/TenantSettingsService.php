<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Support\Facades\Cache;

class TenantSettingsService
{
    /**
     * Obter valor de configuração (com cache)
     */
    public function get(string $key, Tenant $tenant, mixed $default = null): mixed
    {
        return $tenant->run(function () use ($key, $default) {
            return Cache::remember(
                "settings.{$key}",
                now()->addHour(),
                function () use ($key, $default) {
                    $setting = TenantSetting::where('key', $key)->first();

                    return $setting
                        ? $setting->getCastedValue()
                        : $default;
                }
            );
        });
    }

    /**
     * Definir valor de configuração
     */
    public function set(string $key, mixed $value, Tenant $tenant, ?string $type = null): void
    {
        $tenant->run(function () use ($key, $value, $type) {
            $setting = TenantSetting::firstOrNew(['key' => $key]);

            if (!$setting->exists && $type) {
                $setting->type = $type;
            }

            $setting->setCastedValue($value);
            $setting->save();

            // Limpar cache
            Cache::forget("settings.{$key}");
        });
    }

    /**
     * Obter múltiplas configurações
     */
    public function getMany(array $keys, Tenant $tenant): array
    {
        return $tenant->run(function () use ($keys) {
            $settings = TenantSetting::whereIn('key', $keys)->get();

            $result = [];
            foreach ($keys as $key) {
                /** @var TenantSetting|null $setting */
                $setting      = $settings->firstWhere('key', $key);
                $result[$key] = $setting ? $setting->getCastedValue() : null;
            }

            return $result;
        });
    }

    /**
     * Obter todas configurações públicas (para frontend)
     */
    public function getPublic(Tenant $tenant): array
    {
        return $tenant->run(function () {
            return Cache::remember('settings.public', now()->addDay(), function () {
                return TenantSetting::public()
                    ->get()
                    ->mapWithKeys(fn(TenantSetting $s) => [$s->key => $s->getCastedValue()])
                    ->toArray();
            });
        });
    }

    /**
     * Obter configurações de um módulo
     */
    public function getModuleSettings(string $module, Tenant $tenant): array
    {
        return $tenant->run(function () use ($module) {
            return TenantSetting::module($module)
                ->get()
                ->mapWithKeys(fn(TenantSetting $s) => [$s->key => $s->getCastedValue()])
                ->toArray();
        });
    }

    /**
     * Resetar para padrão
     */
    public function reset(string $key, Tenant $tenant): void
    {
        $tenant->run(function () use ($key) {
            TenantSetting::where('key', $key)->delete();
            Cache::forget("settings.{$key}");
        });
    }

    /**
     * Atualizar múltiplas configurações
     */
    public function updateMany(array $settings, Tenant $tenant): void
    {
        $tenant->run(function () use ($settings) {
            foreach ($settings as $key => $value) {
                $setting = TenantSetting::firstOrNew(['key' => $key]);
                $setting->setCastedValue($value);
                $setting->save();

                Cache::forget("settings.{$key}");
            }
        });
    }
}
