<!-- GSD:project-start source:PROJECT.md -->
## Project

**Ban Chim Bồ Câu — Laravel Backend**

Backend API Laravel cho hệ thống bán gia cầm & thú nuôi của mẹ: chim bồ câu (sống & thịt), gia cầm các loại. Hệ thống hỗ trợ khách tự đặt hàng online và mẹ nhập đơn thủ công qua Zalo/điện thoại. Tất cả giao tiếp qua RESTful API (frontend riêng sẽ tích hợp sau).

**Core Value:** Mẹ luôn biết còn bao nhiêu hàng và không bao giờ bán quá số lượng thực tế.

### Constraints

- **Tech Stack**: Laravel (PHP) — bắt buộc, mẹ đã quen hệ sinh thái này
- **Architecture**: Clean Architecture — tách Domain/Application/Infrastructure/Presentation
- **Language**: Tiếng Việt trong messages, validation errors, dashboard labels
- **Scale**: Hộ kinh doanh gia đình — không cần over-engineer cho traffic lớn
- **Database**: MySQL/PostgreSQL — cần hỗ trợ SELECT FOR UPDATE để tránh oversell
<!-- GSD:project-end -->

<!-- GSD:stack-start source:research/STACK.md -->
## Technology Stack

## Recommended Stack
### Core Framework
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| PHP | 8.3 | Runtime | 8.3 hits the sweet spot: fully supported by Laravel 12, best benchmark throughput in independent tests (444 req/s trimmed avg), all ecosystem packages compatible. 8.4 works but is newer with less ecosystem coverage. 8.2 is the minimum and has no reason to prefer it. |
| Laravel | 12.x | Web framework | Released February 24, 2025. Stable, LTS-adjacent (bug fixes until 2026-08, security until 2027-02). Built-in health check routes, improved service container boot performance, and it is the version all current packages target. Do NOT use Laravel 11 — it is the prior release with no advantages for a greenfield project. |
| MySQL | 8.0+ | Primary database | Constraint from PROJECT.md. `SELECT FOR UPDATE` / `lockForUpdate()` works reliably on MySQL 8.0. InnoDB row-level locking is the mechanism preventing oversell — it is battle-tested on this engine. PostgreSQL is technically superior for complex concurrent transactions but introduces unnecessary ops complexity for a family-scale app. |
### Auth
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Sanctum | bundled with Laravel 12 | API token auth for admin + public API | Sanctum is now the default API auth when you run `php artisan install:api`. It covers two patterns needed here: (1) stateful cookie-based auth for the admin SPA, (2) API token issuance for guest carts / frontend clients. No OAuth2 machinery, no extra infra. PROJECT.md already specifies Sanctum — research confirms it is the right call for first-party APIs. |
### API Layer
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel API Resources | bundled | Response transformation / serialization | Eloquent API Resources live in the Presentation layer and enforce the contract between domain models and JSON output. No extra package needed; they handle conditional attributes, relationships, and pagination wrappers. |
| spatie/laravel-data | ^4.20 | DTOs across Clean Architecture layers | Replaces manual DTO classes. A single `Data` object serves as: form request validation, DTO passing between Application and Domain layers, and API response transformer. Eliminates the duplication of defining the same shape three times. v4.20.1 released March 18, 2025 — actively maintained. |
| spatie/laravel-query-builder | ^6.3 | Filterable / sortable API endpoints | Translates `?filter[status]=pending&sort=-created_at` query params into Eloquent queries safely. Prevents hand-rolling filter logic in controllers. v6.3.6 as of November 2025. |
| infinitypaul/idempotency-laravel | latest | Duplicate order prevention | Implements `Idempotency-Key` header middleware with distributed lock-based concurrency control. Exactly what PROJECT.md requires for duplicate order prevention. Client sends a UUID key; server returns the cached response on replay. Simpler than hand-rolling the same pattern. |
### Database & Inventory Locking
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Eloquent | bundled | ORM | Stays in the Infrastructure layer (repositories). Domain entities must NOT extend Eloquent — use plain PHP objects in the Domain layer and Eloquent models only in Infrastructure repositories. |
| Laravel DB Transactions + `lockForUpdate()` | bundled | Oversell prevention | The correct and only reliable mechanism. Wrap inventory decrement in `DB::transaction()` with `->lockForUpdate()` on the stock row. MySQL InnoDB holds a row-level exclusive lock until the transaction commits, serializing concurrent purchases of the same SKU. No third-party package needed — this is built into Laravel's query builder. |
| Laravel Migrations | bundled | Schema management | Standard. Migrations belong in the Infrastructure layer. |
| spatie/laravel-permission | ^6.x | Role-based access (admin vs public) | Two roles: `admin` (mom) and `guest`/API consumers. Spatie Permission integrates with Laravel's Gate and Policy system, keeping authorization logic out of controllers. Overkill could be argued, but it standardizes role checking and is trivially uninstalled if needs change. |
### Queue / Jobs
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Queues (database driver) | bundled | Async job processing | For this scale (family business, low concurrency), the `database` queue driver is correct. Zero additional infrastructure — jobs land in the `jobs` table, workers run via `php artisan queue:work`. Redis adds operational complexity (memory sizing, eviction policy) with no benefit at this traffic level. Switch to Redis only if the queue table shows lock contention under load. |
| Laravel Horizon | NOT recommended for v1 | Queue dashboard | Horizon requires Redis. Since we use the database driver, Horizon does not apply. Use `failed_jobs` table + `php artisan queue:failed` for visibility. Add Horizon in a later phase if Redis is adopted. |
| Laravel Notifications | bundled | New order alerts | Built-in notification system supports mail, database, and webhook channels. Use for "new order" alerts to admin. No extra package needed for v1. |
- `SendNewOrderNotification` — fires after order confirmed
- `UpdateInventorySnapshot` (optional) — async cache warm for dashboard counts
### Testing
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Pest | ^3.x | Test runner + assertions | Pest 3 is the de-facto standard for new Laravel projects in 2025. Built on PHPUnit engine (same coverage, same CI integration), but with functional syntax that reduces boilerplate by ~40%. All major Laravel ecosystem packages (Spatie, Livewire, Filament) publish Pest tests. Laravel 12 starter kits default to Pest. |
| Pest Laravel plugin | bundled with Pest | Laravel-specific helpers | `artisan()`, `get()`, `post()`, `actingAs()` etc. — eliminates `RefreshDatabase` boilerplate. |
| Laravel factories | bundled | Test data generation | Model factories for seeding test databases. Essential for inventory and order scenario tests. |
### API Documentation
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Scribe | ^4.x | API docs generation | Scribe generates human-readable HTML docs AND an OpenAPI spec AND a Postman collection from your route definitions and docblocks, without requiring annotations on every method. It inspects Eloquent API Resources to infer response payloads automatically. PROJECT.md names Scribe explicitly. The alternative (L5-Swagger) requires Swagger annotations on every endpoint — too much maintenance overhead for a project where the developer is also the product owner. |
### Dev Tools
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Pint | bundled | Code style (PSR-12) | Zero-config PHP code formatter, ships with Laravel 12. Run in CI to enforce style. |
| Laravel Telescope | ^5.x | Local debugging | Request inspector, query logger, job monitor for local development. Do NOT deploy to production without strict auth guard — it exposes sensitive data. |
| PHP CS Fixer / PHPStan | latest | Static analysis | PHPStan at level 6+ catches type errors before runtime. Essential when implementing Clean Architecture with interfaces — static analysis validates that Infrastructure implementations satisfy Domain interfaces. |
| Laravel Sail | ^1.x | Local Docker dev environment | Provides MySQL 8 + Redis + MailHog without manual Docker setup. Optional — only use if the developer does not already have a local PHP/MySQL environment. |
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
## Installation
# Laravel 12 new project
# Install API auth scaffolding (installs Sanctum)
# Core packages
# API documentation
# Testing
# Dev / debug
# Static analysis
# Code style
# Laravel Pint ships with Laravel 12, no install needed
## Clean Architecture Layer Map
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
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

Conventions not yet established. Will populate as patterns emerge during development.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

Architecture not yet mapped. Follow existing patterns found in the codebase.
<!-- GSD:architecture-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd:quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd:debug` for investigation and bug fixing
- `/gsd:execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd:profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
