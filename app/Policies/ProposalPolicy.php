<?php

namespace App\Policies;

use App\Enums\DocumentOwner;
use App\Models\Proposal;
use App\Models\ProposalDocument;
use App\Models\User;

/**
 * Autorização por registro das propostas. A capacidade vem da permissão do
 * cargo (a mesma exigida pelo middleware da rota) e a visibilidade vem de
 * `Proposal::scopeVisibleTo`: parceiro, vendedor do imóvel e cliente só
 * acessam as propostas a que estão vinculados.
 */
class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('documents.proposals.view');
    }

    public function view(User $user, Proposal $proposal): bool
    {
        return $this->viewAny($user) && $proposal->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('documents.proposals.create');
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposals.edit') && $proposal->isVisibleTo($user);
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposals.delete') && $proposal->isVisibleTo($user);
    }

    public function manageTimeline(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposal_timeline.edit') && $proposal->isVisibleTo($user);
    }

    public function manageFinancial(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposal_financial.edit') && $proposal->isVisibleTo($user);
    }

    public function viewFinancial(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposal_financial.view') && $proposal->isVisibleTo($user);
    }

    public function uploadDocument(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposal_documents.create') && $proposal->isVisibleTo($user);
    }

    public function deleteDocument(User $user, Proposal $proposal): bool
    {
        return $user->checkPermissionTo('documents.proposal_documents.delete') && $proposal->isVisibleTo($user);
    }

    /**
     * Cargos externos só baixam os documentos que o cargo libera (ver
     * `DocumentOwner::visibleTo`); a equipe baixa qualquer documento.
     */
    public function downloadDocument(User $user, Proposal $proposal, ProposalDocument $document): bool
    {
        if (! $user->checkPermissionTo('documents.proposal_documents.view') || ! $proposal->isVisibleTo($user)) {
            return false;
        }

        return in_array($document->owner, DocumentOwner::visibleTo($user), true);
    }

    /**
     * O comprovante de pagamento pode ser anexado por quem gerencia o
     * financeiro ou pelo próprio proponente, somente na proposta dele.
     */
    public function uploadProof(User $user, Proposal $proposal): bool
    {
        if ($this->manageFinancial($user, $proposal)) {
            return true;
        }

        return $proposal->applicants()->where('applicants.user_id', $user->id)->exists();
    }

    public function viewAttachment(User $user, Proposal $proposal): bool
    {
        return $this->viewFinancial($user, $proposal) || $this->uploadProof($user, $proposal);
    }

    public function print(User $user, Proposal $proposal): bool
    {
        return $this->view($user, $proposal);
    }
}
