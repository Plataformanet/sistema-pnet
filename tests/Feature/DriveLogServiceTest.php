<?php

use App\Enums\DocumentTypeDriveEnum;
use App\Enums\DriveLogActionEnum;
use App\Http\Requests\StoreDriveRequest;
use App\Models\Drive;
use App\Models\DriveFolder;
use App\Models\DriveLog;
use App\Models\User;
use App\Services\DriveLogService;
use App\Services\DriveService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->user = $this->tenant->run(fn () => User::factory()->create(['name' => 'João Tester']));

    $this->actingAs($this->user);

    config(['bucket.disk' => 'public']);
    Storage::fake('public');
});

/**
 * Cria uma pasta com um arquivo dentro. Chamar dentro de `$tenant->run()`.
 */
function driveLogFixture(string $folderName = 'Docs', string $fileName = 'contrato.pdf'): Drive
{
    $folder = DriveFolder::create(['name' => $folderName, 'parent_id' => null]);

    return $folder->drives()->create([
        'user_id' => auth()->id(),
        'name' => $fileName,
        'document_path' => "drive/{$folderName}/{$fileName}",
        'document_size' => 10,
        'document_type' => DocumentTypeDriveEnum::PDF,
        'modified_by' => auth()->id(),
        'modified_at' => now(),
    ]);
}

test('record grava ação, usuário, IP, arquivo e dados do arquivo em meta', function () {
    $this->tenant->run(function () {
        $drive = driveLogFixture();

        $log = app(DriveLogService::class)->record(DriveLogActionEnum::UPLOAD, $drive, ['origem' => 'teste']);
        $fresh = DriveLog::findOrFail($log->id);

        expect($fresh->action)->toBe(DriveLogActionEnum::UPLOAD)
            ->and($fresh->user_id)->toBe($this->user->id)
            ->and($fresh->drive_id)->toBe($drive->id)
            ->and($fresh->ip_address)->toBe('127.0.0.1')
            ->and($fresh->meta)->toEqual([
                'name' => 'contrato.pdf',
                'document_path' => 'drive/Docs/contrato.pdf',
                'document_type' => 'pdf',
                'origem' => 'teste',
            ]);
    });
});

test('record aceita ação sem arquivo (pasta)', function () {
    $this->tenant->run(function () {
        $log = app(DriveLogService::class)->record(DriveLogActionEnum::DELETE, null, ['folder_id' => 7, 'name' => 'Pasta']);

        expect($log->fresh()->drive_id)->toBeNull()
            ->and($log->fresh()->meta)->toEqual(['folder_id' => 7, 'name' => 'Pasta']);
    });
});

test('findAll retorna os logs do tenant', function () {
    $this->tenant->run(function () {
        app(DriveLogService::class)->record(DriveLogActionEnum::UPLOAD);
        app(DriveLogService::class)->record(DriveLogActionEnum::DOWNLOAD);
    });

    $logs = $this->tenant->run(fn () => app(DriveLogService::class)->findAll($this->tenant)->collect());

    expect($logs)->toHaveCount(2);
});

test('permanentDeletions lista só exclusões definitivas, no formato da tela, com o nome de quem excluiu', function () {
    $this->tenant->run(function () {
        $drive = driveLogFixture();
        $service = app(DriveLogService::class);

        $service->record(DriveLogActionEnum::UPLOAD, $drive);
        $service->record(DriveLogActionEnum::PERMANENT_DELETE, $drive);
    });

    $rows = app(DriveLogService::class)->permanentDeletions($this->tenant);

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toMatchArray([
            'name' => 'contrato.pdf',
            'document_path' => 'drive/Docs/contrato.pdf',
            'document_type' => 'pdf',
            'deleted_by' => 'João Tester',
        ])
        ->and($rows->first()['deleted_at'])->toBeString();
});

test('permanentDeletions usa o nome gravado em meta nos logs antigos, sem user_id', function () {
    $this->tenant->run(fn () => DriveLog::create([
        'action' => DriveLogActionEnum::PERMANENT_DELETE,
        'meta' => ['name' => 'velho.pdf', 'deleted_by' => 'Fulano', 'deleted_at' => '2026-01-01 10:00:00'],
    ]));

    $rows = app(DriveLogService::class)->permanentDeletions($this->tenant);

    expect($rows->first()['deleted_by'])->toBe('Fulano')
        ->and($rows->first()['name'])->toBe('velho.pdf');
});

test('upload, download, exclusão, restauração e exclusão definitiva geram log com usuário e IP', function () {
    $folder = $this->tenant->run(fn () => DriveFolder::create(['name' => 'Fluxo', 'parent_id' => null]));
    $service = app(DriveService::class);

    $drive = $this->tenant->run(fn () => $service->store(
        formRequest(StoreDriveRequest::class, [
            'drive_id' => 1,
            'user_id' => $this->user->id,
            'folder_id' => $folder->id,
        ], ['documents' => [UploadedFile::fake()->create('doc.pdf', 5, 'application/pdf')]]),
        $this->tenant
    ));

    $this->tenant->run(function () use ($service, $drive) {
        $service->download((string) $drive->id, $this->tenant);
        $service->delete((string) $drive->id, $this->tenant);
        $service->restore((string) $drive->id, DocumentTypeDriveEnum::PDF->value, $this->tenant);
    });

    $actions = $this->tenant->run(fn () => DriveLog::orderBy('id')->get());

    expect($actions->pluck('action')->all())->toBe([
        DriveLogActionEnum::UPLOAD,
        DriveLogActionEnum::DOWNLOAD,
        DriveLogActionEnum::DELETE,
        DriveLogActionEnum::RESTORE,
    ])->and($actions->pluck('user_id')->unique()->all())->toBe([$this->user->id])
        ->and($actions->pluck('ip_address')->unique()->all())->toBe(['127.0.0.1']);
});

test('moveSelected de um arquivo gera log de movimentação com origem e destino', function () {
    [$drive, $target] = $this->tenant->run(fn () => [
        driveLogFixture('Origem', 'mover.pdf'),
        DriveFolder::create(['name' => 'Destino', 'parent_id' => null]),
    ]);
    Storage::disk('public')->put('drive/Origem/mover.pdf', 'x');

    $this->tenant->run(fn () => app(DriveService::class)->moveSelected(
        [['type' => 'file', 'id' => (string) $drive->id]],
        $target->id,
        $this->tenant
    ));

    $log = $this->tenant->run(fn () => DriveLog::where('action', DriveLogActionEnum::MOVE)->firstOrFail());

    expect($log->drive_id)->toBe($drive->id)
        ->and($log->meta['from'])->toBe('drive/Origem/mover.pdf')
        ->and($log->meta['to'])->toBe('drive/Destino/mover.pdf');
});
