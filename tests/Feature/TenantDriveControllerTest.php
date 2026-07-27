<?php

use App\Enums\DocumentTypeDriveEnum;
use App\Http\Controllers\TenantDriveController;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\User;
use Illuminate\Http\Request;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->user = $this->tenant->run(fn () => User::factory()->create(['name' => 'Dono']));

    $this->actingAs($this->user);
});

/**
 * Cria a pasta e o drive que a representa (document_type = folder).
 * Deve ser chamado dentro de um $tenant->run().
 */
function createFolderWithDrive(string $name, ?int $parentId = null): DriveFolder
{
    $folder = DriveFolder::create(['name' => $name, 'parent_id' => $parentId]);

    Drive::create([
        'user_id' => test()->user->id,
        'drive_folder_id' => $folder->id,
        'name' => $folder->name,
        'document_path' => 'drive/'.$folder->getPath(),
        'document_size' => 0,
        'document_type' => DocumentTypeDriveEnum::FOLDER->value,
        'modified_by' => test()->user->id,
    ]);

    return $folder;
}

/**
 * Invoca o index do drive simulando a query string da requisição e devolve as
 * props renderizadas pelo Inertia.
 *
 * @param  array<string, mixed>  $query
 * @return array<string, mixed>
 */
function driveIndexProps(array $query = []): array
{
    $request = Request::create('/drive', 'GET', $query, [], [], ['HTTP_X_INERTIA' => 'true']);

    app()->instance('request', $request);

    $response = app(TenantDriveController::class)->index();

    return $response->toResponse($request)->getData(true)['props'];
}

test('index lista o conteúdo da pasta recebendo apenas folder_id (clique no breadcrumb)', function () {
    $folderId = $this->tenant->run(function () {
        $root = createFolderWithDrive('Arquivos do escritório');
        createFolderWithDrive('Contas', $root->id);

        return $root->id;
    });

    $props = driveIndexProps(['folder_id' => $folderId]);

    expect(collect($props['drives'])->pluck('name')->all())->toBe(['Contas'])
        ->and(collect($props['folders'])->pluck('name')->all())->toBe(['Arquivos do escritório']);
});

test('index monta o breadcrumb completo ao entrar numa subpasta', function () {
    $subFolderId = $this->tenant->run(function () {
        $root = createFolderWithDrive('Arquivos do escritório');
        $sub = createFolderWithDrive('Contas', $root->id);

        return $sub->id;
    });

    $props = driveIndexProps(['folder_id' => $subFolderId]);

    expect(collect($props['folders'])->pluck('name')->all())
        ->toBe(['Arquivos do escritório', 'Contas']);
});

test('index lista as pastas raiz quando não recebe folder_id', function () {
    $this->tenant->run(function () {
        $root = createFolderWithDrive('Arquivos do escritório');
        createFolderWithDrive('Contas', $root->id);
    });

    $props = driveIndexProps();

    expect(collect($props['drives'])->pluck('name')->all())->toBe(['Arquivos do escritório'])
        ->and($props['folders'])->toBe([]);
});
