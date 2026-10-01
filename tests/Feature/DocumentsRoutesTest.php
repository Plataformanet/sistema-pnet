<?php

use App\Http\Middleware\Authenticate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * @return array<int, RoutingRoute>
 */
function documentsRoutes(): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => str_starts_with((string) $route->getName(), 'tenant.documents.'))
        ->values()
        ->all();
}

test('toda rota do grupo Documentações exige autenticação e uma permissão do módulo', function () {
    $routes = documentsRoutes();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $route) {
        $middleware = $route->gatherMiddleware();
        $permissions = collect($middleware)->filter(fn ($item) => is_string($item) && str_starts_with($item, 'permission:documents.'));

        expect($permissions)->toHaveCount(1, "Rota {$route->getName()} sem permissão do módulo")
            ->and($middleware)->toContain(Authenticate::class);
    }
});

test('nenhuma rota GET do grupo Documentações altera dados', function () {
    $writeActions = ['store', 'update', 'destroy', 'restore', 'move', 'convert', 'start', 'complete'];

    foreach (documentsRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }

        $action = str($route->getName())->afterLast('.')->value();

        expect($writeActions)->not->toContain($action);
    }
});

test('a busca de proponente por CPF tem limite de requisições', function () {
    $middleware = Route::getRoutes()->getByName('tenant.documents.applicants.lookup')->gatherMiddleware();

    expect($middleware)->toContain('throttle:documents-lookup');
});

test('os limitadores do módulo separam usuários de mesmo id em tenants diferentes', function (string $limiterName) {
    $user = (new User)->forceFill(['id' => 1]);
    $limiter = RateLimiter::limiter($limiterName);

    $keyFor = function (string $host) use ($limiter, $user): string {
        $request = Request::create("http://{$host}/documents");
        $request->setUserResolver(fn () => $user);

        return $limiter($request)->key;
    };

    expect($keyFor('empresa-a.localhost'))->not->toBe($keyFor('empresa-b.localhost'));
})->with(['documents-lookup', 'fee-calculator']);

test('parâmetros de rota tipados como enum usam enum de string, o único que o Laravel converte sozinho', function () {
    foreach (documentsRoutes() as $route) {
        foreach ($route->signatureParameters() as $parameter) {
            $type = $parameter->getType();

            if (! $type instanceof ReflectionNamedType || ! enum_exists($type->getName())) {
                continue;
            }

            expect((string) (new ReflectionEnum($type->getName()))->getBackingType())
                ->toBe('string', "Rota {$route->getName()}: \${$parameter->getName()} usa um enum que não é de string");
        }
    }
});
