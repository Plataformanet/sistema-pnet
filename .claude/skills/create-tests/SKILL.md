---
name: create-tests
description: Cria testes unitários e de feature dos módulos do sistema seguindo os padrões do projeto com Pest. Use quando pedirem para criar testes.
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
argument-hint: [nome-da-feature] [descrição]
---

# Criar testes (Pest + Laravel)

Você é especialista em escrever testes limpos, expressivos e completos com **Pest PHP** para aplicações **Laravel**. Siga este fluxo à risca para gerar testes prontos para rodar e alinhados aos padrões já existentes no projeto.

## Argumentos

- `$1` — **nome da feature/classe** a ser testada (ex.: `UserRegistration`, `OrderController`, `PaymentService`).
- `$2` — **descrição** opcional do comportamento ou cenário que precisa de cobertura.

Argumentos completos recebidos: `$ARGUMENTS`

Se o `$1` não foi informado, peça ao usuário **apenas** o nome da feature/classe antes de continuar. Não invente o alvo do teste.

---

## 1. Entenda o que será testado (antes de escrever qualquer código)

Identifique:

- **O que está sendo testado?** Model, Controller, Service, Job, Event, Policy, Command, endpoint de API, etc.
- **Qual o tipo de teste?** Unitário (lógica pura, sem DB/HTTP) ou Feature (HTTP, banco, stack completa do Laravel).
- **Existe uma classe/arquivo específico?** Se sim, **leia o arquivo com `Read` antes** de escrever os testes.

Use `Glob` e `Grep` para localizar o alvo e entender o contexto:

```
Glob: app/**/*$1*.php
Grep: "class $1" em app/
```

Faça perguntas de esclarecimento **somente** se o contexto estiver ambíguo. Caso contrário, prossiga.

## 2. Descubra os padrões do projeto (não invente convenções)

Antes de criar o arquivo, inspecione os testes que já existem para seguir o mesmo estilo:

- Leia `tests/Pest.php` para ver `uses()` globais, helpers e datasets compartilhados.
- Use `Glob: tests/**/*.php` e leia 1–2 testes parecidos com o que você vai criar.
- Verifique se há factories em `database/factories/` para os models envolvidos (`Glob: database/factories/*.php`).

Reaproveite helpers, factories e estados já existentes. **Nunca** crie helpers duplicados se já houver um equivalente.

---

## 3. Estrutura de arquivos e nomenclatura

```
tests/
├── Unit/            # Lógica pura, sem DB, sem HTTP
│   └── Services/
│       └── PaymentServiceTest.php
├── Feature/         # HTTP, DB, stack completa
│   └── Api/
│       └── UserControllerTest.php
└── Pest.php         # Helpers globais, uses(), datasets
```

**Regras:**
- Arquivo: `{NomeDaClasse}Test.php`.
- Bloco `describe()`: a classe ou feature em teste.
- Nome dos testes: frase em linguagem natural descrevendo o comportamento — *"cria um usuário quando os dados são válidos"*.

---

## 4. Padrões de teste

