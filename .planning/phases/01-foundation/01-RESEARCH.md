# Phase 1: Foundation - Research

**Researched:** 2026-03-28
**Domain:** Laravel 12 Clean Architecture skeleton, Sanctum API auth, PHPStan/Larastan, Pest 4, Vietnamese localization
**Confidence:** HIGH

---

## Summary

Phase 1 establishes everything that every subsequent phase depends on. The three load-bearing decisions are: (1) the folder/namespace layout that enforces Clean Architecture boundaries, (2) the global JSON error envelope in `bootstrap/app.php` that every endpoint inherits, and (3) Sanctum token expiry configured from day one. Getting any of these wrong requires a full-layer refactor later.

The stack is fully validated: PHP 8.3 + Laravel 12 (12.x-dev is still in the 12 branch; Laravel 13 is not yet stable as of 2026-03-28 and is scheduled for Q1 2026 release), Pest 4 (latest stable, fully compatible with Laravel 12), Larastan 3.x (requires Laravel 11.16+, supports level 0–10), PHPStan 2.1.x. The `laravel/laravel` Packagist entry shows v13.1.0 for the application skeleton but the framework (`laravel/framework`) 13.x is still dev — pin to `^12.0` explicitly.

One important version correction from STACK.md: the research was written when Pest 3 was current. Pest 4.4.3 is now the latest stable (released 2026-03-21). Use `^4.0` not `^3.0`. The Pest 4 API is identical for Laravel projects; `pest-plugin-laravel` v4.1.0 is the corresponding plugin. All test examples in this document are Pest 4 compatible.

**Primary recommendation:** Build the four-layer directory structure (`app/Domain`, `app/Application`, `app/Infrastructure`, `app/Presentation`) first, write the PHPStan config to enforce boundaries, then wire Sanctum and the error handler — in that order. The skeleton must compile and analyse clean before any feature code is written.

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| AUTH-01 | Admin can log in with email/password via Sanctum | Sanctum `install:api` + `createToken()` pattern; admin user seeder |
| AUTH-02 | Admin token has a configured expiry (not permanent) | `config/sanctum.php` `expiration` key; `sanctum:prune-expired` scheduler |
| AUTH-03 | Guest customers do not need an account (anonymous cart with token) | Public routes require no auth; Sanctum token for guest cart deferred to Phase 3 |
| AUTH-04 | Admin can log out and revoke the token | `$request->user()->currentAccessToken()->delete()` |
| TECH-01 | Clean Architecture: Domain/Application/Infrastructure/Presentation layers; Domain has zero Laravel dependency | Custom folder structure; PHPStan custom rule enforces zero Eloquent/Laravel imports in `app/Domain/` |
| TECH-02 | All API routes versioned at `/api/v1/` from day one | Route prefix in `routes/api.php` |
| TECH-03 | All API errors return JSON envelope `{ code, message, errors }` | `withExceptions()` callback in `bootstrap/app.php`; `shouldRenderJsonWhen()` for all `api/*` requests |
| TECH-04 | Validation messages, status labels in Vietnamese | `php artisan lang:publish`; create `lang/vi/validation.php`; set `APP_LOCALE=vi` |
| TECH-06 | Test coverage for PlaceOrderAction (concurrent oversell) and idempotency (duplicate order) — scaffolding only in Phase 1 | Test file stubs for these two critical scenarios; full implementation in Phase 3 |
| TECH-07 | PHPStan level 6+ passes with zero Domain-layer Laravel imports flagged | `phpstan.neon` with level 6; custom `NoLaravelImportInDomainRule`; `php artisan analyse` wrapper |
</phase_requirements>

---

## Project Constraints (from CLAUDE.md)

| Directive | Type | Note |
|-----------|------|------|
| Laravel (PHP) — bắt buộc | Required stack | Cannot deviate |
| Clean Architecture — Domain/Application/Infrastructure/Presentation | Required architecture | Enforced by PHPStan |
| Tiếng Việt in messages, validation errors, dashboard labels | Required language | `APP_LOCALE=vi` + `lang/vi/` |
| MySQL/PostgreSQL with SELECT FOR UPDATE support | Required database | Use InnoDB tables |
| GSD workflow: no direct edits outside a GSD command | Workflow constraint | Use `/gsd:execute-phase` entry point |

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.3 | Runtime | Fully supported by Laravel 12; best-tested benchmark throughput; 8.5 is the system PHP but 8.3 is the project target per STACK.md |
| Laravel | ^12.0 | Framework | Stable, LTS-adjacent (security fixes until 2027-02); Laravel 13 not yet stable |
| MySQL | 8.0+ | Database | InnoDB row locks required for `lockForUpdate()` |
| Laravel Sanctum | bundled (via `install:api`) | Token auth | First-party API auth; no OAuth2 overhead |
| spatie/laravel-data | ^4.20 | DTOs | Latest: 4.20.1 (2026-03-18). Single object for validation + data transfer + transformation |
| Pest | ^4.0 | Testing | Latest: 4.4.3 (2026-03-21). De-facto standard for new Laravel projects; built on PHPUnit engine |
| pestphp/pest-plugin-laravel | ^4.0 | Laravel test helpers | Latest: 4.1.0 (2026-02-21). Provides `actingAs()`, `get()`, `post()`, etc. |
| larastan/larastan | ^3.0 | Static analysis | Latest: 3.9.3 (2026-02-20). PHPStan extension for Laravel type inference |
| phpstan/phpstan | ^2.0 | PHPStan engine | Latest: 2.1.44 (2026-03-25) |
| Laravel Pint | bundled | Code style | PSR-12 formatter, zero-config, ships with Laravel 12 |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| spatie/laravel-permission | ^6.0 | Role-based access | Admin vs. guest route protection; install in Phase 1 even if full use is Phase 2+ |
| Laravel Telescope | ^5.0 | Local debugging | Dev-only; query inspector, request log; gate behind auth in non-local envs |
| infinitypaul/idempotency-laravel | latest | Duplicate order prevention | Phase 3 use, but install scaffolding in Phase 1 (idempotency_keys table migration belongs here) |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Pest 4 | PHPUnit directly | PHPUnit works but Pest has cleaner syntax; ecosystem has converged on Pest for Laravel |
| Larastan | PHPStan alone | PHPStan alone cannot resolve Laravel magic (facades, Eloquent relations); Larastan is required |
| spatie/laravel-data | Manual DTO classes | Manual DTOs work but are boilerplate; spatie/laravel-data handles validation + casting + transformation |

