<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Base dos cadastros de apoio do módulo de Documentações (bancos, cartórios,
 * tipos, etapas e serviços). Todos excluem por soft delete e podem ser
 * restaurados, para não quebrar propostas e orçamentos que já os referenciam.
 */
abstract class CatalogService
{
    public const PER_PAGE = 20;

    /**
     * @return class-string<Model>
     */
    abstract protected function getModel(): string;

    /**
     * Relações consumidoras somadas na coluna "Em uso" da listagem.
     *
     * @return array<int, string>
     */
    protected function usageRelations(): array
    {
        return [];
    }

    /**
     * Colunas pesquisadas pela busca da listagem.
     *
     * @return array<int, string>
     */
    protected function searchableColumns(): array
    {
        return ['name'];
    }

    /**
     * @param  array{search?: string|null, trashed?: bool|string|null}  $filters
     */
    public function findAll(array $filters, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(function () use ($filters) {
            $relations = $this->usageRelations();
            $search = $filters['search'] ?? null;

            return $this->getModel()::query()
                ->when(filter_var($filters['trashed'] ?? false, FILTER_VALIDATE_BOOLEAN), fn ($query) => $query->withTrashed())
                ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                    foreach ($this->searchableColumns() as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                }))
                ->withCount($relations)
                ->ordered()
                ->paginate(self::PER_PAGE)
                ->withQueryString()
                ->through(fn (Model $item) => array_merge($item->toArray(), [
                    'in_use_count' => collect($relations)
                        ->sum(fn (string $relation) => $item->getAttribute(str($relation)->snake().'_count')),
                ]));
        });
    }

    /**
     * Opções para os selects de outros módulos. Registros excluídos não são
     * oferecidos, exceto o que já está gravado no registro em edição.
     */
    public function options(Tenant $tenant, int|string|null $currentId = null): Collection
    {
        return $tenant->run(fn () => $this->getModel()::query()
            ->when($currentId, fn ($query) => $query->withTrashed()->where(
                fn ($query) => $query->whereNull('deleted_at')->orWhere('id', $currentId)
            ))
            ->ordered()
            ->get());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, Tenant $tenant): Model
    {
        return $tenant->run(fn () => $this->getModel()::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => $this->getModel()::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => (bool) $this->getModel()::findOrFail($id)->delete());
    }

    public function restore(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => $this->getModel()::onlyTrashed()->findOrFail($id)->restore());
    }

    public function findById(string $id, Tenant $tenant): Model
    {
        return $tenant->run(fn () => $this->getModel()::findOrFail($id));
    }
}
