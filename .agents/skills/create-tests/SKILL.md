---
name: create-tests
description: Creates unit and feature tests for the system's modules following the project's standards with Pest. Use when asked to create tests.
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
argument-hint: [feature-name] [description]
---

# Create tests (Pest + Laravel)

You are an expert in writing clean, expressive, and comprehensive tests with **Pest PHP** for **Laravel** applications. Follow this workflow precisely to produce runnable tests that match the patterns already present in the project.

## Arguments

- `$1` — **feature/class name** to be tested (e.g., `UserRegistration`, `OrderController`, `PaymentService`).
- `$2` — optional **description** of the behavior or scenario that needs coverage.

Full arguments received: `$ARGUMENTS`

If `$1` was not provided, ask the user **only** for the feature/class name before continuing. Do not invent the test target.

---

## 1. Understand what will be tested (before writing any code)

Identify:

- **What is being tested?** Model, Controller, Service, Job, Event, Policy, Command, API endpoint, etc.
- **What type of test?** Unit (pure logic, no DB/HTTP) or Feature (HTTP, database, full Laravel stack).
- **Is there a specific class/file?** If so, **read the file with `Read` first** before writing the tests.

Use `Glob` and `Grep` to locate the target and understand the context:

```
Glob: app/**/*$1*.php
Grep: "class $1" in app/
```

Ask clarifying questions **only** if the context is ambiguous. Otherwise, proceed.

## 2. Discover the project's patterns (don't invent conventions)

Before creating the file, inspect existing tests so you follow the same style:

- Read `tests/Pest.php` to see global `uses()`, helpers, and shared datasets.
- Use `Glob: tests/**/*.php` and read 1–2 tests similar to what you're about to create.
- Check whether factories exist in `database/factories/` for the involved models (`Glob: database/factories/*.php`).

Reuse existing helpers, factories, and states. **Never** create duplicate helpers if an equivalent already exists.

---

## 3. File structure & naming conventions

```
tests/
├── Unit/            # Pure logic, no DB, no HTTP
│   └── Services/
│       └── PaymentServiceTest.php
├── Feature/         # HTTP, DB, full stack
│   └── Api/
│       └── UserControllerTest.php
└── Pest.php         # Global helpers, uses(), datasets
```

**Rules:**
- File: `{ClassName}Test.php`.
- `describe()` block: the class or feature under test.
- Test names: a plain-English sentence describing the behavior — *"creates a user when valid data is provided"*.

---

## 4. Testing patterns

### 4.1 Base structure
```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('User registration', function () {
    it('creates a user with valid data', function () {
        $response = $this->postJson('/api/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
                 ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    });

    it('rejects duplicate emails', function () {
        User::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/register', ['email' => 'john@example.com'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['email']);
    });
});
```

### 4.2 Feature / authenticated HTTP
```php
it('returns the user profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)            // or ->actingAs($user, 'sanctum')
         ->getJson('/api/profile')
         ->assertOk()
         ->assertJson(['data' => ['email' => $user->email]]);
});
```

### 4.3 Model (unit)
```php
it('belongs to an organization', function () {
    $org  = Organization::factory()->create();
    $user = User::factory()->for($org)->create();

    expect($user->organization)->toBeInstanceOf(Organization::class)
        ->and($user->organization->id)->toBe($org->id);
});
```

### 4.4 Database assertions
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
Http::fake(['api.external.com/*' => Http::response(['ok' => true], 200)]);
```

### 4.6 Policies / authorization
```php
it('prevents a non-admin from deleting users', function () {
    $user   = User::factory()->create(['role' => 'viewer']);
    $target = User::factory()->create();

    $this->actingAs($user)
         ->deleteJson("/api/users/{$target->id}")
         ->assertForbidden();
});
```

### 4.7 Console Commands
```php
it('runs the cleanup command successfully', function () {
    $this->artisan('app:cleanup-stale-records')
         ->expectsOutput('Cleanup complete.')
         ->assertSuccessful();
});
```

---

## 5. Prefer `expect()` over PHPUnit assertions

```php
expect($user->name)->toBe('Ada')
    ->and($user->email)->toContain('@')
    ->and($user->isAdmin())->toBeFalse();

expect(fn () => $service->process(null))
    ->toThrow(InvalidArgumentException::class, 'Input cannot be null');
```

Common matchers: `toBe`, `toEqual`, `toBeTrue/False/Null`, `toBeInstanceOf`, `toContain`, `toHaveCount`, `toHaveKey`, `toMatchArray`, `toThrow`, `toBeGreaterThan/LessThan`.

## 6. Datasets (data-driven tests)
```php
it('validates the email format', function (string $email) {
    $this->postJson('/api/register', ['email' => $email])
         ->assertUnprocessable()
         ->assertJsonValidationErrors(['email']);
})->with([
    'missing @'    => ['notanemail'],
    'missing TLD'  => ['user@domain'],
    'empty string' => [''],
]);
```

## 7. Factories — always prefer them over manual inserts
```php
$admin = User::factory()->admin()->create();
$post  = Post::factory()
    ->for(User::factory()->create(), 'author')
    ->has(Comment::factory()->count(3))
    ->create();
$user  = User::factory()->create(['email' => 'specific@test.com']);
```

## 8. Time — use `travelTo()`, never `sleep()`
```php
it('expires the token after 24 hours', function () {
    $token = Token::factory()->create();
    $this->travelTo(now()->addHours(25));
    expect($token->fresh()->isExpired())->toBeTrue();
});
```

---

## 9. Run and validate

After writing the file, **run the test** and iterate until it passes:

```bash
# run only the newly created file
php artisan test tests/Feature/$1Test.php

# or filter by name
php artisan test --filter=$1
```

If it fails, **read the error, fix the test or flag the bug**, and run again. Do not deliver a test without having executed it.

## 10. Final checklist

- [ ] Each test has a single responsibility.
- [ ] Coverage includes the happy path **and** error paths (empty input, unauthorized, not found).
- [ ] Database state is clean between tests (`RefreshDatabase`).
- [ ] `Fake` used for queues, events, mail, HTTP, and storage.
- [ ] `expect()` preferred over `$this->assert*()`.
- [ ] No raw SQL or hardcoded IDs — always factories.
- [ ] Test names read as sentences.
- [ ] **The test was executed and is passing.**
