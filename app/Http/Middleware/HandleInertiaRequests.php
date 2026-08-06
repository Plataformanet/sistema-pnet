<?php

namespace App\Http\Middleware;

use App\Models\TenantSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'auth' => [
                'user' => $request->user(),
            ],
            // Compartilha dados do tenant com o Vue
            'tenant' => $this->getTenantData(),

            'permissions' => function () use ($request) {
                if ($request->user()) {
                    return $request->user()->getAllPermissions()->pluck('name');
                }

                return [];
            },

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'email' => fn () => $request->session()->get('email'),
            ],
        ]);
    }

    private function getTenantData(): ?array
    {
        if (! tenancy()->initialized) {
            return null;
        }

        $tenant = tenant();

        $logoPath = TenantSetting::where('key', 'company.logo_path')->value('value');
        $logoUrl = null;

        if ($logoPath && Storage::disk(config('bucket.disk'))->exists($logoPath)) {
            $logoUrl = route('tenant.settings.company.logo').'?v='.time();
        }

        $companyName = TenantSetting::where('key', 'company.trade_name')->value('value')
            ?: TenantSetting::where('key', 'company.name')->value('value')
            ?: $tenant->name;

        return [
            'id' => $tenant->getTenantKey(),
            'name' => $companyName,
            'logoUrl' => $logoUrl,
            'domain' => $tenant->domains?->first()?->domain,
            'plan' => $tenant->plan ?? null,
            'hasModules' => $tenant->hasModule($tenant->modulesByTenants()),
        ];
    }
}
