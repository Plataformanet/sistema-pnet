<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    // public function share(Request $request): array
    // {
    //     return [
    //         ...parent::share($request),

    //     ];
    // }
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth'        => [
                'user' => $request->user(),
            ],
            // Compartilha dados do tenant com o Vue
            'tenant'      => $this->getTenantData(),

            'permissions' => function () use ($request) {
                if ($request->user()) {
                    return $request->user()->getAllPermissions()->pluck('name');
                }
                return [];
            },

            'flash'       => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'email'   => fn() => $request->session()->get('email'),
            ],
        ]);
    }

    private function getTenantData(): ?array
    {
        if (!tenancy()->initialized) {
            return null;
        }

        $tenant = tenant();

        return [
            'id'         => $tenant->getTenantKey(),
            'name'       => $tenant->name,
            'domain'     => $tenant->domains?->first()?->domain,
            'plan'       => $tenant->plan ?? null,
            'hasModules' => $tenant->hasModule($tenant->modulesByTenants()),
        ];
    }
}
