<?php

use App\Enums\DocumentTypeDriveEnum;
use App\Enums\PermissionTypeDriveEnum;
use App\Http\Requests\ForceDeleteRequest;
use App\Http\Requests\ForceDeleteTrashRequest;
use App\Http\Requests\StoreAccessPermissionDriveRequest;
use App\Http\Requests\StoreDriveRequest;
use App\Http\Requests\UpdateDriveRequest;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\DriveLog;
use App\Models\DrivePermission;
use App\Models\User;
use App\Services\DriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->user = $this->tenant->run(fn () => User::factory()->create(['name' => 'Dono']));

    $this->actingAs($this->user);

    config(['drive.disk' => 'public']);
    Storage::fake('public');
});

/**
 * Cria uma pasta. Deve ser chamado dentro de um $tenant->run().
 *
 * @param  array<string, mixed>  $attrs
 */
function makeFolder(array $attrs = []): DriveFolder
{
    return DriveFolder::create(array_merge(['name' => 'Pasta', 'parent_id' => null], $attrs));
}

/**
 * Cria o "drive" que representa a pasta (document_type = folder).
 * Deve ser chamado dentro de um $tenant->run().
 *
 * @param  array<string, mixed>  $attrs
 */
function makeFolderDrive(DriveFolder $folder, array $attrs = []): Drive
{
    return Drive::create(array_merge([
        'user_id' => test()->user->id,
        'drive_folder_id' => $folder->id,
        'name' => $folder->name,
        'document_path' => 'drive/'.$folder->getPath(),
        'document_size' => 0,
        'document_type' => DocumentTypeDriveEnum::FOLDER->value,
        'modified_by' => test()->user->id,
    ], $attrs));
}

/**
 * Cria um arquivo dentro de uma pasta. Deve ser chamado dentro de um $tenant->run().
 *
 * @param  array<string, mixed>  $attrs
 */
function makeFile(DriveFolder $folder, array $attrs = []): Drive
{
    return Drive::create(array_merge([
        'user_id' => test()->user->id,
        'drive_folder_id' => $folder->id,
        'name' => 'arquivo.pdf',
        'document_path' => 'drive/'.$folder->getPath().'/arquivo.pdf',
        'document_size' => 1024,
        'document_type' => DocumentTypeDriveEnum::PDF->value,
        'modified_by' => test()->user->id,
    ], $attrs));
}

// ---------------------------------------------------------------------------
// Listagens
// ---------------------------------------------------------------------------

test('findAll retorna apenas pastas raiz com permission_attrs', function () {
    $this->tenant->run(function () {
        $root = makeFolder(['name' => 'Raiz']);
        makeFolderDrive($root);

        $child = makeFolder(['name' => 'Sub', 'parent_id' => $root->id]);
        makeFolderDrive($child); // tem parent: não deve aparecer na raiz
    });

    $drives = $this->tenant->run(fn () => app(DriveService::class)->findAll($this->tenant)->collect());

    expect($drives)->toHaveCount(1)
        ->and($drives->first()->name)->toBe('Raiz')
        ->and($drives->first()->permission_attrs)->toBeArray()
        ->and($drives->first()->permission_attrs['has_access'])->toBeTrue();
});

test('findByFolder retorna subpastas e arquivos da pasta', function () {
    $folderId = $this->tenant->run(function () {
        $root = makeFolder(['name' => 'Raiz']);
        makeFolderDrive($root);

        $sub = makeFolder(['name' => 'Sub', 'parent_id' => $root->id]);
        makeFolderDrive($sub);

        makeFile($root, ['name' => 'doc.pdf']);

        return $root->id;
    });

    $drives = $this->tenant->run(
        fn () => app(DriveService::class)->findByFolder((string) $folderId, $this->tenant)->collect()
    );

    expect($drives->pluck('name')->sort()->values()->all())->toBe(['Sub', 'doc.pdf']);
});

test('search filtra os drives pelo nome', function () {
    $this->tenant->run(function () {
        $folder = makeFolder();
        makeFolderDrive($folder);
        makeFile($folder, ['name' => 'relatorio-2026.pdf']);
        makeFile($folder, ['name' => 'contrato.pdf']);
    });

    $drives = $this->tenant->run(
        fn () => app(DriveService::class)->search('relatorio', $this->tenant)->collect()
    );

    expect($drives)->toHaveCount(1)
        ->and($drives->first()->name)->toBe('relatorio-2026.pdf');
});

