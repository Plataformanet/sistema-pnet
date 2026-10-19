<?php

use App\Exceptions\StageOrderTakenException;
use App\Http\Requests\CreateStageRequest;
use App\Http\Requests\MoveStageRequest;
use App\Http\Requests\StoreStageRequest;
use App\Http\Requests\UpdateStageRequest;
use App\Models\Bank;
use App\Models\Stage;
use App\Services\StageService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
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
 * Valida o Form Request de etapa, opcionalmente com o `{id}` da rota.
 *
 * @param  class-string<FormRequest>  $class
 * @param  array<string, mixed>  $data
 * @return array<string, array<int, string>>
 */
function stageRequestErrors(array $data, string $class = StoreStageRequest::class, ?int $routeId = null): array
{
    $request = $class::create('/', 'POST', $data);
    $request->setContainer(app())->setRedirector(app(Redirector::class));

    if ($routeId !== null) {
        $route = new Route('POST', 'documents/stages/{id}', fn () => null);
        $route->bind($request);
        $route->setParameter('id', (string) $routeId);
        $request->setRouteResolver(fn () => $route);
    }

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

/**
 * Cria um banco com uma timeline personalizada de etapas nas ordens informadas.
 *
 * @param  array<int, int>  $orders
 * @return array{0: Bank, 1: array<int, Stage>}
 */
function bankWithTimeline(array $orders): array
{
    return test()->tenant->run(function () use ($orders) {
        $bank = Bank::factory()->create();

        return [$bank, array_map(fn (int $order) => Stage::factory()->forBank($bank)->create(['order' => $order]), $orders)];
    });
}

test('a listagem mostra só a timeline selecionada', function () {
    [$bank] = bankWithTimeline([1, 2]);
    $service = app(StageService::class);

    $default = collect($service->findAll([], $this->tenant)->items());
    $bankStages = collect($service->findAll(['bank_id' => $bank->id], $this->tenant)->items());

    expect($default->pluck('order')->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8])
        ->and($default->pluck('bank_id')->unique()->all())->toBe([null])
        ->and($bankStages->pluck('order')->all())->toBe([1, 2])
        ->and($bankStages->pluck('bank_id')->unique()->all())->toBe([$bank->id]);
});

test('move não troca de posição com etapa de outra timeline', function () {
    [, [$bankFirst, $bankSecond]] = bankWithTimeline([1, 5]);
    $defaultFourth = $this->tenant->run(fn () => Stage::forTimeline(null)->where('order', 4)->firstOrFail());

    app(StageService::class)->move((string) $bankSecond->id, StageService::DIRECTION_UP, $this->tenant);

    $this->tenant->run(function () use ($bankFirst, $bankSecond, $defaultFourth) {
        expect($bankSecond->fresh()->order)->toBe(1)
            ->and($bankFirst->fresh()->order)->toBe(5)
            ->and($defaultFourth->fresh()->order)->toBe(4)
            ->and(Stage::forTimeline(null)->ordered()->pluck('order')->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8]);
    });
});

test('moveTo com alvo de outra timeline lança erro e não altera nada', function () {
    [, [$bankFirst, $bankSecond]] = bankWithTimeline([1, 2]);
    $defaultFirst = $this->tenant->run(fn () => Stage::forTimeline(null)->where('order', 1)->firstOrFail());

    expect(fn () => app(StageService::class)->moveTo((string) $bankSecond->id, (string) $defaultFirst->id, $this->tenant))
        ->toThrow(ModelNotFoundException::class);

    $this->tenant->run(function () use ($bankFirst, $bankSecond) {
        expect($bankFirst->fresh()->order)->toBe(1)
            ->and($bankSecond->fresh()->order)->toBe(2)
            ->and(Stage::forTimeline(null)->ordered()->pluck('order')->all())->toBe([1, 2, 3, 4, 5, 6, 7, 8]);
    });
});

test('moveTo reordena dentro da timeline do banco', function () {
    [, [$first, $second, $third]] = bankWithTimeline([1, 2, 3]);

    app(StageService::class)->moveTo((string) $third->id, (string) $first->id, $this->tenant);

    $this->tenant->run(function () use ($first, $second, $third) {
        expect([$third->fresh()->order, $first->fresh()->order, $second->fresh()->order])->toBe([1, 2, 3]);
    });
});

test('mover rejeita etapa de destino de outra timeline', function () {
    [, [$bankFirst, $bankSecond]] = bankWithTimeline([1, 2]);
    $defaultFirst = $this->tenant->run(fn () => Stage::forTimeline(null)->where('order', 1)->firstOrFail());

    $this->tenant->run(function () use ($bankFirst, $bankSecond, $defaultFirst) {
        expect(stageRequestErrors(['target_id' => $defaultFirst->id], MoveStageRequest::class, $bankSecond->id))->toHaveKey('target_id')
            ->and(stageRequestErrors(['target_id' => $bankFirst->id], MoveStageRequest::class, $bankSecond->id))->toBe([]);
    });
});

test('a ordem é única por timeline', function () {
    [$bank] = bankWithTimeline([1]);

    $this->tenant->run(function () use ($bank) {
        $otherBank = Bank::factory()->create();

        expect(stageRequestErrors(stagePayload(['order' => 1, 'bank_id' => $otherBank->id])))->toBe([])
            ->and(stageRequestErrors(stagePayload(['order' => 2, 'bank_id' => $bank->id])))->toBe([])
            ->and(stageRequestErrors(stagePayload(['order' => 1, 'bank_id' => $bank->id])))->toHaveKey('order')
            ->and(stageRequestErrors(stagePayload(['order' => 1])))->toHaveKey('order');
    });
});

