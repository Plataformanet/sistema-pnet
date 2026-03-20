<?php

namespace App\Services;

use App\Models\TenantSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Obter valor de configuração (com cache)
     */
    public function get(string $key, mixed $default = null): mixed
    {
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
    }

    /**
     * Definir valor de configuração
     */
    public function set(string $key, mixed $value, ?string $type = null): void
    {
        $setting = TenantSetting::firstOrNew(['key' => $key]);

        if (!$setting->exists && $type) {
            $setting->type = $type;
        }

        $setting->setCastedValue($value);
        $setting->save();

        // Limpar cache
        Cache::forget("settings.{$key}");
    }

    /**
     * Obter múltiplas configurações
     */
    public function getMany(array $keys): array
    {
        $settings = TenantSetting::whereIn('key', $keys)->get();

        $result = [];
        foreach ($keys as $key) {
            $setting = $settings->firstWhere('key', $key);
            $result[$key] = $setting ? $setting->getCastedValue() : null;
        }

        return $result;
    }

    /**
     * Obter todas configurações públicas (para frontend)
     */
    public function getPublic(): array
    {
        return Cache::remember('settings.public', now()->addDay(), function () {
            return TenantSetting::public()
                ->get()
                ->mapWithKeys(fn($s) => [$s->key => $s->getCastedValue()])
                ->toArray();
        });
    }

    /**
     * Obter configurações de um módulo
     */
    public function getModuleSettings(string $module): array
    {
        return TenantSetting::module($module)
            ->get()
            ->mapWithKeys(fn($s) => [$s->key => $s->getCastedValue()])
            ->toArray();
    }

    /**
     * Resetar para padrão
     */
    public function reset(string $key): void
    {
        TenantSetting::where('key', $key)->delete();
        Cache::forget("settings.{$key}");
    }

    /**
     * Atualizar múltiplas configurações
     */
    public function updateMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
    }
}