test('findByTrash retorna somente drives na lixeira', function () {
    $this->tenant->run(function () {
        $folder = makeFolder();
        makeFile($folder, ['name' => 'vivo.pdf']);
        $trashed = makeFile($folder, ['name' => 'lixo.pdf']);
        $trashed->delete();
    });

    $drives = $this->tenant->run(fn () => app(DriveService::class)->findByTrash($this->tenant)->collect());

    expect($drives)->toHaveCount(1)
        ->and($drives->first()->name)->toBe('lixo.pdf');
});

// ---------------------------------------------------------------------------
// Exclusão / restauração (soft delete)
// ---------------------------------------------------------------------------

test('delete faz soft delete do arquivo e registra modified_by', function () {
    $file = $this->tenant->run(fn () => makeFile(makeFolder()));

    app(DriveService::class)->delete((string) $file->id, $this->tenant);

    $this->tenant->run(function () use ($file) {
        $fresh = Drive::withTrashed()->find($file->id);

        expect($fresh->trashed())->toBeTrue()
            ->and($fresh->modified_by)->toBe($this->user->id);
    });
});

test('deleteSelected faz soft delete de vários drives', function () {
    [$a, $b] = $this->tenant->run(function () {
        $folder = makeFolder();

        return [makeFile($folder, ['name' => 'a.pdf']), makeFile($folder, ['name' => 'b.pdf'])];
    });

    app(DriveService::class)->deleteSelected([$a->id, $b->id], $this->tenant);

    $this->tenant->run(function () use ($a, $b) {
        expect(Drive::whereIn('id', [$a->id, $b->id])->count())->toBe(0)
            ->and(Drive::withTrashed()->whereIn('id', [$a->id, $b->id])->count())->toBe(2);
    });
});

test('restore restaura um arquivo da lixeira', function () {
    $file = $this->tenant->run(function () {
        $f = makeFile(makeFolder());
        $f->delete();

        return $f;
    });

    app(DriveService::class)->restore((string) $file->id, DocumentTypeDriveEnum::PDF->value, $this->tenant);

    $this->tenant->run(fn () => expect(Drive::find($file->id))->not->toBeNull());
});

test('restore de pasta restaura a pasta e seus drives filhos', function () {
    $folder = $this->tenant->run(function () {
        $folder = makeFolder(['name' => 'Lixo']);
        $folderDrive = makeFolderDrive($folder);
        $file = makeFile($folder);

        $file->delete();
        $folderDrive->delete();
        $folder->delete();

        return $folder;
    });

    app(DriveService::class)->restore((string) $folder->id, DocumentTypeDriveEnum::FOLDER->value, $this->tenant);

    $this->tenant->run(function () use ($folder) {
        expect(DriveFolder::find($folder->id))->not->toBeNull()
            ->and(Drive::where('drive_folder_id', $folder->id)->count())->toBe(2);
    });
});

// ---------------------------------------------------------------------------
// Upload / rename
// ---------------------------------------------------------------------------

test('store faz upload do arquivo, cria o drive e grava no disco', function () {
    $folder = $this->tenant->run(fn () => makeFolder(['name' => 'Uploads']));

    $drive = $this->tenant->run(fn () => app(DriveService::class)->store(
        formRequest(StoreDriveRequest::class, [
            'drive_id' => 1,
            'user_id' => $this->user->id,
            'folder_id' => $folder->id,
            'modified_at' => ['2026-01-15 10:00:00'],
        ], [
            'documents' => [UploadedFile::fake()->create('relatorio.pdf', 50, 'application/pdf')],
        ]),
        $this->tenant
    ));

    expect($drive->name)->toBe('relatorio.pdf');

    Storage::disk('public')->assertExists('drive/Uploads/relatorio.pdf');

    $this->tenant->run(fn () => expect(
        Drive::where('drive_folder_id', $folder->id)->where('name', 'relatorio.pdf')->exists()
    )->toBeTrue());
});

test('update renomeia um arquivo e move o conteúdo no disco', function () {
    $folder = $this->tenant->run(fn () => makeFolder(['name' => 'Docs']));

    Storage::disk('public')->put('drive/Docs/antigo.pdf', 'conteudo');

    $file = $this->tenant->run(fn () => makeFile($folder, [
        'name' => 'antigo.pdf',
        'document_path' => 'drive/Docs/antigo.pdf',
    ]));

    $this->tenant->run(fn () => app(DriveService::class)->update(
        formRequest(UpdateDriveRequest::class, [
            'id' => $file->id,
            'type_drive' => 0, // arquivo
            'name' => 'novo',
            'drive_type' => DocumentTypeDriveEnum::PDF->value,
        ]),
        $this->tenant
    ));

    $this->tenant->run(function () use ($file) {
        $fresh = Drive::find($file->id);

        expect($fresh->name)->toBe('novo.pdf')
            ->and($fresh->document_path)->toBe('drive/Docs/novo.pdf');
    });

    Storage::disk('public')->assertExists('drive/Docs/novo.pdf');
    Storage::disk('public')->assertMissing('drive/Docs/antigo.pdf');
});

