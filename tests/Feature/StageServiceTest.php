<?php

use App\Http\Requests\MoveStageRequest;
use App\Http\Requests\StoreStageRequest;
use App\Models\Stage;
use App\Services\StageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->tenant = sharedTenant();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stagePayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nova Etapa',
        'order' => 50,
        'has_date' => false,
        'date_required' => false,
        'has_upload' => false,
        'upload_required' => false,
        'title_required' => false,
        'notes_required' => false,
        'shows_property_data' => false,
        'shows_registry_protocol' => false,
        'completion_deadline_hours' => 0,
        'alert_deadline_hours' => 0,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function stageRequestErrors(array $data): array
{
    $request = StoreStageRequest::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    try {
        $request->validateResolved();
    } catch (ValidationException $exception) {
        return $exception->errors();
    }

    return [];
}

test('a listagem de etapas é ordenada pela ordem', function () {
    $orders = collect(app(StageService::class)->findAll([], $this->tenant)->items())->pluck('order')->all();

    expect($orders)->toBe([1, 2, 3, 4, 5, 6, 7, 8]);
});

test('move troca a etapa de posição com a vizinha ativa', function () {
    $service = app(StageService::class);
    [$second, $third] = $this->tenant->run(fn () => [Stage::where('order', 2)->firstOrFail(), Stage::where('order', 3)->firstOrFail()]);

    $service->move((string) $third->id, StageService::DIRECTION_UP, $this->tenant);

    $this->tenant->run(function () use ($second, $third) {
        expect($third->fresh()->order)->toBe(2)
            ->and($second->fresh()->order)->toBe(3);
    });
});

test('move ignora etapas excluídas ao procurar a vizinha', function () {
    $service = app(StageService::class);
    [$first, $third] = $this->tenant->run(function () {
        Stage::where('order', 2)->firstOrFail()->delete();

        return [Stage::where('order', 1)->firstOrFail(), Stage::where('order', 3)->firstOrFail()];
    });

    $service->move((string) $third->id, StageService::DIRECTION_UP, $this->tenant);

    $this->tenant->run(function () use ($first, $third) {
        expect($third->fresh()->order)->toBe(1)
            ->and($first->fresh()->order)->toBe(3);
    });
});

test('move na primeira posição não altera nada', function () {
    $first = $this->tenant->run(fn () => Stage::where('order', 1)->firstOrFail());

    app(StageService::class)->move((string) $first->id, StageService::DIRECTION_UP, $this->tenant);

    $this->tenant->run(fn () => expect($first->fresh()->order)->toBe(1));
});

/**
 * Nomes das etapas ativas na ordem atual.
 *
 * @return array<int, string>
 */
function activeStageNames(): array
{
    return Stage::ordered()->pluck('name')->all();
}

test('moveTo leva a etapa para baixo, até a posição da alvo, e sobe as do meio', function () {
    [$names, $second, $fifth] = $this->tenant->run(fn () => [
        activeStageNames(),
        Stage::where('order', 2)->firstOrFail(),
        Stage::where('order', 5)->firstOrFail(),
    ]);

    app(StageService::class)->moveTo((string) $second->id, (string) $fifth->id, $this->tenant);

    $this->tenant->run(function () use ($names, $second) {
        expect(activeStageNames())->toBe([$names[0], $names[2], $names[3], $names[4], $names[1], $names[5], $names[6], $names[7]])
            ->and($second->fresh()->order)->toBe(5)
            ->and(Stage::ordered()->pluck('order')->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8]);
    });
});

test('moveTo leva a etapa para cima, até a posição da alvo, e desce as do meio', function () {
    [$names, $first, $fourth] = $this->tenant->run(fn () => [
        activeStageNames(),
        Stage::where('order', 1)->firstOrFail(),
        Stage::where('order', 4)->firstOrFail(),
    ]);

    app(StageService::class)->moveTo((string) $fourth->id, (string) $first->id, $this->tenant);

    $this->tenant->run(fn () => expect(activeStageNames())
        ->toBe([$names[3], $names[0], $names[1], $names[2], $names[4], $names[5], $names[6], $names[7]]));
});

