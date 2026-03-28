# Technology Stack

**Project:** Ban Chim Bo Cau — Laravel E-Commerce Backend
**Domain:** Poultry & pet sales, single-vendor, family business
**Researched:** 2026-03-28
**Overall confidence:** HIGH (core stack), MEDIUM (supporting libraries)

---

## Recommended Stack

### Core Framework

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP | 8.3 | Runtime | 8.3 hits the sweet spot: fully supported by Laravel 12, best benchmark throughput in independent tests (444 req/s trimmed avg), all ecosystem packages compatible. 8.4 works but is newer with less ecosystem coverage. 8.2 is the minimum and has no reason to prefer it. |
| Laravel | 12.x | Web framework | Released February 24, 2025. Stable, LTS-adjacent (bug fixes until 2026-08, security until 2027-02). Built-in health check routes, improved service container boot performance, and it is the version all current packages target. Do NOT use Laravel 11 — it is the prior release with no advantages for a greenfield project. |
| MySQL | 8.0+ | Primary database | Constraint from PROJECT.md. `SELECT FOR UPDATE` / `lockForUpdate()` works reliably on MySQL 8.0. InnoDB row-level locking is the mechanism preventing oversell — it is battle-tested on this engine. PostgreSQL is technically superior for complex concurrent transactions but introduces unnecessary ops complexity for a family-scale app. |

**Confidence:** HIGH — verified against laravel.com/docs/12.x/releases and PHP benchmark data.

---

### Auth

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Sanctum | bundled with Laravel 12 | API token auth for admin + public API | Sanctum is now the default API auth when you run `php artisan install:api`. It covers two patterns needed here: (1) stateful cookie-based auth for the admin SPA, (2) API token issuance for guest carts / frontend clients. No OAuth2 machinery, no extra infra. PROJECT.md already specifies Sanctum — research confirms it is the right call for first-party APIs. |

**Do NOT use:** Laravel Passport. It adds an OAuth2 server, client credential flows, and a separate database migration set. This project has one vendor and no third-party OAuth consumers. Passport is over-engineering for this scope.

**Confidence:** HIGH — official Laravel docs, community consensus across multiple 2025 sources.

---

### API Layer

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel API Resources | bundled | Response transformation / serialization | Eloquent API Resources live in the Presentation layer and enforce the contract between domain models and JSON output. No extra package needed; they handle conditional attributes, relationships, and pagination wrappers. |
| spatie/laravel-data | ^4.20 | DTOs across Clean Architecture layers | Replaces manual DTO classes. A single `Data` object serves as: form request validation, DTO passing between Application and Domain layers, and API response transformer. Eliminates the duplication of defining the same shape three times. v4.20.1 released March 18, 2025 — actively maintained. |
| spatie/laravel-query-builder | ^6.3 | Filterable / sortable API endpoints | Translates `?filter[status]=pending&sort=-created_at` query params into Eloquent queries safely. Prevents hand-rolling filter logic in controllers. v6.3.6 as of November 2025. |
| infinitypaul/idempotency-laravel | latest | Duplicate order prevention | Implements `Idempotency-Key` header middleware with distributed lock-based concurrency control. Exactly what PROJECT.md requires for duplicate order prevention. Client sends a UUID key; server returns the cached response on replay. Simpler than hand-rolling the same pattern. |

**Do NOT use:** `tymon/jwt-auth`. JWT adds stateless token complexity (token revocation is a solved problem in Sanctum via token deletion). JWT is useful for microservices that cannot share session state — irrelevant here.

**Confidence:** spatie packages HIGH (official Spatie docs + Packagist), idempotency package MEDIUM (June 2025 release, less community history — evaluate at implementation time).

---

### Database & Inventory Locking

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Eloquent | bundled | ORM | Stays in the Infrastructure layer (repositories). Domain entities must NOT extend Eloquent — use plain PHP objects in the Domain layer and Eloquent models only in Infrastructure repositories. |
| Laravel DB Transactions + `lockForUpdate()` | bundled | Oversell prevention | The correct and only reliable mechanism. Wrap inventory decrement in `DB::transaction()` with `->lockForUpdate()` on the stock row. MySQL InnoDB holds a row-level exclusive lock until the transaction commits, serializing concurrent purchases of the same SKU. No third-party package needed — this is built into Laravel's query builder. |
| Laravel Migrations | bundled | Schema management | Standard. Migrations belong in the Infrastructure layer. |
| spatie/laravel-permission | ^6.x | Role-based access (admin vs public) | Two roles: `admin` (mom) and `guest`/API consumers. Spatie Permission integrates with Laravel's Gate and Policy system, keeping authorization logic out of controllers. Overkill could be argued, but it standardizes role checking and is trivially uninstalled if needs change. |

