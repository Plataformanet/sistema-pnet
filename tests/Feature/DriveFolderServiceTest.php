<?php

use App\Enums\DocumentTypeDriveEnum;
use App\Http\Requests\StoreDriveFolderRequest;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\User;
use App\Services\DriveFolderService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->user = $this->tenant->run(fn () => User::factory()->create());

    $this->actingAs($this->user);

    Storage::fake('public');
});

function storeFolder(array $data): DriveFolder
{
    return test()->tenant->run(fn () => app(DriveFolderService::class)->store(
        formRequest(StoreDriveFolderRequest::class, $data),
        test()->tenant
    ));
}

test('store cria a pasta e o respectivo drive da pasta', function () {
    $folder = storeFolder(['name' => 'Documentos']);

    expect($folder)->toBeInstanceOf(DriveFolder::class)
        ->and($folder->name)->toBe('Documentos')
        ->and($folder->parent_id)->toBeNull();

    $this->tenant->run(function () use ($folder) {
        $drive = Drive::where('drive_folder_id', $folder->id)->first();

        expect($drive)->not->toBeNull()
            ->and($drive->name)->toBe('Documentos')
            ->and($drive->document_type)->toBe(DocumentTypeDriveEnum::FOLDER)
            ->and($drive->document_path)->toBe('drive/Documentos')
            ->and((int) $drive->document_size)->toBe(0)
            ->and($drive->user_id)->toBe($this->user->id);
    });
});

test('store incrementa o nome quando já existe pasta com o mesmo nome no mesmo nível', function () {
    $first = storeFolder(['name' => 'Projetos']);
    $second = storeFolder(['name' => 'Projetos']);

    expect($first->name)->toBe('Projetos')
        ->and($second->name)->toBe('Projetos (1)');
});

test('store cria subpasta com caminho herdado do parent', function () {
    $parent = storeFolder(['name' => 'Pai']);
    $child = storeFolder(['name' => 'Filha', 'parent_id' => $parent->id]);

    expect($child->parent_id)->toBe($parent->id);

    $this->tenant->run(function () use ($child) {
        $drive = Drive::where('drive_folder_id', $child->id)->first();

        expect($drive->document_path)->toBe('drive/Pai/Filha');
    });
});

test('delete faz soft delete da pasta e dos drives filhos', function () {
    $folder = storeFolder(['name' => 'ParaApagar']);

    app(DriveFolderService::class)->delete((string) $folder->id, $this->tenant);

    $this->tenant->run(function () use ($folder) {
        expect(DriveFolder::withTrashed()->find($folder->id)->trashed())->toBeTrue()
            ->and(Drive::where('drive_folder_id', $folder->id)->exists())->toBeFalse()
            ->and(Drive::withTrashed()->where('drive_folder_id', $folder->id)->exists())->toBeTrue();
    });
});