### 4.1 Estrutura base
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Cadastro de usuário', function () {
    it('cria um usuário com dados válidos', function () {
        $response = $this->postJson('/api/register', [
            'name'                  => 'João Silva',
            'email'                 => 'joao@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
                 ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
    });

    it('rejeita e-mails duplicados', function () {
        User::factory()->create(['email' => 'joao@example.com']);

        $this->postJson('/api/register', ['email' => 'joao@example.com'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['email']);
    });
});
```

### 4.2 Feature / HTTP autenticado
```php
it('retorna o perfil do usuário', function () {
    $user = User::factory()->create();

    $this->actingAs($user)            // ou ->actingAs($user, 'sanctum')
         ->getJson('/api/profile')
         ->assertOk()
         ->assertJson(['data' => ['email' => $user->email]]);
});
```

### 4.3 Model (unitário)
```php
it('pertence a uma organização', function () {
    $org  = Organization::factory()->create();
    $user = User::factory()->for($org)->create();

    expect($user->organization)->toBeInstanceOf(Organization::class)
        ->and($user->organization->id)->toBe($org->id);
});
```

### 4.4 Asserções de banco
```php
$this->assertDatabaseHas('orders', ['status' => 'paid', 'user_id' => $user->id]);
$this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
$this->assertDatabaseCount('products', 5);
$this->assertSoftDeleted('users', ['id' => $user->id]);
```

### 4.5 Fakes (Jobs, Events, Notifications, Mail, Storage, HTTP)
```php
Queue::fake();        Queue::assertPushed(SendWelcomeEmail::class);
Event::fake();        Event::assertDispatched(OrderPlaced::class);
Notification::fake(); Notification::assertSentTo($user, InvoiceReady::class);
Storage::fake('avatars');
Http::fake(['api.externa.com/*' => Http::response(['ok' => true], 200)]);
```

### 4.6 Policies / autorização
```php
it('impede que não-admin exclua usuários', function () {
    $user   = User::factory()->create(['role' => 'viewer']);
    $target = User::factory()->create();

    $this->actingAs($user)
         ->deleteJson("/api/users/{$target->id}")
         ->assertForbidden();
});
```

### 4.7 Console Commands
```php
it('executa o comando de limpeza com sucesso', function () {
    $this->artisan('app:cleanup-stale-records')
         ->expectsOutput('Limpeza concluída.')
         ->assertSuccessful();
});
```

---

## 5. Prefira `expect()` às asserções do PHPUnit

```php
expect($user->name)->toBe('Ada')
    ->and($user->email)->toContain('@')
    ->and($user->isAdmin())->toBeFalse();

expect(fn () => $service->process(null))
    ->toThrow(InvalidArgumentException::class, 'Input não pode ser nulo');
```

Matchers comuns: `toBe`, `toEqual`, `toBeTrue/False/Null`, `toBeInstanceOf`, `toContain`, `toHaveCount`, `toHaveKey`, `toMatchArray`, `toThrow`, `toBeGreaterThan/LessThan`.

## 6. Datasets (testes orientados a dados)
```php
it('valida o formato do e-mail', function (string $email) {
    $this->postJson('/api/register', ['email' => $email])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['email']);
})->with([
    'sem @'         => ['naoehemail'],
    'sem TLD'       => ['user@domain'],
    'string vazia'  => [''],
]);
```

## 7. Factories — sempre prefira a inserções manuais
```php
$admin = User::factory()->admin()->create();
$post  = Post::factory()
    ->for(User::factory()->create(), 'author')
    ->has(Comment::factory()->count(3))
    ->create();
$user  = User::factory()->create(['email' => 'especifico@test.com']);
```

## 8. Tempo — use `travelTo()`, nunca `sleep()`
```php
it('expira o token após 24 horas', function () {
    $token = Token::factory()->create();
    $this->travelTo(now()->addHours(25));
    expect($token->fresh()->isExpired())->toBeTrue();
});
```

---

## 9. Execute e valide

Depois de escrever o arquivo, **rode o teste** e itere até passar:

```bash
# roda apenas o arquivo recém-criado
sail php artisan test tests/Feature/$1Test.php

# ou filtrando pelo nome
sail php artisan test --filter=$1
```

Se houver falha, **leia o erro, corrija o teste ou aponte o bug** e rode novamente. Não entregue um teste sem ter executado.

## 10. Checklist final

- [ ] Cada teste tem uma única responsabilidade.
- [ ] Cobertura inclui caminho feliz **e** caminhos de erro (input vazio, não autorizado, não encontrado).
- [ ] Estado do banco limpo entre testes (`RefreshDatabase`).
- [ ] `Fake` usado para filas, eventos, e-mail, HTTP e storage.
- [ ] `expect()` preferido a `$this->assert*()`.
- [ ] Sem SQL cru nem IDs fixos — sempre factories.
- [ ] Nomes dos testes leem como frases.
- [ ] **O teste foi executado e está passando.**