**Inventory data model note:** The hybrid inventory (unit-based for live birds, weight/batch-based for meat) should be handled by a `stock_unit` enum column (`per_unit` | `per_kg`) and a single `quantity` decimal column, not two separate tables. `lockForUpdate()` works on either mode identically.

**Confidence:** HIGH — lockForUpdate() behavior verified against Laravel 12.x docs and multiple concurrency articles.

---

### Queue / Jobs

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Queues (database driver) | bundled | Async job processing | For this scale (family business, low concurrency), the `database` queue driver is correct. Zero additional infrastructure — jobs land in the `jobs` table, workers run via `php artisan queue:work`. Redis adds operational complexity (memory sizing, eviction policy) with no benefit at this traffic level. Switch to Redis only if the queue table shows lock contention under load. |
| Laravel Horizon | NOT recommended for v1 | Queue dashboard | Horizon requires Redis. Since we use the database driver, Horizon does not apply. Use `failed_jobs` table + `php artisan queue:failed` for visibility. Add Horizon in a later phase if Redis is adopted. |
| Laravel Notifications | bundled | New order alerts | Built-in notification system supports mail, database, and webhook channels. Use for "new order" alerts to admin. No extra package needed for v1. |

**Jobs to implement (application layer use cases):**
- `SendNewOrderNotification` — fires after order confirmed
- `UpdateInventorySnapshot` (optional) — async cache warm for dashboard counts

**Confidence:** HIGH — official Laravel queue docs, confirmed by community guidance for small apps.

---

### Testing

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Pest | ^3.x | Test runner + assertions | Pest 3 is the de-facto standard for new Laravel projects in 2025. Built on PHPUnit engine (same coverage, same CI integration), but with functional syntax that reduces boilerplate by ~40%. All major Laravel ecosystem packages (Spatie, Livewire, Filament) publish Pest tests. Laravel 12 starter kits default to Pest. |
| Pest Laravel plugin | bundled with Pest | Laravel-specific helpers | `artisan()`, `get()`, `post()`, `actingAs()` etc. — eliminates `RefreshDatabase` boilerplate. |
| Laravel factories | bundled | Test data generation | Model factories for seeding test databases. Essential for inventory and order scenario tests. |

**Critical tests to write (per PROJECT.md):**
1. Oversell prevention: two concurrent requests for the last unit of stock — only one succeeds.
2. Duplicate order: same `Idempotency-Key` sent twice — second request returns cached response, no second order row.
3. Inventory decrement: placing an order reduces stock by the correct amount.
4. Order state machine: invalid transitions are rejected.

**Do NOT use:** PHPUnit directly. It works, but new projects have no reason to prefer class-based xUnit style when Pest is available and the ecosystem has converged on it.

**Confidence:** HIGH — Pest official docs, community consensus from multiple 2025 sources.

---

### API Documentation

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Scribe | ^4.x | API docs generation | Scribe generates human-readable HTML docs AND an OpenAPI spec AND a Postman collection from your route definitions and docblocks, without requiring annotations on every method. It inspects Eloquent API Resources to infer response payloads automatically. PROJECT.md names Scribe explicitly. The alternative (L5-Swagger) requires Swagger annotations on every endpoint — too much maintenance overhead for a project where the developer is also the product owner. |

**Confidence:** HIGH — Scribe official docs at scribe.knuckles.wtf, PROJECT.md specifies it.

---

### Dev Tools

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Pint | bundled | Code style (PSR-12) | Zero-config PHP code formatter, ships with Laravel 12. Run in CI to enforce style. |
| Laravel Telescope | ^5.x | Local debugging | Request inspector, query logger, job monitor for local development. Do NOT deploy to production without strict auth guard — it exposes sensitive data. |
| PHP CS Fixer / PHPStan | latest | Static analysis | PHPStan at level 6+ catches type errors before runtime. Essential when implementing Clean Architecture with interfaces — static analysis validates that Infrastructure implementations satisfy Domain interfaces. |
| Laravel Sail | ^1.x | Local Docker dev environment | Provides MySQL 8 + Redis + MailHog without manual Docker setup. Optional — only use if the developer does not already have a local PHP/MySQL environment. |

**Confidence:** MEDIUM — these are standard Laravel dev tools; PHPStan level recommendation is opinion-based.