// ---------------------------------------------------------------------------
// Download
// ---------------------------------------------------------------------------

test('download transmite o arquivo do disco quando URL assinada está desligada', function () {
    config(['drive.signed_urls' => false]);

    $folder = $this->tenant->run(fn () => makeFolder(['name' => 'Docs']));

    Storage::disk('public')->put('drive/Docs/doc.pdf', 'conteudo');

    $file = $this->tenant->run(fn () => makeFile($folder, [
        'name' => 'doc.pdf',
        'document_path' => 'drive/Docs/doc.pdf',
    ]));

    $response = app(DriveService::class)->download((string) $file->id, $this->tenant);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('content-disposition'))->toContain('doc.pdf');
});

test('download aborta com 404 quando o arquivo não existe no disco', function () {
    $file = $this->tenant->run(fn () => makeFile(makeFolder(), [
        'document_path' => 'drive/Docs/sumiu.pdf',
    ]));

    app(DriveService::class)->download((string) $file->id, $this->tenant);
})->throws(NotFoundHttpException::class);

// ---------------------------------------------------------------------------
// Exclusão permanente (force delete)
// ---------------------------------------------------------------------------

test('forceDelete remove o arquivo do disco, cria log e exclui definitivamente', function () {
    $folder = $this->tenant->run(fn () => makeFolder(['name' => 'Docs']));

    Storage::disk('public')->put('drive/Docs/del.pdf', 'x');

    $file = $this->tenant->run(function () use ($folder) {
        $f = makeFile($folder, ['name' => 'del.pdf', 'document_path' => 'drive/Docs/del.pdf']);
        $f->delete();

        return $f;
    });

    $this->tenant->run(fn () => app(DriveService::class)->forceDelete(
        formRequest(ForceDeleteRequest::class, [
            'id' => $file->id,
            'drive_type' => DocumentTypeDriveEnum::PDF->value,
            'confirm_delete' => 1,
        ]),
        $this->tenant
    ));

    Storage::disk('public')->assertMissing('drive/Docs/del.pdf');

    $this->tenant->run(function () use ($file) {
        expect(Drive::withTrashed()->find($file->id))->toBeNull()
            ->and(DriveLog::count())->toBe(1);
    });
});

test('clearTrash exclui definitivamente os drives selecionados', function () {
    $folder = $this->tenant->run(fn () => makeFolder(['name' => 'Docs']));

    Storage::disk('public')->put('drive/Docs/c.pdf', 'x');

    $file = $this->tenant->run(function () use ($folder) {
        $f = makeFile($folder, ['name' => 'c.pdf', 'document_path' => 'drive/Docs/c.pdf']);
        $f->delete();

        return $f;
    });

    $this->tenant->run(fn () => app(DriveService::class)->clearTrash(
        formRequest(ForceDeleteTrashRequest::class, [
            'confirm_delete' => 1,
            'selected_drives' => [
                ['id' => $file->id, 'drive_type' => DocumentTypeDriveEnum::PDF->value],
            ],
        ]),
        $this->tenant
    ));

    Storage::disk('public')->assertMissing('drive/Docs/c.pdf');

    $this->tenant->run(fn () => expect(Drive::withTrashed()->find($file->id))->toBeNull());
});

// ---------------------------------------------------------------------------
// Permissões de acesso
// ---------------------------------------------------------------------------

test('userCanAccess libera o proprietário do drive', function () {
    $drive = $this->tenant->run(fn () => makeFile(makeFolder(), ['user_id' => $this->user->id]));

    $this->tenant->run(fn () => expect(
        app(DriveService::class)->userCanAccess($drive, $this->user)
    )->toBeTrue());
});

test('userCanAccess bloqueia não-proprietário quando há restrição somente proprietário', function () {
    [$drive, $other] = $this->tenant->run(function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $drive = makeFile(makeFolder(), ['user_id' => $owner->id]);
        $drive->drivePermissions()->create([
            'user_id' => $owner->id,
            'permission_type' => PermissionTypeDriveEnum::SOMENTE_PROPRIETARIO->value,
        ]);

        return [$drive->load('drivePermissions'), $other];
    });

    $this->tenant->run(fn () => expect(
        app(DriveService::class)->userCanAccess($drive, $other)
    )->toBeFalse());
});

