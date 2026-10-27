<?php

namespace App\Services;

use App\Enums\RegistryProtocolPositionEnum;
use App\Models\Applicant;
use App\Models\Proposal;
use App\Models\RegistryProtocol;
use App\Models\Seller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Protocolos de registro do imóvel no cartório, vinculados ou não a uma
 * proposta.
 */
class RegistryProtocolService
{
    public const PER_PAGE = 15;

    public const SEARCH_LIMIT = 20;

    /**
     * @param  array{status?: string|null, position?: int|string|null, search?: string|null, proposal_id?: int|string|null}  $filters
     */
    public function paginate(array $filters, Tenant $tenant): LengthAwarePaginator
    {
        return $tenant->run(fn () => $this->filteredQuery($filters)
            ->when($filters['position'] ?? null, fn (Builder $query, int|string $position) => $query->where('position', $position))
            ->when(
                $filters['status'] ?? 'open',
                fn (Builder $query, string $status) => match ($status) {
                    'closed' => $query->closed(),
                    'all' => $query,
                    default => $query->open(),
                },
            )
            ->with('proposal:id,deleted_at')
            ->orderByDesc('entered_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (RegistryProtocol $protocol) => $this->listRow($protocol)));
    }

    /**
     * Quantidade de protocolos por aba (Em aberto, Encerrados, Todos), numa
     * única consulta agrupada por posição. Respeita a busca e o filtro de
     * proposta, mas não a aba nem a posição selecionadas.
     *
     * @param  array{search?: string|null, proposal_id?: int|string|null}  $filters
     * @return array{open: int, closed: int, all: int}
     */
    public function tabCounts(array $filters, Tenant $tenant): array
    {
        return $tenant->run(function () use ($filters) {
            $counts = $this->filteredQuery($filters)
                ->selectRaw('position, count(*) as total')
                ->groupBy('position')
                ->pluck('total', 'position');

            $closed = (int) $counts->only(RegistryProtocolPositionEnum::closedValues())->sum();
            $all = (int) $counts->sum();

            return ['open' => $all - $closed, 'closed' => $closed, 'all' => $all];
        });
    }

    /**
     * Protocolo para o formulário de edição, com o rótulo da proposta
     * vinculada (o Combobox precisa dele para exibir a seleção atual).
     *
     * @return array<string, mixed>
     */
    public function findForEdit(string $id, Tenant $tenant): array
    {
        return $tenant->run(function () use ($id) {
            $protocol = RegistryProtocol::with('proposal.applicants.contact:id,name_corporatereason')->findOrFail($id);

            return [
                ...$protocol->only(['id', 'proposal_id', 'protocol_number', 'buyer_name', 'seller_name']),
                'entered_at' => $protocol->entered_at?->toDateString(),
                'exited_at' => $protocol->exited_at?->toDateString(),
                'position' => $protocol->position->value,
                'proposal' => $protocol->proposal !== null ? $this->toProposalOption($protocol->proposal) : null,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, Tenant $tenant): RegistryProtocol
    {
        return $tenant->run(fn () => RegistryProtocol::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => RegistryProtocol::findOrFail($id)->update($data));
    }

    public function delete(string $id, Tenant $tenant): bool
    {
        return $tenant->run(fn () => (bool) RegistryProtocol::findOrFail($id)->delete());
    }

    /**
     * Propostas que o usuário vê, buscadas por nº ou nome do proponente
     * (máx. 20), no formato do ComboboxRemote. Diferente da busca da
     * calculadora, não exclui propostas que já têm emolumento.
     *
     * @return Collection<int, array{id: int, number: string, applicants: array<int, string>, name_corporatereason: string}>
     */
    public function searchProposals(?string $search, User $user, Tenant $tenant): Collection
    {
        $search = trim((string) $search);
        $digits = preg_replace('/\D/', '', $search);

        return $tenant->run(fn () => Proposal::query()
            ->visibleTo($user)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search, $digits) {
                if ($digits !== '' && strlen($digits) <= 9) {
                    $query->orWhere('proposals.id', (int) $digits);
                }

                $query->orWhereHas('applicants.contact', fn (Builder $query) => $query->where('name_corporatereason', 'like', '%'.$search.'%'));
            }))
            ->with('applicants.contact:id,name_corporatereason')
            ->latest('id')
            ->limit(self::SEARCH_LIMIT)
            ->get()
            ->map(fn (Proposal $proposal) => $this->toProposalOption($proposal))
            ->values());
    }

    /**
     * Proposta pré-selecionada no cadastro (link "Cadastrar protocolo" da
     * timeline). Proposta inexistente ou fora do alcance do usuário é ignorada.
     *
     * @return array{id: int, number: string, applicants: array<int, string>, name_corporatereason: string}|null
     */
    public function proposalOption(int|string|null $proposalId, User $user, Tenant $tenant): ?array
    {
        if ($proposalId === null) {
            return null;
        }

        return $tenant->run(function () use ($proposalId, $user) {
            $proposal = Proposal::query()
                ->visibleTo($user)
                ->with('applicants.contact:id,name_corporatereason')
                ->find($proposalId);

            return $proposal !== null ? $this->toProposalOption($proposal) : null;
        });
    }

    /**
     * Proposta visível ao usuário para o preenchimento das partes; propostas
     * inexistentes, excluídas ou fora do alcance do usuário dão 404.
     */
    public function findProposalForParties(string $proposalId, User $user, Tenant $tenant): Proposal
    {
        return $tenant->run(fn () => Proposal::query()
            ->visibleTo($user)
            ->with(['applicants:id,contact_id', 'applicants.contact:id,name_corporatereason', 'sellers:id,contact_id', 'sellers.contact:id,name_corporatereason'])
            ->findOrFail($proposalId));
    }

    /**
     * Comprador e vendedor sugeridos para o protocolo: todos os proponentes e
     * todos os vendedores da proposta, separados por vírgula. O usuário pode
     * editar os nomes antes de salvar.
     *
     * @return array{buyer_name: string, seller_name: string|null}
     */
    public function partiesFor(Proposal $proposal): array
    {
        $proposal->loadMissing(['applicants.contact:id,name_corporatereason', 'sellers.contact:id,name_corporatereason']);

        $sellers = $proposal->sellers->map(fn (Seller $seller) => $seller->contact?->name_corporatereason)->filter()->join(', ');

        return [
            'buyer_name' => $proposal->applicants->map(fn (Applicant $applicant) => $applicant->contact?->name_corporatereason)->filter()->join(', '),
            'seller_name' => $sellers !== '' ? $sellers : null,
        ];
    }

    /**
     * Painel do protocolo na timeline da proposta: o protocolo mais recente e
     * quantos outros (anteriores) a proposta tem.
     *
     * @return array<string, mixed>|null
     */
    public function panelFor(Proposal $proposal, Tenant $tenant): ?array
    {
        return $tenant->run(function () use ($proposal) {
            $latest = $proposal->latestRegistryProtocol()->first();

            if ($latest === null) {
                return null;
            }

            return [
                ...$this->listRow($latest),
                'previous_count' => $proposal->registryProtocols()->count() - 1,
            ];
        });
    }

    /**
     * @param  array{search?: string|null, proposal_id?: int|string|null}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return RegistryProtocol::query()
            ->search($filters['search'] ?? null)
            ->when($filters['proposal_id'] ?? null, fn (Builder $query, int|string $proposalId) => $query->where('proposal_id', $proposalId));
    }

    /**
     * @return array{id: int, number: string, applicants: array<int, string>, name_corporatereason: string}
     */
    private function toProposalOption(Proposal $proposal): array
    {
        $applicants = $proposal->applicants
            ->map(fn (Applicant $applicant) => $applicant->contact?->name_corporatereason)
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $proposal->id,
            'number' => $proposal->number,
            'applicants' => $applicants,
            'name_corporatereason' => trim('Nº '.$proposal->number.($applicants !== [] ? ' — '.implode(', ', $applicants) : '')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function listRow(RegistryProtocol $protocol): array
    {
        return [
            'id' => $protocol->id,
            'proposal' => $protocol->proposal_id !== null ? [
                'id' => $protocol->proposal_id,
                'number' => str_pad((string) $protocol->proposal_id, 5, '0', STR_PAD_LEFT),
                'deleted' => $protocol->relationLoaded('proposal') && $protocol->proposal?->trashed() === true,
            ] : null,
            'protocol_number' => $protocol->protocol_number,
            'buyer_name' => $protocol->buyer_name,
            'seller_name' => $protocol->seller_name,
            'entered_at' => $protocol->entered_at?->toDateString(),
            'exited_at' => $protocol->exited_at?->toDateString(),
            'position' => $protocol->position->toArray(),
        ];
    }
}
