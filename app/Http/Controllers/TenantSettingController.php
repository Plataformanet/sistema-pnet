<?php

namespace App\Http\Controllers;

use App\Models\TenantSetting;
use Illuminate\Http\Request;
use App\Services\TenantSettingsService;
use Inertia\Inertia;

class TenantSettingController extends Controller
{
    public function __construct(
        private TenantSettingsService $tenantSettings
    ) {
    }

    /**
     * Exibir página de configurações
     */
    public function index()
    {
        $this->authorize('manage-settings');

        $tenantSettings = TenantSetting::all()
            ->groupBy('module')
            ->map(fn($group) => $group->map(fn($s) => [
                'key'         => $s->key,
                'value'       => $s->getCastedValue(),
                'type'        => $s->type,
                'description' => $s->description,
                'is_public'   => $s->is_public,
            ]));

        return Inertia::render('tenant/company/settings/Index', [
            'settings' => $tenantSettings,
        ]);
    }

    /**
     * Atualizar configurações
     */
    public function update(Request $request)
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'settings'         => 'required|array',
            'settings.*.key'   => 'required|string|exists:tenant_settings,key',
            'settings.*.value' => 'required',
        ]);

        foreach ($validated['settings'] as $data) {
            $this->settings->set($data['key'], $data['value']);
        }

        return back()->with('success', 'Configurações atualizadas');
    }

    /**
     * API: Configurações públicas para frontend
     */
    public function public()
    {
        return response()->json(
            $this->settings->getPublic()
        );
    }
}