---

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Auth | Sanctum | Passport | OAuth2 overhead; no third-party consumers |
| Auth | Sanctum | tymon/jwt-auth | JWT revocation complexity; Sanctum tokens are simpler |
| Queue driver | database | Redis | Redis adds infra ops cost with no benefit at family-business scale |
| Queue monitor | (none v1) | Laravel Horizon | Requires Redis; defer until Redis is adopted |
| API docs | Scribe | L5-Swagger | Swagger annotations are verbose maintenance overhead |
| Testing | Pest | PHPUnit | Pest has same engine with better DX; ecosystem has converged on it |
| DTO | spatie/laravel-data | Manual DTOs | Eliminates boilerplate; handles validation + transformation in one object |
| DB | MySQL 8.0 | PostgreSQL | MySQL sufficient for this scale; team already familiar per PROJECT.md constraints |
| Framework | Laravel 12 | Laravel 11 | No reason to use prior version on greenfield |

---

## Installation

```bash
# Laravel 12 new project
composer create-project laravel/laravel ban-chim-bo-cau "^12.0"
cd ban-chim-bo-cau

# Install API auth scaffolding (installs Sanctum)
php artisan install:api

# Core packages
composer require spatie/laravel-data:"^4.20" \
    spatie/laravel-query-builder:"^6.3" \
    spatie/laravel-permission:"^6.0" \
    infinitypaul/idempotency-laravel

# API documentation
composer require --dev knuckleswtf/scribe

# Testing
composer require --dev pestphp/pest:"^3.0" \
    pestphp/pest-plugin-laravel \
    --with-all-dependencies

# Dev / debug
composer require --dev laravel/telescope:"^5.0"
php artisan telescope:install

# Static analysis
composer require --dev phpstan/phpstan \
    nunomaduro/larastan:"^3.0"

# Code style
# Laravel Pint ships with Laravel 12, no install needed
```

---

## Clean Architecture Layer Map

```
src/
  Domain/
    Order/
      Entities/Order.php          # Plain PHP, no Eloquent
      Entities/OrderItem.php
      ValueObjects/Money.php
      ValueObjects/StockUnit.php  # enum: per_unit | per_kg
      Repositories/OrderRepositoryInterface.php
    Inventory/
      Entities/Product.php
      Repositories/InventoryRepositoryInterface.php
      Events/StockDepleted.php

  Application/
    Order/
      UseCases/PlaceOrderUseCase.php    # calls lockForUpdate() via repo
      UseCases/UpdateOrderStatusUseCase.php
      DTOs/PlaceOrderData.php           # spatie/laravel-data
    Inventory/
      UseCases/DecrementStockUseCase.php
      DTOs/UpdateStockData.php

  Infrastructure/
    Persistence/
      Eloquent/Models/OrderModel.php    # Eloquent stays HERE
      Eloquent/Models/ProductModel.php
      Repositories/EloquentOrderRepository.php
      Repositories/EloquentInventoryRepository.php
    Queue/
      Jobs/SendNewOrderNotification.php

  Presentation/
    Http/
      Controllers/
        OrderController.php
        ProductController.php
        Admin/OrderEntryController.php
      Resources/
        OrderResource.php
        ProductResource.php
      Requests/
        PlaceOrderRequest.php
```

**Rule:** Dependencies only point inward. Presentation depends on Application, Application depends on Domain. Infrastructure depends on Domain (implements interfaces). Domain depends on nothing.

---

## Sources

- [Laravel 12 Release Notes](https://laravel.com/docs/12.x/releases) — framework version and requirements
- [Laravel Sanctum vs Passport Authentication Strategy for 2025](https://www.abbacustechnologies.com/laravel-sanctum-vs-passport-authentication-strategy-for-2025/) — auth comparison
- [spatie/laravel-data v4 Introduction](https://spatie.be/docs/laravel-data/v4/introduction) — DTO package docs
- [spatie/laravel-query-builder Packagist](https://packagist.org/packages/spatie/laravel-query-builder) — current version
- [Laravel Queues 12.x](https://laravel.com/docs/12.x/queues) — official queue docs
- [Preventing Data Races with Pessimistic Locking in Laravel](https://medium.com/@harrisrafto/preventing-data-races-with-pessimistic-locking-in-laravel-549596051457) — lockForUpdate() pattern
- [Scribe documentation](https://scribe.knuckles.wtf/laravel/reference/config) — API docs generator
- [infinitypaul/idempotency-laravel Packagist](https://packagist.org/packages/infinitypaul/idempotency-laravel) — idempotency middleware
- [Pest PHP official site](https://pestphp.com/) — testing framework
- [Laravel performance benchmarks — PHP 8.2 vs 8.3 vs 8.4 vs 8.5](https://sevalla.com/blog/laravel-benchmarks/) — PHP version performance data
- [Idempotency in Laravel 12 (2025): The Complete Guide](https://medium.com/@aiman.asfia/idempotency-in-laravel-12-2025-the-complete-guide-that-will-save-you-from-double-charges-3-am-0135d93f6dea) — idempotency patterns
