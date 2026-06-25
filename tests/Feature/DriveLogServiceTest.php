<?php

use App\Models\DriveLog;
use App\Models\User;
use App\Services\DriveLogService;

beforeEach(function () {
    $this->tenant = sharedTenant();

    $this->user = $this->tenant->run(fn () => User::factory()->create(['name' => 'João Tester']));

    $this->actingAs($this->user);
});

test('store grava o log como array sem dupla codificação e com quem excluiu', function () {
    $log = $this->tenant->run(fn () => app(DriveLogService::class)->store([
        'name' => 'contrato.pdf',
        'document_path' => 'drive/Pasta/contrato.pdf',
        'document_type' => 'pdf',
    ]));

    expect($log)->toBeInstanceOf(DriveLog::class);

    $this->tenant->run(function () use ($log) {
        $fresh = DriveLog::findOrFail($log->id);

        expect($fresh->log)->toBeArray()
            ->and($fresh->log['name'])->toBe('contrato.pdf')
            ->and($fresh->log['document_path'])->toBe('drive/Pasta/contrato.pdf')
            ->and($fresh->log['document_type'])->toBe('pdf')
            ->and($fresh->log['deleted_by'])->toBe('João Tester')
            ->and($fresh->log)->toHaveKey('deleted_at');
    });
});

test('store usa null para chaves ausentes sem quebrar', function () {
    $log = $this->tenant->run(fn () => app(DriveLogService::class)->store([
        'name' => 'somente-nome.txt',
    ]));

    $this->tenant->run(function () use ($log) {
        $fresh = DriveLog::findOrFail($log->id);

        expect($fresh->log['document_path'])->toBeNull()
            ->and($fresh->log['document_type'])->toBeNull()
            ->and($fresh->log['name'])->toBe('somente-nome.txt');
    });
});

test('findAll retorna os logs do tenant', function () {
    $this->tenant->run(function () {
        app(DriveLogService::class)->store(['name' => 'a.pdf']);
        app(DriveLogService::class)->store(['name' => 'b.pdf']);
    });

    $logs = $this->tenant->run(fn () => app(DriveLogService::class)->findAll($this->tenant)->collect());

    expect($logs)->toHaveCount(2);
});
