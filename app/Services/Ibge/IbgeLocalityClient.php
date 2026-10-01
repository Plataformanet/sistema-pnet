<?php

namespace App\Services\Ibge;

use App\Enums\SupportedState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Municípios por UF da API pública de localidades do IBGE (fonte dos códigos
 * usados pela API de emolumentos). Chamado pelo backend e cacheado por UF.
 */
class IbgeLocalityClient
{
    public const CACHE_DAYS = 30;

    /**
     * Usa o store direto (`Cache::store()`), sem as tags que o
     * CacheTenancyBootstrapper aplica pela facade: o store `database` não
     * suporta tags, e a lista do IBGE é igual para todos os tenants.
     *
     * @return Collection<int, array{ibge_code: int, name: string}>
     */
    public function municipalities(SupportedState $state): Collection
    {
        $items = Cache::store()->remember("ibge.municipalities.{$state->value}", now()->addDays(self::CACHE_DAYS), function () use ($state) {
            return Http::baseUrl((string) config('services.ibge.url'))
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout((int) config('services.ibge.timeout'))
                ->retry([200, 1000])
                ->get("estados/{$state->value}/municipios")
                ->throw()
                ->collect()
                ->map(fn (array $municipality) => [
                    'ibge_code' => (int) $municipality['id'],
                    'name' => (string) $municipality['nome'],
                ])
                ->sortBy('name')
                ->values()
                ->all();
        });

        return collect($items);
    }
}