test('a ordem do update é validada na timeline gravada da etapa', function () {
    [$bank, [, $bankSecond]] = bankWithTimeline([1, 2]);

    $this->tenant->run(function () use ($bankSecond) {
        expect(stageRequestErrors(stagePayload(['order' => 1]), UpdateStageRequest::class, $bankSecond->id))->toHaveKey('order')
            ->and(stageRequestErrors(stagePayload(['order' => 9]), UpdateStageRequest::class, $bankSecond->id))->toBe([]);
    });
});

test('etapa nova não pode ser criada em banco excluído', function () {
    $this->tenant->run(function () {
        $bank = Bank::factory()->create();
        $bank->delete();

        expect(stageRequestErrors(stagePayload(['bank_id' => $bank->id])))->toHaveKey('bank_id');
    });
});

test('o update não altera o banco da etapa', function () {
    [$bank, [$stage]] = bankWithTimeline([1]);

    $this->tenant->run(function () use ($stage) {
        $request = UpdateStageRequest::create('/', 'PUT', stagePayload(['order' => 1, 'bank_id' => null]));
        $request->setContainer(app())->setRedirector(app(Redirector::class));
        $route = new Route('PUT', 'documents/stages/{id}', fn () => null);
        $route->bind($request);
        $route->setParameter('id', (string) $stage->id);
        $request->setRouteResolver(fn () => $route);
        $request->validateResolved();

        expect($request->validated())->not->toHaveKey('bank_id');
    });

    app(StageService::class)->update(stagePayload(['order' => 1, 'bank_id' => null]), (string) $stage->id, $this->tenant);

    $this->tenant->run(fn () => expect($stage->fresh()->bank_id)->toBe($bank->id));
});

test('restaurar só considera colisão de ordem na mesma timeline', function () {
    [$bank, [$stage]] = bankWithTimeline([3]);
    $service = app(StageService::class);

    $this->tenant->run(fn () => $stage->delete());
    $service->restore((string) $stage->id, $this->tenant);

    $this->tenant->run(fn () => expect($stage->fresh()->order)->toBe(3));

    $this->tenant->run(function () use ($stage, $bank) {
        $stage->delete();
        Stage::factory()->forBank($bank)->create(['order' => 3]);
        Stage::factory()->forBank($bank)->create(['order' => 7]);
    });
    $service->restore((string) $stage->id, $this->tenant);

    $this->tenant->run(fn () => expect($stage->fresh()->order)->toBe(8));
});

test('timelineOptions traz a padrão e os bancos ativos com a contagem de etapas ativas', function () {
    [$custom, [, $deletedStage]] = bankWithTimeline([1, 2, 3]);

    [$empty, $deletedBank] = $this->tenant->run(function () use ($deletedStage) {
        $deletedStage->delete();
        $deletedBank = Bank::factory()->create();
        $deletedBank->delete();

        return [Bank::factory()->create(), $deletedBank];
    });

    $options = collect(app(StageService::class)->timelineOptions($this->tenant));

    expect($options->first())->toBe(['bank_id' => null, 'name' => StageService::DEFAULT_TIMELINE_NAME, 'stages_count' => 8])
        ->and($options->firstWhere('bank_id', $custom->id)['stages_count'])->toBe(2)
        ->and($options->firstWhere('bank_id', $empty->id)['stages_count'])->toBe(0)
        ->and($options->firstWhere('bank_id', $deletedBank->id))->toBeNull()
        ->and(collect(app(StageService::class)->timelineOptions($this->tenant, $deletedBank->id))->firstWhere('bank_id', $deletedBank->id)['name'])
        ->toBe($deletedBank->name.' (excluído)');
});

test('store recusa ordem ocupada na mesma timeline mesmo sem passar pela validação', function () {
    [$bank] = bankWithTimeline([1]);

    app(StageService::class)->store(stagePayload(['order' => 1, 'bank_id' => $bank->id]), $this->tenant);
})->throws(StageOrderTakenException::class);

test('store aceita a mesma ordem em outra timeline', function () {
    $bank = $this->tenant->run(fn () => Bank::factory()->create());

    $stage = app(StageService::class)->store(stagePayload(['order' => 1, 'bank_id' => $bank->id]), $this->tenant);

    $this->tenant->run(fn () => expect($stage->fresh()->bank_id)->toBe($bank->id)
        ->and(Stage::forTimeline(null)->where('order', 1)->count())->toBe(1));
});

test('store grava a etapa na timeline do banco com ordem livre', function () {
    [$bank] = bankWithTimeline([1]);

    $stage = app(StageService::class)->store(stagePayload(['order' => 2, 'bank_id' => $bank->id]), $this->tenant);

    $this->tenant->run(fn () => expect($stage->fresh()->bank_id)->toBe($bank->id)
        ->and(Stage::forTimeline($bank->id)->ordered()->pluck('order')->all())->toBe([1, 2]));
});

test('o formulário de nova etapa só aceita banco ativo', function () {
    $this->tenant->run(function () {
        [$active, $deleted] = [Bank::factory()->create(), Bank::factory()->create()];
        $deleted->delete();

        expect(stageRequestErrors(['bank_id' => $active->id], CreateStageRequest::class))->toBe([])
            ->and(stageRequestErrors([], CreateStageRequest::class))->toBe([])
            ->and(stageRequestErrors(['bank_id' => $deleted->id], CreateStageRequest::class))->toHaveKey('bank_id')
            ->and(stageRequestErrors(['bank_id' => 'abc'], CreateStageRequest::class))->toHaveKey('bank_id');
    });
});
