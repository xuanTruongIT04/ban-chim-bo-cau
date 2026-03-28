---
phase: 01-foundation
plan: 01
subsystem: infra
tags: [laravel, php, clean-architecture, phpstan, larastan, pest, sanctum, sqlite]

# Dependency graph
requires: []
provides:
  - Laravel 12 project skeleton bootable with php artisan serve
  - 4-layer Clean Architecture directory structure (Domain/Application/Infrastructure/Presentation)
  - PHPStan level 6 with custom NoLaravelImportInDomainRule enforcing domain purity
  - UserModel in Infrastructure layer with HasApiTokens, wired in config/auth.php
  - AdminUser pure-PHP domain entity with zero Illuminate imports
  - AdminUserRepositoryInterface in Domain layer
  - All API routes prefixed /api/v1/ via route group
  - .env.testing with in-memory SQLite for test isolation
  - Pest 4 test suite with RefreshDatabase for Feature tests
  - 16 Wave 0 test stubs (todo) covering AUTH-01, AUTH-02, AUTH-04, TECH-02, TECH-03, TECH-04, TECH-06
  - DomainBoundaryTest (passing green) verifying no Illuminate imports in Domain layer
affects: [01-02, all subsequent plans]

# Tech tracking
tech-stack:
  added:
    - laravel/framework:^12.0
    - laravel/sanctum:^4.0 (via install:api)
    - spatie/laravel-data:^4.20
    - spatie/laravel-permission:^6.0
    - pestphp/pest:^4.0
    - pestphp/pest-plugin-laravel:^4.0
    - phpstan/phpstan:^2.0
    - larastan/larastan:^3.0
  patterns:
    - Domain entities are pure PHP POPOs with zero framework imports
    - Infrastructure Eloquent models extend Authenticatable, live in app/Infrastructure/Persistence/Eloquent/Models/
    - Repository interfaces defined in Domain, implementations bound in Infrastructure providers
    - All API routes group-prefixed with /api/v1/ in routes/api.php
    - PHPStan custom rule (NoLaravelImportInDomainRule) enforces domain boundary via phpstan.rules.rule service tag

key-files:
  created:
    - app/Domain/Auth/Entities/AdminUser.php
    - app/Domain/Auth/Repositories/AdminUserRepositoryInterface.php
    - app/Infrastructure/Persistence/Eloquent/Models/UserModel.php
    - app/Infrastructure/Providers/RepositoryServiceProvider.php
    - app/Rules/PHPStan/NoLaravelImportInDomainRule.php
    - database/factories/UserModelFactory.php
    - phpstan.neon
    - .env.testing
    - tests/Feature/Auth/LoginTest.php
    - tests/Feature/Auth/LogoutTest.php
    - tests/Feature/Errors/JsonEnvelopeTest.php
    - tests/Feature/Routing/RouteVersioningTest.php
    - tests/Unit/PHPStan/DomainBoundaryTest.php
    - tests/Unit/Application/Order/PlaceOrderActionTest.php
  modified:
    - composer.json (phpunit upgraded ^11->^12, added analyse script, all dev deps)
    - config/auth.php (UserModel::class instead of User::class)
    - routes/api.php (v1 prefix group)
    - tests/Pest.php (RefreshDatabase for Feature, TestCase for Unit)
    - .env (APP_LOCALE=vi)

key-decisions:
  - "Upgraded phpunit constraint from ^11 to ^12 — Pest 4 requires phpunit ^12; Laravel 12 ships with ^11 which blocks installation"
  - "UserModel @use HasFactory<UserModelFactory> generic annotation — PHPStan level 6 requires explicit generics on HasFactory trait"
  - "Removed @extends Authenticatable generic — parent Authenticatable class is not generic in Laravel; correct annotation is only on the trait @use"
  - "RepositoryServiceProvider bind commented out — EloquentAdminUserRepository not created until Plan 02; binding an unresolvable class causes boot failure"
  - "ignoreErrors for #PHPDoc tag @var# removed from phpstan.neon — unused patterns cause PHPStan to error; removed to keep config clean"

