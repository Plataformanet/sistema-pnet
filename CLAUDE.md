# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository, please always reply in Brazilian Portuguese.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+), `stancl/tenancy` v3.9, Laravel Fortify, Spatie Laravel Permission
- **Frontend**: Vue 3 + TypeScript, Inertia.js v2, TailwindCSS v4, Reka UI, TanStack Vue Table
- **Database**: MySQL (central + per-tenant databases)

## Commands

```bash
# Start dev server (PHP + queue + Vite concurrently)
sail composer run dev

# Run tests
sail pest

# Reset central DB and seed
sail composer run central-migrate-fresh-seed

# Reset all tenant DBs
sail composer run tenants-migrate-fresh

# PHP code style (Pint)
sail pint

# TypeScript check + frontend build
sail npm run build
```

Single test: `sail php artisan test --filter=TestClassName`

## Architecture

### Multi-Tenancy

The app uses subdomain/domain-based tenancy via `stancl/tenancy`. There are **two database tiers**:

- **Central DB**: `tenants`, `domains`, `plans`, `modules`, `plan_modules`, `subscriptions`, `payments`, `coupons`, `central_users`
- **Tenant DB** (one per tenant, named `tenant{uuid}`): `users`, roles/permissions (Spatie), `contacts`, `clients`, `suppliers`, `employees`, `products`, `services`, `settings`, etc.

Migrations are split: `database/migrations/central/` and `database/migrations/tenant/`.

Routes live in `routes/web.php` (central, e.g. registration) and `routes/tenant.php` (tenant context, behind `InitializeTenancyByDomain` middleware).

All tenant DB operations must run inside `$tenant->run(fn() => ...)` to switch the database connection. The global `tenant()` helper returns the current tenant.

### Contact as Base Entity

`Contact` is the shared base for clients, suppliers, employees, and proponents. Each specialization (e.g. `Client`, `Supplier`) has a `contact_id` FK and `belongsTo(Contact::class)`. `Contact` owns the common fields (name/CPF-CNPJ/email/phone) and has a `hasOne(Address::class)`. When creating any of these entities, always create the `Contact` first (via `ContactService`), then create the specialization.

### Service Layer Pattern

Controllers inject services and delegate all logic to them. Services:
- Always accept `Tenant $tenant` as a parameter
- Run all DB writes inside `$tenant->run()` to switch to the tenant connection
- Throw exceptions on failure (controllers catch and redirect with flash messages)

**Transactions:** for regular tenant-scoped methods, wrap writes in `DB::transaction(fn() => ...)` **inside** `$tenant->run()`. The connection is already switched and stable before the closure runs, so the transaction begins and commits on the same tenant connection — `DB::transaction()` here is safe and preferred (auto-rollback on exception + deadlock retry).

The manual `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()` (with try/catch) is **only** for `TenantService::store` (tenant creation). There, `Tenant::create` triggers the `CreateDatabase` / `MigrateDatabase` / `SeedDatabase` job pipeline, which reconnects the database mid-flow. Wrapping that in `DB::transaction()` throws `PDOException: There is no active transaction`, because the connection is purged between `begin` and `commit`. So the manual transaction must be opened **inside `$tenant->run()`** (after the connection has stabilized on the tenant), around only the tenant-side inserts.

### Permissions & Roles (Spatie)

Permissions are seeded per-tenant when the tenant is created (via `TenantService::store`). The `admin` role receives all permissions. Permission strings follow the pattern `module.resource.action` (e.g. `registrations.clients.view`). Routes are guarded with `->middleware('permission:...')`. The frontend receives the authenticated user's permissions via Inertia shared data (`usePage().props.permissions`).

### Module System

Plans include modules; modules have associated permissions. When a tenant is created, the plan's modules are attached to the tenant and their permissions are seeded into the tenant DB.

### Frontend Structure

Pages mirror the route/feature structure under `resources/js/pages/`:
- `central/` — public/central pages
- `tenant/{feature}/{subfeature}/{list|create|edit}/` — per-feature views

Each feature folder typically contains `List.vue`, `Create.vue`, `Edit.vue`, a shared `components/` dir (e.g. `ClientForm.vue`), and a `columns.ts` for TanStack Table definitions.

Inertia shared props available everywhere: `auth.user`, `tenant`, `permissions` (array of permission strings), `flash.success/error`.

<!-- rtk-instructions v2 -->
# RTK (Rust Token Killer) - Token-Optimized Commands

## Golden Rule

**Always prefix commands with `rtk`**. If RTK has a dedicated filter, it uses it. If not, it passes through unchanged. This means RTK is always safe to use.