**Installation:**
```bash
# Create project (pins to Laravel 12 branch)
composer create-project laravel/laravel ban-chim-bo-cau "^12.0"
cd ban-chim-bo-cau

# Install Sanctum via artisan (creates config/sanctum.php + migrations)
php artisan install:api

# Core packages
composer require \
    spatie/laravel-data:"^4.20" \
    spatie/laravel-permission:"^6.0"

# Testing
composer require --dev \
    pestphp/pest:"^4.0" \
    pestphp/pest-plugin-laravel:"^4.0" \
    --with-all-dependencies

# Initialize Pest (creates tests/Pest.php, converts TestCase)
./vendor/bin/pest --init

# Static analysis
composer require --dev \
    phpstan/phpstan:"^2.0" \
    larastan/larastan:"^3.0"

# Dev tools (optional)
composer require --dev laravel/telescope:"^5.0"
php artisan telescope:install
```

**Version verification:** Verified against Packagist on 2026-03-28. Valid approximately 30 days for stable packages.

---

## Architecture Patterns

### Recommended Project Structure

This project places the four Clean Architecture layers directly inside `app/` (not `src/`) to avoid autoloader changes from the Laravel default. The namespace root remains `App\`.

```
app/
├── Domain/                          # Pure PHP — zero framework imports
│   ├── Auth/
│   │   ├── Entities/
│   │   │   └── AdminUser.php        # POPO (not extends Model)
│   │   └── Repositories/
│   │       └── AdminUserRepositoryInterface.php
│   ├── Shared/
│   │   └── ValueObjects/
│   │       └── Email.php
│   └── .phpstan-domain-boundary     # Marker file (optional; see PHPStan section)
│
├── Application/                     # Orchestrates domain + infrastructure via interfaces
│   └── Auth/
│       └── Actions/
│           ├── LoginAdminAction.php
│           └── LogoutAdminAction.php
│
├── Infrastructure/                  # Laravel-specific implementations
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   └── Models/
│   │   │       └── UserModel.php    # extends Illuminate\Database\Eloquent\Model
│   │   ├── Repositories/
│   │   │   └── EloquentAdminUserRepository.php
│   │   └── Mappers/
│   │       └── UserMapper.php       # Eloquent model <-> domain entity
│   └── Providers/
│       └── RepositoryServiceProvider.php
│
├── Presentation/                    # HTTP delivery
│   └── Http/
│       ├── Controllers/
│       │   └── Auth/
│       │       └── AuthController.php
│       ├── Requests/
│       │   └── LoginRequest.php
│       └── Resources/
│           └── AdminTokenResource.php
│
├── Exceptions/                      # Custom domain exceptions
│   ├── Auth/
│   │   └── InvalidCredentialsException.php
│   └── Handler.php                  # (Not used in L12 — logic goes in bootstrap/app.php)
│
└── Providers/
    └── AppServiceProvider.php

routes/
├── api.php                          # All routes under /api/v1/ prefix
└── web.php                          # Empty (API-only project)

lang/
└── vi/
    ├── validation.php               # Vietnamese validation messages
    └── auth.php                     # Vietnamese auth messages

database/
└── migrations/
    ├── 0001_01_01_000000_create_users_table.php       # Bundled with Laravel
    ├── 0001_01_01_000001_create_cache_table.php       # Bundled
    ├── 0001_01_01_000002_create_jobs_table.php        # Bundled (database queue driver)
    └── 2026_03_28_000001_create_idempotency_keys_table.php  # Phase 1 addition

tests/
├── Pest.php                         # Pest bootstrap (auto-generated by --init)
├── Feature/
│   └── Auth/
│       ├── LoginTest.php
│       └── LogoutTest.php
└── Unit/
    └── Domain/
        └── .gitkeep                 # Placeholder; domain unit tests added in later phases
```

**Namespace examples:**
- `App\Domain\Auth\Entities\AdminUser` — pure PHP, no imports from `Illuminate\`
- `App\Infrastructure\Persistence\Eloquent\Models\UserModel` — extends `Illuminate\Database\Eloquent\Model`
- `App\Application\Auth\Actions\LoginAdminAction` — depends on `App\Domain\Auth\Repositories\AdminUserRepositoryInterface`
- `App\Presentation\Http\Controllers\Auth\AuthController` — depends on `App\Application\Auth\Actions\LoginAdminAction`

### Pattern 1: Admin Login Action

**What:** Controller calls Action; Action calls repository interface; Infrastructure provides Eloquent implementation.
**When to use:** Every admin operation follows this pattern.

```php
// Source: ARCHITECTURE.md pattern + Laravel 12 official docs