patterns-established:
  - "Pattern 1: Domain entities are pure PHP with declare(strict_types=1) and no framework imports — enforced by NoLaravelImportInDomainRule"
  - "Pattern 2: Infrastructure Eloquent models use @use HasFactory<FactoryClass> PHPDoc for PHPStan generics compliance"
  - "Pattern 3: Repository interfaces live in Domain, concrete implementations in Infrastructure, binding registered in RepositoryServiceProvider"

requirements-completed: [TECH-01, TECH-02, TECH-06, TECH-07]

# Metrics
duration: 28min
completed: 2026-03-28
---

# Phase 01 Plan 01: Laravel Foundation Summary

**Laravel 12 + Pest 4 + PHPStan level 6 skeleton with 4-layer Clean Architecture, custom domain boundary enforcement rule, and 16 Wave 0 test stubs**

## Performance

- **Duration:** 28 min
- **Started:** 2026-03-28T06:57:08Z
- **Completed:** 2026-03-28T07:25:00Z
- **Tasks:** 2
- **Files modified:** 75+

## Accomplishments

- Laravel 12 project boots with Sanctum, spatie/laravel-data, laravel-permission installed
- 4-layer Clean Architecture directory structure created: Domain/Application/Infrastructure/Presentation
- PHPStan level 6 passes cleanly with custom `NoLaravelImportInDomainRule` active (enforces zero Illuminate/Laravel imports in Domain layer)
- UserModel moved to Infrastructure layer, wired in config/auth.php, default app/Models/User.php deleted
- AdminUser domain entity and AdminUserRepositoryInterface created as pure PHP (zero framework imports)
- 16 Wave 0 test stubs created covering AUTH-01, AUTH-02, AUTH-04, TECH-02, TECH-03, TECH-04, TECH-06
- DomainBoundaryTest passes green confirming domain purity
- All API routes prefixed /api/v1/ — `./vendor/bin/phpstan analyse` and `./vendor/bin/pest` both exit 0

## Task Commits

Each task was committed atomically:

1. **Task 1: Laravel 12 project with Clean Architecture skeleton** - `59abb9f` (feat)
2. **Task 2: PHPStan level 6 with domain boundary rule and Wave 0 test stubs** - `5334e6f` (feat)

## Files Created/Modified

- `app/Domain/Auth/Entities/AdminUser.php` - Pure PHP domain entity with no Illuminate imports
- `app/Domain/Auth/Repositories/AdminUserRepositoryInterface.php` - Domain repository interface
- `app/Infrastructure/Persistence/Eloquent/Models/UserModel.php` - Eloquent user model with HasApiTokens, moved from App\Models
- `app/Infrastructure/Providers/RepositoryServiceProvider.php` - Provider stub (bind commented for Plan 02)
- `app/Rules/PHPStan/NoLaravelImportInDomainRule.php` - Custom PHPStan rule enforcing domain boundary
- `database/factories/UserModelFactory.php` - Factory for UserModel (replaces UserFactory)
- `phpstan.neon` - PHPStan level 6 config with larastan extension and custom rule
- `composer.json` - Added all packages; upgraded phpunit ^11 -> ^12 for Pest 4 compatibility
- `config/auth.php` - Updated to use UserModel::class instead of User::class
- `routes/api.php` - All routes grouped under Route::prefix('v1')
- `.env.testing` - APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:
- `tests/Pest.php` - RefreshDatabase for Feature tests, TestCase for Unit tests
- `tests/Feature/Auth/LoginTest.php` - 5 todo stubs (AUTH-01, AUTH-02, TECH-04)
- `tests/Feature/Auth/LogoutTest.php` - 2 todo stubs (AUTH-04)
- `tests/Feature/Errors/JsonEnvelopeTest.php` - 4 todo stubs (TECH-03)
- `tests/Feature/Routing/RouteVersioningTest.php` - 2 todo stubs (TECH-02)
- `tests/Unit/PHPStan/DomainBoundaryTest.php` - Real test (passes green) verifying Domain purity
- `tests/Unit/Application/Order/PlaceOrderActionTest.php` - 3 todo stubs (TECH-06)

## Decisions Made