test('moveTo reaproveita as ordens das etapas ativas e não colide com etapas excluídas', function () {
    [$third, $sixth] = $this->tenant->run(function () {
        Stage::where('order', 4)->firstOrFail()->delete();

        return [Stage::where('order', 3)->firstOrFail(), Stage::where('order', 6)->firstOrFail()];
    });

    app(StageService::class)->moveTo((string) $third->id, (string) $sixth->id, $this->tenant);

    $this->tenant->run(function () use ($third) {
        expect(Stage::ordered()->pluck('order')->all())->toBe([1, 2, 3, 5, 6, 7, 8])
            ->and($third->fresh()->order)->toBe(6)
            ->and(Stage::onlyTrashed()->where('order', 4)->count())->toBe(1);
    });
});

test('moveTo não aceita etapa excluída', function () {
    [$deleted, $first] = $this->tenant->run(function () {
        $deleted = Stage::where('order', 8)->firstOrFail();
        $deleted->delete();

        return [$deleted, Stage::where('order', 1)->firstOrFail()];
    });

    app(StageService::class)->moveTo((string) $deleted->id, (string) $first->id, $this->tenant);
})->throws(ModelNotFoundException::class);

test('mover exige a direção ou a etapa de destino', function () {
    $request = MoveStageRequest::create('/', 'PATCH', []);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    expect(fn () => $request->validateResolved())->toThrow(ValidationException::class);
});

test('a ordem é única só entre etapas ativas', function () {
    expect(stageRequestErrors(stagePayload(['order' => 1])))->toHaveKey('order');

    $this->tenant->run(fn () => Stage::where('order', 8)->firstOrFail()->delete());

    expect(stageRequestErrors(stagePayload(['order' => 8])))->toBe([]);
});

test('regras cruzadas rejeitam combinações inválidas de flags', function (array $overrides, string $field) {
    expect(stageRequestErrors(stagePayload($overrides)))->toHaveKey($field);
})->with([
    'data obrigatória sem data' => [['date_required' => true, 'has_date' => false], 'date_required'],
    'upload obrigatório sem upload' => [['upload_required' => true, 'has_upload' => false], 'upload_required'],
    'título obrigatório sem upload' => [['title_required' => true, 'has_upload' => false], 'title_required'],
    'alerta maior que a conclusão' => [['completion_deadline_hours' => 24, 'alert_deadline_hours' => 48], 'alert_deadline_hours'],
]);

test('regras cruzadas aceitam combinações válidas', function () {
    expect(stageRequestErrors(stagePayload([
        'has_date' => true,
        'date_required' => true,
        'has_upload' => true,
        'upload_required' => true,
        'title_required' => true,
        'completion_deadline_hours' => 48,
        'alert_deadline_hours' => 24,
    ])))->toBe([])
        ->and(stageRequestErrors(stagePayload(['completion_deadline_hours' => 0, 'alert_deadline_hours' => 24])))->toBe([]);
});

test('restaurar uma etapa cuja ordem foi ocupada a manda para o fim, sem repetir a ordem', function () {
    $service = app(StageService::class);

    [$deleted, $occupant] = $this->tenant->run(function () {
        $deleted = Stage::factory()->create(['order' => 9000]);
        $deleted->delete();

        return [$deleted, Stage::factory()->create(['order' => 9000])];
    });

    $service->restore((string) $deleted->id, $this->tenant);

    $this->tenant->run(function () use ($deleted, $occupant) {
        $restored = $deleted->fresh();

        expect($restored->trashed())->toBeFalse()
            ->and($restored->order)->toBe(Stage::max('order'))
            ->and($restored->order)->not->toBe($occupant->order)
            ->and(Stage::where('order', 9000)->count())->toBe(1);
    });
});

test('restaurar uma etapa com a ordem livre mantém a ordem original', function () {
    $deleted = $this->tenant->run(function () {
        $stage = Stage::factory()->create(['order' => 9100]);
        $stage->delete();

        return $stage;
    });

    app(StageService::class)->restore((string) $deleted->id, $this->tenant);

    $this->tenant->run(fn () => expect($deleted->fresh()->order)->toBe(9100));
});