// app/Application/Auth/Actions/LoginAdminAction.php
namespace App\Application\Auth\Actions;

use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Exceptions\Auth\InvalidCredentialsException;
use Illuminate\Support\Facades\Hash; // NOTE: This IS an Illuminate import
// Application layer is allowed to use Illuminate — only Domain layer is forbidden

final class LoginAdminAction
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $adminUsers,
    ) {}

    public function handle(string $email, string $password): string
    {
        $user = $this->adminUsers->findByEmail($email);

        if ($user === null || !Hash::check($password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        return $this->adminUsers->createToken($user);
    }
}
```

### Pattern 2: Route Versioning

**What:** All API routes are nested under `/api/v1/` from day one.
**When to use:** Every route in the project.

```php
// Source: Laravel 12 official docs — routes/api.php

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public routes (no auth required)
    Route::post('/admin/login', [AuthController::class, 'login'])
        ->name('auth.login');

    // Admin-only routes (Sanctum protected)
    Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('auth.logout');
        // Future admin routes go here
    });

    // Public customer routes (future phases)
    Route::prefix('products')->name('products.')->group(function () {
        // Phase 2+
    });
});
```

### Pattern 3: Global JSON Error Envelope

**What:** All exceptions across the entire application are caught and normalized to `{ code, message, errors }`.
**When to use:** Configured once in `bootstrap/app.php`; applies to every request.

```php
// Source: Laravel 12 official docs — bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Middleware config
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Always render JSON for api/* routes (never HTML)
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request, \Throwable $e) => $request->is('api/*')
        );

        // 2. Normalize ALL exceptions to the envelope shape
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null; // Let default handle non-API routes
            }

            [$status, $code, $message, $errors] = match (true) {
                $e instanceof ValidationException => [
                    422,
                    'VALIDATION_ERROR',
                    'Dữ liệu không hợp lệ.',
                    $e->errors(),
                ],
                $e instanceof AuthenticationException => [
                    401,
                    'UNAUTHENTICATED',
                    'Bạn chưa đăng nhập.',
                    [],
                ],
                $e instanceof HttpException => [
                    $e->getStatusCode(),
                    'HTTP_ERROR',
                    $e->getMessage() ?: 'Lỗi yêu cầu.',
                    [],
                ],
                default => [
                    500,
                    'SERVER_ERROR',
                    'Đã xảy ra lỗi. Vui lòng thử lại.',
                    [],
                ],
            };

            return response()->json([
                'success' => false,
                'code'    => $code,
                'message' => $message,
                'errors'  => $errors,
            ], $status);
        });
    })
    ->create();
```

**CRITICAL NOTE:** In Laravel 12, the exception handler lives in `bootstrap/app.php` using the `withExceptions()` callback — NOT in `app/Exceptions/Handler.php`. The old `Handler.php` approach is Laravel 9 and earlier. Do not create `app/Exceptions/Handler.php`.

### Anti-Patterns to Avoid

- **Domain entity extends Model:** If any file in `app/Domain/` contains `extends Model`, the architecture is broken. PHPStan will catch this if the custom rule is configured (see PHPStan section).
- **Repository interface in Infrastructure/:** The interface must live in `Domain/` so the dependency arrow points inward. `Infrastructure/` imports from `Domain/`, never the reverse.
- **Setting `$order->status = 'new_value'` directly:** All status transitions go through the domain entity's `transitionTo()` method. Direct assignment bypasses the state machine.
- **Dispatch inside `DB::transaction()`:** Email/notification dispatch inside a transaction creates silent failure modes if the dispatch fails. Use `DB::afterCommit()` or dispatch after the transaction block.
- **Validation logic in controllers:** FormRequest classes handle all validation. Controllers only call Actions and return Resources.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Request validation + DTO transfer | Manual validation arrays + separate DTO classes | `spatie/laravel-data` | Single class serves as FormRequest, DTO, and API transformer; casting and type coercion built in |
| Idempotency key storage and lookup | Custom middleware + hand-rolled table query | `infinitypaul/idempotency-laravel` (or the idempotency table pattern from PITFALLS.md) | Race condition-safe concurrency control; unique index enforcement; cached response replay |
| Role-based route protection | Custom middleware checking a `role` column | `spatie/laravel-permission` | `Gate`, `Policy`, `@can` Blade directives all integrate; role assignment API included |
| API token auth with expiry | Custom token table + hash verification | Laravel Sanctum | Pruning, scopes/abilities, SPA cookie auth, token expiry — all built in |
| JSON error normalization | Per-exception try/catch in every controller | `withExceptions()` in `bootstrap/app.php` | One callback catches all; handles validation, 404, 500, AuthenticationException uniformly |
| Vietnamese validation messages | Hardcoding strings in FormRequest `messages()` | `lang/vi/validation.php` | Centralised; Laravel resolves automatically when `APP_LOCALE=vi`; community translation packages available |

**Key insight:** Laravel 12's exception handler callback (`withExceptions`) is the correct place for the JSON envelope. Attempting to add it at the middleware level or per-controller leads to incomplete coverage — unauthenticated 401s and 404s from the router fire before any middleware or controller is reached.

---

## PHPStan / Larastan Setup

### Installation and baseline config

```neon
# phpstan.neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
        - tests/

    level: 6

    # Exclude infrastructure models from "missing property" false positives
    ignoreErrors:
        - '#PHPDoc tag @var#'
```

### Enforcing zero Laravel/Eloquent imports in Domain layer

PHPStan does not have a built-in "forbidden import" rule out of the box. The correct approach is a custom PHPStan rule class registered in `phpstan.neon`. This is **not** available from larastan out of the box — it must be written for this project.

**Implementation:**

```php
// app/Rules/PHPStan/NoLaravelImportInDomainRule.php
namespace App\Rules\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Use_>
 */
