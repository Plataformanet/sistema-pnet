<?php

namespace App\Http\Requests;

use App\Models\RegistryProtocol;

class UpdateRegistryProtocolRequest extends StoreRegistryProtocolRequest
{
    protected function ignoreId(): ?string
    {
        return $this->route('id');
    }

    protected function currentProposalId(): ?int
    {
        $proposalId = RegistryProtocol::whereKey($this->route('id'))->value('proposal_id');

        return $proposalId !== null ? (int) $proposalId : null;
    }
}