**Important**: Even in command chains with `&&`, use `rtk`:
```bash
# ❌ Wrong
git add . && git commit -m "msg" && git push

# ✅ Correct
rtk git add . && rtk git commit -m "msg" && rtk git push
```

## RTK Commands by Workflow

### Build & Compile (80-90% savings)
```bash
rtk cargo build         # Cargo build output
rtk cargo check         # Cargo check output
rtk cargo clippy        # Clippy warnings grouped by file (80%)
rtk tsc                 # TypeScript errors grouped by file/code (83%)
rtk lint                # ESLint/Biome violations grouped (84%)
rtk prettier --check    # Files needing format only (70%)
rtk next build          # Next.js build with route metrics (87%)
```

### Test (60-99% savings)
```bash
rtk cargo test          # Cargo test failures only (90%)
rtk go test             # Go test failures only (90%)
rtk jest                # Jest failures only (99.5%)
rtk vitest              # Vitest failures only (99.5%)
rtk playwright test     # Playwright failures only (94%)
rtk pytest              # Python test failures only (90%)
rtk rake test           # Ruby test failures only (90%)
rtk rspec               # RSpec test failures only (60%)
rtk test <cmd>          # Generic test wrapper - failures only
```

### Git (59-80% savings)
```bash
rtk git status          # Compact status
rtk git log             # Compact log (works with all git flags)
rtk git diff            # Compact diff (80%)
rtk git show            # Compact show (80%)
rtk git add             # Ultra-compact confirmations (59%)
rtk git commit          # Ultra-compact confirmations (59%)
rtk git push            # Ultra-compact confirmations
rtk git pull            # Ultra-compact confirmations
rtk git branch          # Compact branch list
rtk git fetch           # Compact fetch
rtk git stash           # Compact stash
rtk git worktree        # Compact worktree
```

Note: Git passthrough works for ALL subcommands, even those not explicitly listed.

### GitHub (26-87% savings)
```bash
rtk gh pr view <num>    # Compact PR view (87%)
rtk gh pr checks        # Compact PR checks (79%)
rtk gh run list         # Compact workflow runs (82%)
rtk gh issue list       # Compact issue list (80%)
rtk gh api              # Compact API responses (26%)
```

### JavaScript/TypeScript Tooling (70-90% savings)
```bash
rtk pnpm list           # Compact dependency tree (70%)
rtk pnpm outdated       # Compact outdated packages (80%)
rtk pnpm install        # Compact install output (90%)
rtk npm run <script>    # Compact npm script output
rtk npx <cmd>           # Compact npx command output
rtk prisma              # Prisma without ASCII art (88%)
```

### Files & Search (60-75% savings)
```bash
rtk ls <path>           # Tree format, compact (65%)
rtk read <file>         # Code reading with filtering (60%)
rtk grep <pattern>      # Search grouped by file (75%). Format flags (-c, -l, -L, -o, -Z) run raw.
rtk find <pattern>      # Find grouped by directory (70%)
```

### Analysis & Debug (70-90% savings)
```bash
rtk err <cmd>           # Filter errors only from any command
rtk log <file>          # Deduplicated logs with counts
rtk json <file>         # JSON structure without values
rtk deps                # Dependency overview
rtk env                 # Environment variables compact
rtk summary <cmd>       # Smart summary of command output
rtk diff                # Ultra-compact diffs
```

### Infrastructure (85% savings)
```bash
rtk docker ps           # Compact container list
rtk docker images       # Compact image list
rtk docker logs <c>     # Deduplicated logs
rtk kubectl get         # Compact resource list
rtk kubectl logs        # Deduplicated pod logs
```

### Network (65-70% savings)
```bash
rtk curl <url>          # Compact HTTP responses (70%)
rtk wget <url>          # Compact download output (65%)
```

### Meta Commands
```bash
rtk gain                # View token savings statistics
rtk gain --history      # View command history with savings
rtk discover            # Analyze Claude Code sessions for missed RTK usage
rtk proxy <cmd>         # Run command without filtering (for debugging)
rtk init                # Add RTK instructions to CLAUDE.md
rtk init --global       # Add RTK to ~/.claude/CLAUDE.md
```

## Token Savings Overview

| Category | Commands | Typical Savings |
|----------|----------|-----------------|
| Tests | vitest, playwright, cargo test | 90-99% |
| Build | next, tsc, lint, prettier | 70-87% |
| Git | status, log, diff, add, commit | 59-80% |
| GitHub | gh pr, gh run, gh issue | 26-87% |
| Package Managers | pnpm, npm, npx | 70-90% |
| Files | ls, read, grep, find | 60-75% |
| Infrastructure | docker, kubectl | 85% |
| Network | curl, wget | 65-70% |

Overall average: **60-90% token reduction** on common development operations.
<!-- /rtk-instructions -->
