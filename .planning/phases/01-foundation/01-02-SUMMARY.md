---
phase: 01-foundation
plan: 02
subsystem: auth
tags: [laravel, sanctum, auth, vietnamese, localization, json-envelope, pest, clean-architecture]

# Dependency graph
requires:
  - phase: 01-01
    provides: Laravel 12 skeleton, Clean Architecture structure, UserModel with HasApiTokens, AdminUserRepositoryInterface, route v1 prefix group, Pest 4 test suite with Wave 0 stubs
provides:
  - POST /api/v1/admin/login endpoint returning Sanctum token (AUTH-01)
  - Token non-null expires_at via SANCTUM_TOKEN_EXPIRY env (AUTH-02)
  - sanctum:prune-expired scheduled daily (AUTH-02)
  - POST /api/v1/admin/logout endpoint revoking current token (AUTH-04)
  - Public routes accessible without authentication (AUTH-03)
  - Global JSON error envelope { success, code, message, errors } on all api/* routes (TECH-03)
  - Vietnamese validation and auth messages in lang/vi/ (TECH-04)
  - EloquentAdminUserRepository implementing AdminUserRepositoryInterface
  - UserMapper mapping UserModel to AdminUser domain entity
  - LoginAdminAction and LogoutAdminAction in Application layer
  - AuthController in Presentation layer
  - AdminSeeder seeding admin@banchimbocau.vn
  - All 16 Wave 0 test stubs converted to real passing tests (16 passed, 3 todos for Phase 3)
affects: [all subsequent plans, 02-products, 03-orders]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Application actions (LoginAdminAction, LogoutAdminAction) receive HTTP Request only at controller boundary; domain logic uses only domain types
    - UserMapper static toDomain() converts Eloquent model to pure domain entity — no Eloquent in Domain layer
    - Global exception renderer in bootstrap/app.php with match(true) on exception type — add new exception types here
    - redirectGuestsTo(null) in withMiddleware prevents RouteNotFoundException on api/* auth failures
    - Auth::forgetGuards() required between sequential requests in same test when testing token revocation

key-files:
  created:
    - app/Exceptions/Auth/InvalidCredentialsException.php
    - app/Application/Auth/Actions/LoginAdminAction.php
    - app/Application/Auth/Actions/LogoutAdminAction.php
    - app/Infrastructure/Persistence/Mappers/UserMapper.php
    - app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php
    - app/Presentation/Http/Controllers/Auth/AuthController.php
    - app/Presentation/Http/Requests/LoginRequest.php
    - database/seeders/AdminSeeder.php
    - lang/vi/validation.php
    - lang/vi/auth.php
    - lang/en/ (published baseline via lang:publish)
  modified:
    - bootstrap/app.php (JSON envelope + redirectGuestsTo null)
    - bootstrap/providers.php (RepositoryServiceProvider registered)
    - config/sanctum.php (expiration = SANCTUM_TOKEN_EXPIRY env, default 43200)
    - routes/api.php (login/logout routes wired)
    - routes/console.php (sanctum:prune-expired daily scheduler)
    - app/Infrastructure/Providers/RepositoryServiceProvider.php (bind activated)
    - database/seeders/DatabaseSeeder.php (calls AdminSeeder)
    - phpstan.neon (phpVersion: 80300 added)
    - .env.testing (APP_LOCALE=vi added)
    - tests/Feature/Auth/LoginTest.php (stubs replaced)
    - tests/Feature/Auth/LogoutTest.php (stubs replaced)
    - tests/Feature/Errors/JsonEnvelopeTest.php (stubs replaced)
    - tests/Feature/Routing/RouteVersioningTest.php (stubs replaced)

key-decisions:
  - "redirectGuestsTo(null) in withMiddleware — pure API app has no web login route; without this, Authenticate middleware throws RouteNotFoundException instead of AuthenticationException on non-JSON requests"
  - "app('auth')->forgetGuards() needed between test requests for token revocation test — Laravel auth guard caches user in-process; without reset, deleted token still authenticates in subsequent requests within same test"
  - "APP_LOCALE=vi added to .env.testing — without it, tests run with default 'en' locale and Vietnamese validation message assertions fail"
  - "php -d memory_limit=512M for phpstan analyse — codebase now exceeds 128M default; added phpVersion: 80300 to phpstan.neon as well"

patterns-established:
  - "Pattern 4: JSON error envelope registered in bootstrap/app.php withExceptions — shouldRenderJsonWhen + render callback with match(true) on exception type"
  - "Pattern 5: Presentation FormRequests live in app/Presentation/Http/Requests/ — they validate HTTP input, Application actions receive only validated primitives"
  - "Pattern 6: UserMapper as static class converts Eloquent model to domain entity — keeps Infrastructure mapping logic separate from both layers"

requirements-completed: [AUTH-01, AUTH-02, AUTH-03, AUTH-04, TECH-03, TECH-04]

# Metrics
duration: 32min
completed: 2026-03-28
---

# Phase 01 Plan 02: Admin Auth + JSON Envelope Summary

**Sanctum login/logout with token expiry, global Vietnamese JSON error envelope, and all 16 Wave 0 test stubs converted to passing Feature tests**

## Performance

- **Duration:** 32 min
- **Started:** 2026-03-28T07:06:28Z
- **Completed:** 2026-03-28T07:38:00Z
- **Tasks:** 3 (1a cross-cutting infra, 1b auth feature stack, 2 test conversion)
- **Files modified:** 24+

## Accomplishments

- Admin authentication via POST /api/v1/admin/login returns Sanctum token with non-null expires_at (30 days default)
- POST /api/v1/admin/logout revokes the current token; subsequent use returns 401 UNAUTHENTICATED
- Global JSON error envelope active on all api/* routes: `{ success, code, message, errors }` with codes VALIDATION_ERROR, UNAUTHENTICATED, INVALID_CREDENTIALS, NOT_FOUND, SERVER_ERROR
- Vietnamese validation and auth messages in lang/vi/ — error messages display in tiếng Việt
- All 16 Wave 0 test stubs converted to real assertions; full suite: 16 passed, 3 todos (PlaceOrderAction for Phase 3)
- PHPStan level 6 passes cleanly

## Task Commits

Each task was committed atomically:

1. **Task 1a: Configure cross-cutting infrastructure** - `f02cb06` (feat)
2. **Task 1b: Implement admin auth feature stack** - `90d6fd0` (feat)
3. **Task 2: Convert Wave 0 test stubs to real passing tests** - `a741026` (feat)
4. **chore: published lang/en baseline** - `f7b89d7` (chore)

## Files Created/Modified

- `bootstrap/app.php` - JSON error envelope (shouldRenderJsonWhen + render callback) + redirectGuestsTo(null)
- `bootstrap/providers.php` - RepositoryServiceProvider registered
- `config/sanctum.php` - expiration = env SANCTUM_TOKEN_EXPIRY (default 43200 min = 30 days)
- `routes/api.php` - POST /api/v1/admin/login and POST /api/v1/admin/logout wired to AuthController
- `routes/console.php` - sanctum:prune-expired --hours=24 daily scheduler
- `lang/vi/validation.php` - Vietnamese validation error messages
- `lang/vi/auth.php` - Vietnamese auth messages
- `app/Exceptions/Auth/InvalidCredentialsException.php` - Domain exception with Vietnamese message
- `app/Application/Auth/Actions/LoginAdminAction.php` - Login action: findByEmail + Hash::check + createToken
- `app/Application/Auth/Actions/LogoutAdminAction.php` - Logout action: currentAccessToken()->delete()
- `app/Infrastructure/Persistence/Mappers/UserMapper.php` - Static toDomain(UserModel): AdminUser
- `app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php` - Implements AdminUserRepositoryInterface
- `app/Infrastructure/Providers/RepositoryServiceProvider.php` - Bind activated (EloquentAdminUserRepository now exists)
- `app/Presentation/Http/Requests/LoginRequest.php` - email/password validation rules
- `app/Presentation/Http/Controllers/Auth/AuthController.php` - Injects both actions, delegates completely
- `database/seeders/AdminSeeder.php` - Seeds admin@banchimbocau.vn with bcrypt password
- `database/seeders/DatabaseSeeder.php` - Calls AdminSeeder
- `phpstan.neon` - phpVersion: 80300 added
- `.env.testing` - APP_LOCALE=vi added
- `tests/Feature/Auth/LoginTest.php` - 5 real tests (AUTH-01, AUTH-02, TECH-04)
- `tests/Feature/Auth/LogoutTest.php` - 2 real tests (AUTH-04)
- `tests/Feature/Errors/JsonEnvelopeTest.php` - 4 real tests (TECH-03)
- `tests/Feature/Routing/RouteVersioningTest.php` - 2 real tests (TECH-02)

## Decisions Made

- `redirectGuestsTo(null)` in `bootstrap/app.php` `withMiddleware` — this pure API project has no web login route. Without this, when a non-JSON request hits a Sanctum-protected route, the Authenticate middleware calls `route('login')` inside the `AuthenticationException` constructor, throwing `RouteNotFoundException` (500) instead of letting our handler return 401. Setting null as the redirect URL for API routes is the correct fix.
- `app('auth')->forgetGuards()` between test HTTP calls for token revocation test — the Laravel auth guard instance is cached in the same PHP process for the duration of the test. After a successful Sanctum auth, the guard returns the cached user even if the token is deleted from DB. Calling `forgetGuards()` resets the cache between requests, making the test simulate real HTTP isolation.
- `APP_LOCALE=vi` added to `.env.testing` — the test environment loads its own `.env.testing` which didn't inherit `APP_LOCALE=vi` from `.env`. Without it, Pest tests use the `en` locale and the Vietnamese assertion (`toContain('bắt buộc')`) fails.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] redirectGuestsTo(null) to prevent RouteNotFoundException on non-JSON api/* requests**
- **Found during:** Task 2 (running test suite — JsonEnvelopeTest "never returns HTML" test)
- **Issue:** When a non-JSON request hits `auth:sanctum` middleware, `Authenticate::unauthenticated()` calls `route('login')` to build the redirect URL inside the `AuthenticationException`. Since this is a pure API app with no `login` named route, `RouteNotFoundException` is thrown before the exception reaches our handler, returning 500 instead of 401.
- **Fix:** Added `$middleware->redirectGuestsTo(fn (Request $request) => null)` in `withMiddleware` callback of `bootstrap/app.php`. This sets `Authenticate::$redirectToCallback` to return null, which the middleware uses as the redirect URL — meaning no redirect attempt, and `AuthenticationException` is thrown cleanly.
- **Files modified:** bootstrap/app.php
- **Verification:** JsonEnvelopeTest "never returns HTML" test passes; unauthenticated test returns 401 JSON
- **Committed in:** a741026 (Task 2 commit)

**2. [Rule 1 - Bug] APP_LOCALE=vi missing from .env.testing causing English validation messages in tests**
- **Found during:** Task 2 (running test suite — LoginTest Vietnamese message assertion)
- **Issue:** `.env.testing` overrides `.env` for the test environment. It was missing `APP_LOCALE=vi`, so tests ran with the default `en` locale and the validation message was "The email field is required." instead of "Trường email là bắt buộc."
- **Fix:** Added `APP_LOCALE=vi` to `.env.testing`
- **Files modified:** .env.testing
- **Verification:** LoginTest Vietnamese message assertion passes (toContain 'bắt buộc')
- **Committed in:** a741026 (Task 2 commit)

**3. [Rule 1 - Bug] Auth guard caching causes deleted token to still authenticate in test suite**
- **Found during:** Task 2 (LogoutTest "subsequent request with deleted token" test)
- **Issue:** Laravel's auth guard caches the resolved user in `$guards['sanctum']->user` for the duration of the PHP process. After the first successful `auth:sanctum` check, subsequent HTTP test requests within the same test method reuse the cached user even after `currentAccessToken()->delete()`. This caused the second logout call to return 200 instead of 401.
- **Fix:** Added `$this->app['auth']->forgetGuards()` between the first and second logout calls in the test. This clears the guard cache, forcing Sanctum to re-validate the bearer token against the `personal_access_tokens` table on the next request.
- **Files modified:** tests/Feature/Auth/LogoutTest.php
- **Verification:** LogoutTest "subsequent request with deleted token" passes with 401 UNAUTHENTICATED
- **Committed in:** a741026 (Task 2 commit)

**4. [Rule 3 - Blocking] phpstan analyse OOM — added memory_limit=512M and phpVersion to phpstan.neon**
- **Found during:** Task 1b (first PHPStan run after adding new files)
- **Issue:** Default PHP memory limit (128M) is insufficient for PHPStan to analyse the expanded codebase. PHPStan exits with "Allowed memory size of 134217728 bytes exhausted".
- **Fix:** Run PHPStan with `php -d memory_limit=512M ./vendor/bin/phpstan analyse`. Also added `phpVersion: 80300` to `phpstan.neon` for explicit PHP version targeting.
- **Files modified:** phpstan.neon
- **Verification:** PHPStan exits 0 with no errors
- **Committed in:** 90d6fd0 (Task 1b commit)

---

**Total deviations:** 4 auto-fixed (2 bugs, 2 blocking)
**Impact on plan:** All auto-fixes necessary for correctness and test suite reliability. No scope creep.

## Issues Encountered

- None beyond the auto-fixed deviations documented above.

## User Setup Required

None - no external service configuration required. Default admin@banchimbocau.vn seeded with `php artisan db:seed`. Run PHPStan with `php -d memory_limit=512M ./vendor/bin/phpstan analyse --no-progress`.

## Known Stubs

None — all Wave 0 test stubs converted to real passing tests. `PlaceOrderActionTest` intentionally remains as 3 todos (deferred to Phase 3).

## Next Phase Readiness

- Admin authentication complete and tested end-to-end
- JSON error envelope active on all api/* routes — all future feature tests inherit consistent error format
- Vietnamese localization active — validation errors display in tiếng Việt
- RepositoryServiceProvider pattern established — future repositories follow same bind pattern
- Phase 01 complete: skeleton + auth + error handling + test coverage
- Phase 02 can proceed: product catalog (CRUD sản phẩm, danh mục, tồn kho)

## Self-Check: PASSED

All created files verified on disk:
- app/Exceptions/Auth/InvalidCredentialsException.php: FOUND
- app/Application/Auth/Actions/LoginAdminAction.php: FOUND
- app/Application/Auth/Actions/LogoutAdminAction.php: FOUND
- app/Infrastructure/Persistence/Mappers/UserMapper.php: FOUND
- app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php: FOUND
- app/Presentation/Http/Controllers/Auth/AuthController.php: FOUND
- app/Presentation/Http/Requests/LoginRequest.php: FOUND
- database/seeders/AdminSeeder.php: FOUND
- lang/vi/validation.php: FOUND
- lang/vi/auth.php: FOUND

All task commits verified in git history:
- f02cb06 (Task 1a): FOUND
- 90d6fd0 (Task 1b): FOUND
- a741026 (Task 2): FOUND
- f7b89d7 (lang/en chore): FOUND

---
*Phase: 01-foundation*
*Completed: 2026-03-28*
