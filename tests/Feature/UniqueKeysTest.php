<?php

use App\Http\Requests\StoreServiceRequest;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\TenantSetting;
use App\Services\TenantSettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

test('tenant_settings.key é única, inclusive entre as configurações excluídas', function () {
    $this->tenant->run(function () {
        TenantSetting::create(['key' => 'teste.chave', 'value' => 'a', 'type' => 'string']);

        expect(fn () => TenantSetting::create(['key' => 'teste.chave', 'value' => 'b', 'type' => 'string']))
            ->toThrow(QueryException::class);

        TenantSetting::where('key', 'teste.chave')->delete();

        expect(fn () => TenantSetting::create(['key' => 'teste.chave', 'value' => 'c', 'type' => 'string']))
            ->toThrow(QueryException::class);
    });
});

test('a configuração usa soft delete', function () {
    $this->tenant->run(function () {
        $setting = TenantSetting::create(['key' => 'teste.soft', 'value' => 'a', 'type' => 'string']);
        $setting->delete();

        expect(TenantSetting::where('key', 'teste.soft')->exists())->toBeFalse()
            ->and(TenantSetting::withTrashed()->where('key', 'teste.soft')->exists())->toBeTrue();
    });
});

test('definir de novo uma configuração resetada reaproveita a linha excluída', function () {
    $service = app(TenantSettingsService::class);

    $service->set('teste.reset', 'primeiro', $this->tenant, 'string');
    $service->reset('teste.reset', $this->tenant);
    $service->set('teste.reset', 'segundo', $this->tenant);

    $this->tenant->run(function () {
        $rows = TenantSetting::withTrashed()->where('key', 'teste.reset')->get();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()->trashed())->toBeFalse()
            ->and($rows->first()->value)->toBe('segundo');
    });
});

test('updateMany também reativa a configuração excluída', function () {
    $service = app(TenantSettingsService::class);

    $service->set('teste.many', 'a', $this->tenant, 'string');
    $service->reset('teste.many', $this->tenant);
    $service->updateMany(['teste.many' => 'b'], $this->tenant);

    expect($service->get('teste.many', $this->tenant))->toBe('b');
});

test('permissions.name do catálogo central é único', function () {
    $central = DB::connection(config('tenancy.database.central_connection'));
    $moduleId = $central->table('modules')->value('id');
    $name = 'teste.unico.view';

    $row = ['module_id' => $moduleId, 'name' => $name, 'display_name' => 'Teste', 'created_at' => now(), 'updated_at' => now()];
    $central->table('permissions')->insert($row);

    expect(fn () => $central->table('permissions')->insert($row))->toThrow(QueryException::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function servicePayload(int $categoryId, array $overrides = []): array
{
    return array_merge([
        'service_category_id' => $categoryId,
        'name' => 'Registro de imóvel',
        'sku' => 'SRV-001',
        'cost_value' => 1000,
        'sell_value' => 2000,
        'fees' => 0,
        'status' => true,
    ], $overrides);
}

test('services.sku é único no banco, inclusive entre os serviços excluídos (RN-CAT-01)', function () {
    $this->tenant->run(function () {
        $category = ServiceCategory::create(['name' => 'Documentação']);
        $service = Service::create(servicePayload($category->id));

        expect(fn () => Service::create(servicePayload($category->id, ['name' => 'Outro'])))
            ->toThrow(QueryException::class);

        $service->delete();

        expect(fn () => Service::create(servicePayload($category->id, ['name' => 'Outro'])))
            ->toThrow(QueryException::class);
    });
});

test('o request de serviço recusa SKU repetido com mensagem em pt-BR', function () {
    $this->tenant->run(function () {
        $category = ServiceCategory::create(['name' => 'Documentação']);
        Service::create(servicePayload($category->id));

        $data = servicePayload($category->id, ['name' => 'Duplicado']);
        $request = StoreServiceRequest::create('/', 'POST', $data);
        $errors = Validator::make($data, $request->rules(), $request->messages())->errors();

        expect($errors->first('sku'))->toBe('O SKU já está cadastrado.');
    });
});
