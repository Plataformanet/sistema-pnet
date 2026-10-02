<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usuários externos às propostas (Parceiro, Vendedor do imóvel e Cliente) só
 * enxergam o que for da proposta: telas internas sem permissão própria
 * (Dashboard e CRM) os mandam para a lista de propostas.
 */
class RedirectProposalRestrictedUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasOnlyProposalRestrictedRoles()) {
            return redirect()->route('tenant.documents.proposals.list');
        }

        return $next($request);
    }
}