final class NoLaravelImportInDomainRule implements Rule
{
    private const FORBIDDEN_PREFIXES = [
        'Illuminate\\',
        'Laravel\\',
    ];

    private const DOMAIN_PATH_SEGMENT = '/Domain/';

    public function getNodeType(): string
    {
        return Use_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $filePath = $scope->getFile();

        // Only enforce for files inside the Domain layer
        if (!str_contains($filePath, self::DOMAIN_PATH_SEGMENT)) {
            return [];
        }

        $errors = [];
        foreach ($node->uses as $use) {
            $importedName = $use->name->toString();
            foreach (self::FORBIDDEN_PREFIXES as $prefix) {
                if (str_starts_with($importedName, $prefix)) {
                    $errors[] = RuleErrorBuilder::message(
                        sprintf(
                            'Domain layer must not import from "%s". Found: "%s". Move this to Application or Infrastructure layer.',
                            rtrim($prefix, '\\'),
                            $importedName
                        )
                    )->build();
                    break;
                }
            }
        }

        return $errors;
    }
}
```

**Register in `phpstan.neon`:**

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/
    level: 6

services:
    -
        class: App\Rules\PHPStan\NoLaravelImportInDomainRule
        tags:
            - phpstan.rules.rule
```

**Artisan wrapper** — add to `composer.json` scripts so `php artisan analyse` works:

```json
"scripts": {
    "analyse": "./vendor/bin/phpstan analyse"
}
```

Then `php artisan analyse` works via the Composer bridge. The standard invocation remains `./vendor/bin/phpstan analyse`.

**Confidence:** The custom rule approach is MEDIUM confidence — the PHPStan Rule interface and `Use_` node type are verified from official docs. The exact file path check using `str_contains($filePath, '/Domain/')` is a pragmatic heuristic; the rule will need integration-testing during Wave 0 of Phase 1.

---

## Sanctum Setup

### Installation

```bash
php artisan install:api
```

This publishes `config/sanctum.php`, adds the `api` route file, and runs the `personal_access_tokens` migration.

### Token expiry configuration

```php
// config/sanctum.php
return [
    // Minutes until token expires. null = never (DO NOT use null for admin tokens)
    'expiration' => env('SANCTUM_TOKEN_EXPIRY', 43200), // 30 days default

    // Guest/anonymous token expiry (for Phase 3 cart tokens)
    // This is application-level logic; Sanctum itself uses one global expiry.
    // Solution: use token abilities to differentiate, or create tokens with
    // per-token expiry via the third argument to createToken():
    //   $user->createToken('admin', ['*'], now()->addDays(30))->plainTextToken
];
```

**Per-token expiry** (preferred over global config for mixed admin/guest use):

```php
// Source: Laravel 12 Sanctum official docs
$token = $user->createToken(
    name: 'admin-session',
    abilities: ['*'],
    expiresAt: now()->addDays(30),
)->plainTextToken;
```

### Token pruning

```php
// routes/console.php or app/Console/Kernel.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
```

