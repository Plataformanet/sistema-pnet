<?php

namespace App\Services;

use App\Models\ItbiMunicipality;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ItbiMunicipalityService
{
    public const PER_PAGE = 20;

    /**
     * @param  array{search?: string|null}  $filters
     */
    public function findAll(array $filters, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(fn () => ItbiMunicipality::query()
            ->withCount('brackets')
            ->with('rate:id,itbi_municipality_id')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('state')
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (ItbiMunicipality $municipality) => array_merge($municipality->only(['id', 'name', 'ibge_code', 'full_rate']), [
                'state' => $municipality->state->value,
                'module' => ['value' => $municipality->module->value, 'label' => $municipality->module->label()],
                'configured' => $municipality->module->usesBrackets()
                    ? $municipality->brackets_count > 0 && $municipality->full_rate !== null
                    : $municipality->rate !== null,
            ])));
    }

    public function findById(string $id, Tenant $tenant): ItbiMunicipality
    {
        return $tenant->run(fn () => ItbiMunicipality::with(['rate', 'brackets'])->findOrFail($id));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, Tenant $tenant): ItbiMunicipality
    {
        return $tenant->run(fn () => ItbiMunicipality::create($data));
    }

    /**
     * Um município tem um único módulo: ao trocá-lo, os parâmetros do módulo
     * anterior (alíquotas ou faixas) são apagados junto, na mesma transação.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $id, Tenant $tenant): ItbiMunicipality
    {
        return $tenant->run(function () use ($data, $id) {
            return DB::transaction(function () use ($data, $id) {
                $municipality = ItbiMunicipality::lockForUpdate()->findOrFail($id);
                $previousModule = $municipality->module;

                $municipality->update($data);

                if ($municipality->module !== $previousModule) {
                    $municipality->rate()->delete();
                    $municipality->brackets()->delete();
                }

                return $municipality;
            });
        });
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => (bool) ItbiMunicipality::findOrFail($id)->delete());
    }

    /**
     * Grava só os campos do módulo do município; os demais ficam nulos.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRate(array $data, string $id, Tenant $tenant): void
    {
        $tenant->run(function () use ($data, $id) {
            $municipality = ItbiMunicipality::findOrFail($id);
            $fields = $municipality->module->requiredFields();

            $values = collect(['financed_rate', 'financed_cap_amount', 'first_property_financed_rate', 'other_property_financed_rate'])
                ->mapWithKeys(fn (string $field) => [$field => in_array($field, $fields, true) ? $data[$field] : null])
                ->put('own_funds_rate', $data['own_funds_rate'])
                ->all();

            $municipality->rate()->updateOrCreate([], $values);
        });
    }

    /**
     * Substitui o conjunto de faixas (módulo 02) e a alíquota cheia usada em
     * SFI e no registro em geral.
     *
     * @param  array{full_rate: float|string, brackets: array<int, array<string, mixed>>}  $data
     */
    public function updateBrackets(array $data, string $id, Tenant $tenant): void
    {
        $tenant->run(function () use ($data, $id) {
            DB::transaction(function () use ($data, $id) {
                $municipality = ItbiMunicipality::lockForUpdate()->findOrFail($id);

                $municipality->update(['full_rate' => $data['full_rate']]);
                $municipality->brackets()->delete();
                $municipality->brackets()->createMany(collect($data['brackets'])->map(fn (array $bracket) => [
                    'min_value' => $bracket['min_value'],
                    'max_value' => $bracket['max_value'],
                    'rate' => $bracket['rate'],
                    'discount_amount' => $bracket['discount_amount'] ?? 0,
                ])->all());
            });
        });
    }
}
