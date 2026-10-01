<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TenantProposalSearchController extends Controller
{
    /**
     * Propostas sem emolumento vinculado, com busca por nº ou proponente
     * (máx. 20). Formato do ComboboxRemote: `[{ id, name_corporatereason }]`.
     */
    public function __invoke(Request $request)
    {
        try {
            $search = trim((string) $request->query('search'));
            $digits = preg_replace('/\D/', '', $search);
            $user = $request->user();

            $proposals = tenant()->run(fn () => Proposal::query()
                ->withoutFeeEstimate()
                ->visibleTo($user)
                ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search, $digits) {
                    if ($digits !== '' && strlen($digits) <= 9) {
                        $query->orWhere('proposals.id', (int) $digits);
                    }

                    $query->orWhereHas('applicants.contact', fn (Builder $query) => $query->where('name_corporatereason', 'like', '%'.$search.'%'));
                }))
                ->with('applicants.contact:id,name_corporatereason')
                ->latest('id')
                ->limit(20)
                ->get());

            return response()->json($proposals->map(fn (Proposal $proposal) => [
                'id' => $proposal->id,
                'name_corporatereason' => 'Nº '.$proposal->number.' — '.$proposal->applicants->map(fn ($applicant) => $applicant->contact->name_corporatereason)->join(', '),
            ])->values());
        } catch (\Throwable $th) {
            Log::error('Erro ao buscar propostas: '.$th->getMessage());

            return response()->json(['message' => 'Erro ao buscar propostas.'], 500);
        }
    }
}
