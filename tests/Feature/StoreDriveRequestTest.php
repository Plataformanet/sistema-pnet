<?php

use App\Http\Requests\StoreDriveRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * Valida um payload contra as regras do StoreDriveRequest dentro do tenant.
 *
 * @param  array<string, mixed>  $data
 */
function validateStoreDrive(array $data): Illuminate\Validation\Validator
{
    return test()->tenant->run(function () use ($data) {
        $request = new StoreDriveRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    });
}

/**
 * Reproduz exatamente o payload que o frontend (List.vue) envia no upload.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function frontendUploadPayload(array $overrides = []): array
{
    return array_merge([
        'documents' => [UploadedFile::fake()->create('relatorio.pdf', 100, 'application/pdf')],
        'folder_id' => 1,
        'user_id' => 1,
        'modified_at' => [now()->toISOString()],
    ], $overrides);
}

test('passa com o payload real enviado pelo frontend (sem drive_id)', function () {
    // Regressão: o frontend não envia drive_id; a regra required de drive_id
    // fazia o upload falhar com 422 antes de qualquer lógica de storage.
    expect(validateStoreDrive(frontendUploadPayload())->passes())->toBeTrue();
});

test('falha quando o folder_id não é informado', function () {
    $validator = validateStoreDrive(frontendUploadPayload(['folder_id' => null]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('folder_id'))->toBeTrue();
});

test('falha quando o user_id não é informado', function () {
    $validator = validateStoreDrive(frontendUploadPayload(['user_id' => null]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('user_id'))->toBeTrue();
});

test('falha quando o documento tem extensão não permitida', function () {
    $validator = validateStoreDrive(frontendUploadPayload([
        'documents' => [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')],
    ]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('documents.0'))->toBeTrue();
});

test('aceita as extensões de documento permitidas', function (string $extension) {
    $validator = validateStoreDrive(frontendUploadPayload([
        'documents' => [UploadedFile::fake()->create("arquivo.{$extension}", 10)],
    ]));

    expect($validator->passes())->toBeTrue();
})->with(['pdf', 'docx', 'jpg', 'png', 'xlsx', 'txt', 'zip']);