- Upgraded phpunit from `^11` to `^12` — Pest 4 requires phpunit 12; Laravel 12 ships with phpunit 11 as default which blocks Pest 4 installation.
- UserModel annotated with `@use HasFactory<UserModelFactory>` — PHPStan level 6 requires explicit generics annotation on the HasFactory trait to satisfy the `missingType.generics` check. The `@extends` annotation on the class was incorrect (parent is not generic).
- RepositoryServiceProvider bind line commented out — `EloquentAdminUserRepository` doesn't exist until Plan 02; binding a non-existent class causes a boot failure when the container tries to resolve it.
- Removed `ignoreErrors: [#PHPDoc tag @var#]` from phpstan.neon — PHPStan reports an error when an `ignoreErrors` pattern matches nothing; since no `@var` PHPDoc exists in the codebase yet, the pattern was unused.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] phpunit constraint upgraded from ^11 to ^12**
- **Found during:** Task 1 (installing Pest 4 dev dependencies)
- **Issue:** Laravel 12 ships with `phpunit/phpunit: ^11.5.50` in composer.json; Pest 4 requires phpunit `^12`, causing dependency resolution failure
- **Fix:** Updated phpunit constraint to `^12.0` in composer.json before installing Pest
- **Files modified:** composer.json
- **Verification:** Pest 4.4.3 installed successfully
- **Committed in:** 59abb9f (Task 1 commit)

**2. [Rule 1 - Bug] Fixed PHPStan generic type error on UserModel HasFactory**
- **Found during:** Task 2 (running phpstan analyse for first time)
- **Issue:** PHPStan level 6 reported `missingType.generics` — UserModel uses HasFactory but doesn't specify TFactory type
- **Fix:** Added `@use HasFactory<UserModelFactory>` PHPDoc annotation; removed incorrect `@extends Authenticatable<UserModelFactory>` (parent class is not generic)
- **Files modified:** app/Infrastructure/Persistence/Eloquent/Models/UserModel.php
- **Verification:** PHPStan exits 0 with no errors
- **Committed in:** 5334e6f (Task 2 commit)

**3. [Rule 1 - Bug] Removed unused ignoreErrors pattern from phpstan.neon**
- **Found during:** Task 2 (running phpstan analyse)
- **Issue:** Plan specified `ignoreErrors: ['#PHPDoc tag @var#']` but no `@var` PHPDoc exists in codebase; PHPStan reports an error for unmatched ignore patterns
- **Fix:** Removed the `ignoreErrors` section from phpstan.neon
- **Files modified:** phpstan.neon
- **Verification:** PHPStan exits 0 with no errors
- **Committed in:** 5334e6f (Task 2 commit)

---

**Total deviations:** 3 auto-fixed (1 blocking dependency conflict, 2 bugs from first PHPStan run)
**Impact on plan:** All auto-fixes necessary for correctness and functionality. No scope creep.

## Issues Encountered

- None beyond the auto-fixed deviations documented above.

## User Setup Required

None - no external service configuration required. SQLite in-memory is used for testing, local SQLite file for development.

## Next Phase Readiness

- Plan 01-02 can proceed immediately — skeleton boots, PHPStan passes, Pest runs
- Plan 01-02 needs to: implement AdminUser login endpoint, JSON error envelope handler, Vietnamese validation messages, Sanctum token expiry config
- RepositoryServiceProvider bind is commented out — Plan 02 must create EloquentAdminUserRepository and uncomment the binding
- Default ExampleTest files (tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php) are still present; Plan 02 may remove them

## Self-Check: PASSED

All created files verified on disk:
- app/Domain/Auth/Entities/AdminUser.php: FOUND
- app/Rules/PHPStan/NoLaravelImportInDomainRule.php: FOUND
- phpstan.neon: FOUND
- tests/Feature/Auth/LoginTest.php: FOUND
- .planning/phases/01-foundation/01-01-SUMMARY.md: FOUND

All task commits verified in git history:
- 59abb9f (Task 1): FOUND
- 5334e6f (Task 2): FOUND

---
*Phase: 01-foundation*
*Completed: 2026-03-28*