### Route protection

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    // All admin routes
});
```

### Admin differentiation from guest

Phase 1 uses Sanctum's `auth:sanctum` middleware for admin routes. Guest (customer) routes have no auth middleware. The admin `User` model is seeded with a known admin account. `spatie/laravel-permission` can add an `admin` role in Phase 1 setup, but route-level differentiation is handled via the separate route group + `auth:sanctum`.

**Logout (token revocation):**

```php
// Source: Laravel 12 Sanctum official docs
public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Đã đăng xuất thành công.']);
}
```

---

## Global JSON Error Envelope

See the "Pattern 3: Global JSON Error Envelope" code example above in Architecture Patterns. Key points to reinforce for the planner:

1. **Location:** `bootstrap/app.php` `withExceptions()` callback — NOT `app/Exceptions/Handler.php`.
2. **Force JSON:** `shouldRenderJsonWhen()` ensures `api/*` requests never return HTML even when `Accept` header is absent.
3. **Envelope shape:**
   ```json
   {
     "success": false,
     "code": "VALIDATION_ERROR",
     "message": "Dữ liệu không hợp lệ.",
     "errors": { "email": ["Email không hợp lệ."] }
   }
   ```
4. **Machine-readable codes:** `VALIDATION_ERROR`, `UNAUTHENTICATED`, `NOT_FOUND`, `SERVER_ERROR` — frontend can switch on `code` for localized display without string-matching `message`.
5. **`errors` field:** Always present (empty object `{}` for non-validation errors). Never omit it.
6. **Custom domain exceptions** extend `DomainException` (PHP built-in) and are caught in the `default` arm of the `match` statement. Phase 2+ adds specific arms for `InsufficientStockException`, `InvalidOrderTransitionException`, etc.

---

## Vietnamese Language Setup

### Steps

1. **Publish language files:**
   ```bash
   php artisan lang:publish
   ```
   This creates `lang/en/` with all default Laravel messages.

2. **Set locale in `.env`:**
   ```env
   APP_LOCALE=vi
   APP_FALLBACK_LOCALE=en
   ```

3. **Create `lang/vi/validation.php`** with Vietnamese translations. Key entries:

   ```php
   // lang/vi/validation.php
   return [
       'accepted'             => ':attribute phải được chấp nhận.',
       'required'             => 'Trường :attribute là bắt buộc.',
       'required_if'          => 'Trường :attribute là bắt buộc khi :other là :value.',
       'email'                => ':attribute phải là địa chỉ email hợp lệ.',
       'min'                  => [
           'numeric' => ':attribute phải tối thiểu :min.',
           'string'  => ':attribute phải có ít nhất :min ký tự.',
       ],
       'max'                  => [
           'numeric' => ':attribute không được vượt quá :max.',
           'string'  => ':attribute không được vượt quá :max ký tự.',
       ],
       'unique'               => ':attribute này đã được sử dụng.',
       'confirmed'            => 'Xác nhận :attribute không khớp.',
       'numeric'              => ':attribute phải là số.',
       'integer'              => ':attribute phải là số nguyên.',
       'decimal'              => ':attribute phải có đúng :decimal chữ số thập phân.',
       'in'                   => ':attribute không hợp lệ.',
       'string'               => ':attribute phải là chuỗi ký tự.',
       'password'             => [
           'mixed'       => ':attribute phải chứa ít nhất một chữ hoa và một chữ thường.',
           'uncompromised' => ':attribute đã xuất hiện trong dữ liệu bị lộ. Vui lòng chọn :attribute khác.',
           'letters'     => ':attribute phải chứa ít nhất một chữ cái.',
           'numbers'     => ':attribute phải chứa ít nhất một chữ số.',
           'symbols'     => ':attribute phải chứa ít nhất một ký tự đặc biệt.',
       ],

       // Custom attribute names
       'attributes' => [
           'email'    => 'email',
           'password' => 'mật khẩu',
       ],
   ];
   ```

4. **Create `lang/vi/auth.php`:**
   ```php
   return [
       'failed'   => 'Thông tin đăng nhập không chính xác.',
       'password' => 'Mật khẩu không đúng.',
       'throttle' => 'Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau :seconds giây.',
   ];
   ```

5. **Community package (optional but recommended):** `caouecs/laravel-lang` provides complete community-maintained Vietnamese translations. Install with:
   ```bash
   composer require --dev caouecs/laravel-lang
   cp vendor/caouecs/laravel-lang/src/vi/validation.php lang/vi/validation.php
   ```
   Alternatively, use `laravel-lang/common` package for full coverage.

**Confidence:** HIGH — Laravel localization system is well-documented; Vietnamese files structure is verified.

---

## Pest 4 Setup

### Installation recap

```bash
composer require --dev pestphp/pest:"^4.0" pestphp/pest-plugin-laravel:"^4.0" --with-all-dependencies
./vendor/bin/pest --init
```

### Test structure for Clean Architecture

```php
// tests/Pest.php (auto-generated by --init)
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
```

### Feature test pattern (HTTP layer)

```php
// tests/Feature/Auth/LoginTest.php
<?php

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Hash;

describe('Admin Login', function () {
    it('returns a Sanctum token on valid credentials', function () {
        $admin = UserModel::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token'])
                 ->assertJsonPath('token', fn ($v) => is_string($v) && strlen($v) > 10);
    });

    it('returns VALIDATION_ERROR envelope on missing fields', function () {
        $response = $this->postJson('/api/v1/admin/login', []);

        $response->assertStatus(422)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'VALIDATION_ERROR')
                 ->assertJsonStructure(['success', 'code', 'message', 'errors']);
    });

    it('returns UNAUTHENTICATED_ERROR on wrong password', function () {
        UserModel::factory()->create(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/v1/admin/login', [
            'email'    => 'admin@example.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(401)
                 ->assertJsonPath('success', false)
                 ->assertJsonPath('code', 'INVALID_CREDENTIALS');
    });
});
```

### Unit test pattern (Domain layer — no DB)

```php
// tests/Unit/Domain/Auth/AdminUserTest.php
<?php

use App\Domain\Auth\Entities\AdminUser;

describe('AdminUser domain entity', function () {
    it('can be instantiated without Laravel dependencies', function () {
        $user = new AdminUser(
            id: 1,
            email: 'admin@test.com',
            passwordHash: '$2y$...',
        );

        expect($user->email)->toBe('admin@test.com');
    });
});
```

### PHPStan rule test

```php
// tests/Feature/PHPStan/DomainBoundaryTest.php
it('domain layer has zero Illuminate imports', function () {
    $domainFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(app_path('Domain'))
    );

    foreach ($domainFiles as $file) {
        if ($file->getExtension() !== 'php') continue;
        $contents = file_get_contents($file->getPathname());
        expect($contents)
            ->not->toContain('use Illuminate\\')
            ->not->toContain('use Laravel\\');
    }
})->coversNothing();
```

---

## Initial Database Schema Considerations

### Migrations that belong in Phase 1

| Migration | Reason for Phase 1 |
|-----------|-------------------|
| `create_users_table` | Bundled with `laravel/laravel`; admin user lives here |
| `create_cache_table` | Bundled; needed for rate limiting + app cache |
| `create_jobs_table` | Bundled; database queue driver; needed even if no jobs yet |
| `create_personal_access_tokens_table` | Created by `php artisan install:api` (Sanctum) |
| `create_idempotency_keys_table` | Phase 3 feature but the table definition is a Phase 1 concern — PITFALLS.md and ARCHITECTURE.md both specify it as a foundation element. Create now; the middleware is wired in Phase 3. |

### Migrations that do NOT belong in Phase 1

All product, inventory, order, cart, payment, and delivery tables belong in Phases 2 and 3. Do not create them now — the domain entities for those are not defined yet, and premature schema locks in migrations.

### `idempotency_keys` table schema

```php
// database/migrations/2026_03_28_000001_create_idempotency_keys_table.php
Schema::create('idempotency_keys', function (Blueprint $table) {
    $table->id();
    $table->string('key', 100)->unique(); // UUID from client
    $table->string('status', 20)->default('processing'); // processing | completed
    $table->integer('response_status')->nullable();
    $table->longText('response_body')->nullable();
    $table->string('user_identifier', 100)->nullable(); // IP or user_id
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->index(['key', 'status']);
    $table->index('expires_at');
});
```

### `users` table additions

The default Laravel `users` table needs no changes for Phase 1. The admin user is seeded via `DatabaseSeeder` or a dedicated `AdminSeeder`. Avoid adding a separate `admins` table in Phase 1 — `spatie/laravel-permission` handles role differentiation.

---

## Common Pitfalls

### Pitfall 1: Domain entity file contains `use Illuminate\...`

**What goes wrong:** A developer creates a domain entity by running `php artisan make:model Product` and places it in `Domain/`. The generated file extends `Illuminate\Database\Eloquent\Model`.
**Why it happens:** `artisan make:model` always generates an Eloquent model, not a POPO.
**How to avoid:** Never use `artisan make:model` for domain entities. Create domain entity files manually. PHPStan custom rule will catch violations on every `./vendor/bin/phpstan analyse` run.
**Warning signs:** `php artisan analyse` reports errors in `app/Domain/**` files.

### Pitfall 2: `bootstrap/app.php` exception handler missing `shouldRenderJsonWhen()`

**What goes wrong:** An unauthenticated request to `/api/v1/admin/orders` (with no `Accept: application/json` header) returns a Laravel HTML redirect to `/login` instead of a JSON 401.
**Why it happens:** Sanctum's `auth:sanctum` middleware throws `AuthenticationException`, which defaults to redirecting without `shouldRenderJsonWhen()` configured.
**How to avoid:** Always add `$exceptions->shouldRenderJsonWhen(fn($request, $e) => $request->is('api/*'))` in `withExceptions()`. Test with `curl` without `Accept` header.
**Warning signs:** Postman shows HTML body on 401 responses.

### Pitfall 3: Sanctum `expiration` left as `null`

**What goes wrong:** `config/sanctum.php` ships with `expiration => null`. This means all tokens never expire. An admin token issued today is valid indefinitely.
**Why it happens:** Developers copy the config without reading the comment.
**How to avoid:** Set `expiration` to a non-null value in `config/sanctum.php` AND add the `sanctum:prune-expired` scheduler before any token is issued.
**Warning signs:** `personal_access_tokens` table has rows with `expires_at = NULL`.

### Pitfall 4: PHPStan custom rule not running because class is not autoloaded

**What goes wrong:** `NoLaravelImportInDomainRule` is registered in `phpstan.neon` but PHPStan throws "Class not found". The rule class lives in `app/Rules/PHPStan/` but Composer's autoloader may not pick it up without a full `composer dump-autoload`.
**Why it happens:** New classes added to `app/` during analysis setup aren't picked up without autoloader refresh.
**How to avoid:** Run `composer dump-autoload` after creating the rule class. Verify with `./vendor/bin/phpstan analyse --debug`.
**Warning signs:** PHPStan output includes "Class App\Rules\PHPStan\NoLaravelImportInDomainRule not found".

### Pitfall 5: `laravel/laravel ^13` installed instead of `^12`

**What goes wrong:** `composer create-project laravel/laravel project-name` without a version constraint installs the latest skeleton (v13.1.0 at time of research), which may pull in `laravel/framework` 13.x-dev.
**Why it happens:** No version pinned.
**How to avoid:** Always use `composer create-project laravel/laravel project-name "^12.0"`.
**Warning signs:** `composer.json` shows `"laravel/framework": "^13.0"`.

### Pitfall 6: Language files not published before setting `APP_LOCALE=vi`

**What goes wrong:** Setting `APP_LOCALE=vi` without creating `lang/vi/validation.php` causes Laravel to fall back silently to English, or throws missing key warnings.
**Why it happens:** Laravel publishes only `lang/en/` by default.
**How to avoid:** Run `php artisan lang:publish` first, then create `lang/vi/` files.
**Warning signs:** Validation errors appear in English despite `APP_LOCALE=vi`.

---

## Code Examples

### Login controller (Presentation layer)

```php
// Source: Laravel 12 Sanctum docs + ARCHITECTURE.md patterns
// app/Presentation/Http/Controllers/Auth/AuthController.php

namespace App\Presentation\Http\Controllers\Auth;

use App\Application\Auth\Actions\LoginAdminAction;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Presentation\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    public function __construct(
        private readonly LoginAdminAction $loginAction,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->loginAction->handle(
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        return response()->json(['token' => $token], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Đã đăng xuất thành công.',
        ]);
    }
}
```

### LoginRequest (Presentation layer — allowed to use Illuminate)

```php
// app/Presentation/Http/Requests/LoginRequest.php
namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
```

### RepositoryServiceProvider (Infrastructure layer)

```php
// app/Infrastructure/Providers/RepositoryServiceProvider.php
namespace App\Infrastructure\Providers;

use App\Domain\Auth\Repositories\AdminUserRepositoryInterface;
use App\Infrastructure\Persistence\Repositories\EloquentAdminUserRepository;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AdminUserRepositoryInterface::class,
            EloquentAdminUserRepository::class,
        );
    }
}
```

### Domain entity (zero Illuminate imports)

```php
// app/Domain/Auth/Entities/AdminUser.php
namespace App\Domain\Auth\Entities;

// NO Illuminate\ imports here
final class AdminUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $passwordHash,
    ) {}
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `app/Exceptions/Handler.php` extend class | `withExceptions()` callback in `bootstrap/app.php` | Laravel 11 | Old Handler.php still works but is the legacy path; use new approach for L12 greenfield |
| `composer require pestphp/pest:^3.0` | `^4.0` (Pest 4 released 2026) | 2026 | Pest 4 adds browser testing support; API is backward-compatible for unit/feature tests |
| `nunomaduro/larastan` package name | `larastan/larastan` (new package name) | Larastan 2.0 | Old package name redirects but new projects should use `larastan/larastan` |
| `php artisan sanctum:prune-expired` | `php artisan sanctum:prune-expired --hours=24` | Laravel 12 | The `--hours` flag specifies minimum age; without it, all expired tokens are pruned |

**Deprecated/outdated:**
- `app/Exceptions/Handler.php`: Not removed, but the recommended pattern in Laravel 12 greenfield is `withExceptions()` in `bootstrap/app.php`. Creating Handler.php is not wrong but adds maintenance overhead.
- `Pest ^3.x`: Superseded by Pest 4. The STACK.md recommends `^3.x` — update to `^4.0`.
- `phpunit/phpunit` direct use: Still available as Pest's engine but new projects should not configure PHPUnit directly.

---

## Open Questions

1. **`laravel new` vs `composer create-project` for version pinning**
   - What we know: The Laravel installer (`laravel new`) prompts interactively for Pest and database. `composer create-project` is non-interactive and version-pinnable.
   - What's unclear: Whether `laravel new --pest --no-interaction` with a version flag pins to `^12.0` reliably or pulls latest skeleton.
   - Recommendation: Use `composer create-project laravel/laravel ban-chim-bo-cau "^12.0"` to guarantee version pinning. Then manually install Pest as shown in the Installation section.

2. **PHPStan custom rule — test coverage**
   - What we know: The `NoLaravelImportInDomainRule` approach is architecturally correct based on PHPStan's `Rule` interface.
   - What's unclear: Whether any edge cases (e.g., `use function Illuminate\...`, PHP built-in namespace `use`) trigger false positives.
   - Recommendation: Create the rule in Wave 0, then deliberately add a test `use Illuminate\Database\Eloquent\Model;` to a Domain file, run `phpstan analyse`, and confirm the error is reported. Remove the test after confirmation.

3. **Admin user model location**
   - What we know: Laravel's default `User` model lives in `app/Models/User.php`. The Clean Architecture places Eloquent models in `app/Infrastructure/Persistence/Eloquent/Models/`.
   - What's unclear: Whether to keep the default `User.php` (to avoid breaking Laravel's auth scaffolding) or move it to Infrastructure immediately.
   - Recommendation: Move immediately in Phase 1 and update all references (`config/auth.php`, `database/factories/UserFactory.php`). The cost of moving later is higher. Update `AUTH_MODEL` in `config/auth.php` to `App\Infrastructure\Persistence\Eloquent\Models\UserModel`.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | All | Yes | 8.5.2 | Use 8.3 features only (project target) |
| Composer | Package management | Yes | 2.9.5 | — |
| MySQL | Database | Not verified locally | — | Use SQLite for local dev (`DB_CONNECTION=sqlite`) |
| Git | Version control | Assumed present (git repo exists) | — | — |

**Missing dependencies with no fallback:**
- MySQL 8.0: Not verified as running locally. The project will need MySQL available (via Sail, Docker, or local install) for full integration tests. PHPStan and Pest unit tests can run without MySQL.

**Missing dependencies with fallback:**
- MySQL for local dev: Use `DB_CONNECTION=sqlite` in `.env.testing` to run Feature tests without MySQL overhead. SQLite supports `lockForUpdate()` via WAL mode in Laravel 12.

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 4.4.3 |
| Config file | `phpunit.xml` (generated by `pest --init`; Pest reads it) |
| Quick run command | `./vendor/bin/pest --filter=Auth` |
| Full suite command | `php artisan test` (or `./vendor/bin/pest`) |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| AUTH-01 | `POST /api/v1/admin/login` with valid credentials returns `{ token }` | Feature | `./vendor/bin/pest tests/Feature/Auth/LoginTest.php` | ❌ Wave 0 |
| AUTH-01 | Login with invalid credentials returns 401 + envelope | Feature | `./vendor/bin/pest tests/Feature/Auth/LoginTest.php` | ❌ Wave 0 |
| AUTH-02 | Token has non-null `expires_at` after creation | Feature | `./vendor/bin/pest tests/Feature/Auth/LoginTest.php` | ❌ Wave 0 |
| AUTH-04 | `POST /api/v1/admin/logout` deletes the current token | Feature | `./vendor/bin/pest tests/Feature/Auth/LogoutTest.php` | ❌ Wave 0 |
| AUTH-04 | Subsequent request with deleted token returns 401 | Feature | `./vendor/bin/pest tests/Feature/Auth/LogoutTest.php` | ❌ Wave 0 |
| TECH-01 | No `use Illuminate\` in any `app/Domain/**` file | Unit (file scan) | `./vendor/bin/pest tests/Unit/PHPStan/DomainBoundaryTest.php` | ❌ Wave 0 |
| TECH-02 | All routes are prefixed `/api/v1/` | Feature | `./vendor/bin/pest tests/Feature/Routing/RouteVersioningTest.php` | ❌ Wave 0 |
| TECH-03 | Any invalid JSON request returns `{ success, code, message, errors }` envelope | Feature | `./vendor/bin/pest tests/Feature/Errors/JsonEnvelopeTest.php` | ❌ Wave 0 |
| TECH-03 | Unauthenticated request to admin route returns 401 with envelope (no HTML) | Feature | `./vendor/bin/pest tests/Feature/Errors/JsonEnvelopeTest.php` | ❌ Wave 0 |
| TECH-03 | Request to non-existent route returns 404 with envelope | Feature | `./vendor/bin/pest tests/Feature/Errors/JsonEnvelopeTest.php` | ❌ Wave 0 |
| TECH-04 | Validation error message for missing email field is in Vietnamese | Feature | `./vendor/bin/pest tests/Feature/Auth/LoginTest.php` | ❌ Wave 0 |
| TECH-06 | Stub test file for PlaceOrderAction concurrent oversell exists | Unit (stub) | `./vendor/bin/pest tests/Unit/Application/Order/PlaceOrderActionTest.php` | ❌ Wave 0 |
| TECH-07 | `./vendor/bin/phpstan analyse` exits 0 with no errors | Static analysis | `./vendor/bin/phpstan analyse` | ❌ Wave 0 |
| TECH-07 | Adding `use Illuminate\Database\Eloquent\Model;` to a Domain file causes PHPStan error | Static analysis (manual verify) | `./vendor/bin/phpstan analyse` | — manual |

### Sampling Rate
- **Per task commit:** `./vendor/bin/pest --filter=Auth` (auth tests only, ~5 seconds)
- **Per wave merge:** `php artisan test` (full suite)
- **Phase gate:** Full suite green + `./vendor/bin/phpstan analyse` exits 0 before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Auth/LoginTest.php` — covers AUTH-01, AUTH-02, TECH-04
- [ ] `tests/Feature/Auth/LogoutTest.php` — covers AUTH-04
- [ ] `tests/Feature/Errors/JsonEnvelopeTest.php` — covers TECH-03
- [ ] `tests/Feature/Routing/RouteVersioningTest.php` — covers TECH-02
- [ ] `tests/Unit/PHPStan/DomainBoundaryTest.php` — covers TECH-01
- [ ] `tests/Unit/Application/Order/PlaceOrderActionTest.php` — stub file for TECH-06 (full implementation Phase 3)
- [ ] `tests/Pest.php` — generated by `./vendor/bin/pest --init`
- [ ] `phpstan.neon` — PHPStan config with level 6 + `NoLaravelImportInDomainRule`
- [ ] `app/Rules/PHPStan/NoLaravelImportInDomainRule.php` — custom rule class

---

## Sources

### Primary (HIGH confidence)
- Laravel 12.x official docs (laravel.com/docs/12.x) — installation, Sanctum, error handling, localization
- Laravel 12.x Sanctum docs (laravel.com/docs/12.x/sanctum) — token expiry, `createToken()` signature, pruning
- Laravel 12.x Error Handling docs (laravel.com/docs/12.x/errors) — `withExceptions()`, `shouldRenderJsonWhen()`
- Laravel 12.x Localization docs (laravel.com/docs/12.x/localization) — `lang:publish`, Vietnamese lang file structure
- Packagist: `nunomaduro/larastan` v3.9.3 (2026-02-20)
- Packagist: `phpstan/phpstan` v2.1.44 (2026-03-25)
- Packagist: `pestphp/pest` v4.4.3 (2026-03-21)
- Packagist: `pestphp/pest-plugin-laravel` v4.1.0 (2026-02-21)
- Packagist: `spatie/laravel-data` v4.20.1 (2026-03-18)
- PHPStan custom rules docs (phpstan.org/developing-extensions/rules) — `Rule` interface, `Use_` AST node

### Secondary (MEDIUM confidence)
- Larastan GitHub README (github.com/larastan/larastan) — configuration example, level support
- caouecs/laravel-lang GitHub — Vietnamese translation files structure

### Tertiary (LOW confidence)
- WebSearch results re: PHPStan custom rule for namespace imports — approach verified against PHPStan docs, but specific `str_contains($filePath, '/Domain/')` pattern is pragmatic and needs Wave 0 validation

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all package versions verified against Packagist on 2026-03-28
- Architecture: HIGH — derived from project's own ARCHITECTURE.md + PITFALLS.md which were previously researched at HIGH confidence
- Sanctum setup: HIGH — verified against Laravel 12 official docs
- PHPStan custom rule: MEDIUM — interface verified; exact rule implementation needs Wave 0 integration test
- Vietnamese localization: HIGH — standard Laravel localization system
- Pest 4 patterns: HIGH — official Pest docs; version bump from 3 to 4 confirmed

**Research date:** 2026-03-28
**Valid until:** 2026-04-28 (stable stack; 30-day window before package versions drift)