test('userCanAccess libera usuário com acesso total explícito mesmo sob restrição', function () {
    [$drive, $other] = $this->tenant->run(function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $drive = makeFile(makeFolder(), ['user_id' => $owner->id]);
        $drive->drivePermissions()->create([
            'user_id' => $owner->id,
            'permission_type' => PermissionTypeDriveEnum::SOMENTE_PROPRIETARIO->value,
        ]);
        $drive->drivePermissions()->create([
            'user_id' => $other->id,
            'permission_type' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
        ]);

        return [$drive->load('drivePermissions'), $other];
    });

    $this->tenant->run(fn () => expect(
        app(DriveService::class)->userCanAccess($drive, $other)
    )->toBeTrue());
});

test('getPermissionAttributes retorna atributos de bloqueio quando sem acesso', function () {
    [$drive, $other] = $this->tenant->run(function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $drive = makeFile(makeFolder(), ['user_id' => $owner->id]);
        $drive->drivePermissions()->create([
            'user_id' => $owner->id,
            'permission_type' => PermissionTypeDriveEnum::SOMENTE_PROPRIETARIO->value,
        ]);

        return [$drive->load('drivePermissions'), $other];
    });

    $attrs = $this->tenant->run(fn () => app(DriveService::class)->getPermissionAttributes($drive, $other));

    expect($attrs['has_access'])->toBeFalse()
        ->and($attrs['disable'])->toBeTrue()
        ->and($attrs['title'])->not->toBeNull();
});

test('storeAccessPermissions cria permissões para os usuários informados', function () {
    [$drive, $u1, $u2] = $this->tenant->run(function () {
        $drive = makeFile(makeFolder());

        return [$drive, User::factory()->create(), User::factory()->create()];
    });

    $this->tenant->run(fn () => app(DriveService::class)->storeAccessPermissions(
        formRequest(StoreAccessPermissionDriveRequest::class, [
            'drive_id' => $drive->id,
            'permission' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
            'users' => [$u1->id, $u2->id],
        ]),
        $this->tenant
    ));

    $this->tenant->run(fn () => expect(
        DrivePermission::where('drive_id', $drive->id)->count()
    )->toBe(2));
});

test('storeAccessPermissions não duplica permissão de usuário já existente', function () {
    [$drive, $u1] = $this->tenant->run(function () {
        $drive = makeFile(makeFolder());
        $u1 = User::factory()->create();
        $drive->drivePermissions()->create([
            'user_id' => $u1->id,
            'permission_type' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
        ]);

        return [$drive, $u1];
    });

    $this->tenant->run(fn () => app(DriveService::class)->storeAccessPermissions(
        formRequest(StoreAccessPermissionDriveRequest::class, [
            'drive_id' => $drive->id,
            'permission' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
            'users' => [$u1->id],
        ]),
        $this->tenant
    ));

    $this->tenant->run(fn () => expect(
        DrivePermission::where('drive_id', $drive->id)->where('user_id', $u1->id)->count()
    )->toBe(1));
});

test('userAccess retorna os usuários com permissão no drive', function () {
    [$drive, $u1] = $this->tenant->run(function () {
        $drive = makeFile(makeFolder(), ['name' => 'doc.pdf']);
        $u1 = User::factory()->create(['name' => 'Maria']);
        $drive->drivePermissions()->create([
            'user_id' => $u1->id,
            'permission_type' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
        ]);

        return [$drive, $u1];
    });

    $response = $this->tenant->run(fn () => app(DriveService::class)->userAccess((string) $drive->id, $this->tenant));
    $data = $response->getData(true);

    expect($data['success'])->toBeTrue()
        ->and($data['drive']['name'])->toBe('doc.pdf')
        ->and($data['data'])->toHaveCount(1)
        ->and($data['data'][0]['name'])->toBe('Maria')
        ->and($data['data'][0]['tipo_permission'])->toBe(PermissionTypeDriveEnum::ACESSO_TOTAL->value);
});

test('removeUserAccess remove a permissão do usuário', function () {
    [$drive, $u1] = $this->tenant->run(function () {
        $drive = makeFile(makeFolder());
        $u1 = User::factory()->create();
        $drive->drivePermissions()->create([
            'user_id' => $u1->id,
            'permission_type' => PermissionTypeDriveEnum::ACESSO_TOTAL->value,
        ]);

        return [$drive, $u1];
    });

    app(DriveService::class)->removeUserAccess((string) $drive->id, (string) $u1->id, $this->tenant);

    $this->tenant->run(fn () => expect(
        DrivePermission::where('drive_id', $drive->id)->where('user_id', $u1->id)->exists()
    )->toBeFalse());
});
