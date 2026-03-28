---
phase: quick
plan: 260328-pl9
subsystem: auth
tags: [auth, sanctum, api-response, clean-architecture]
dependency_graph:
  requires: []
  provides: [login-response-with-expires-at, data-envelope-pattern]
  affects: [auth-flow, logout-test]
tech_stack:
  added: []
  patterns: [data-envelope-response, array-return-type-propagation]
key_files:
  created: []
  modified:
    - app/Domain/Auth/Repositories/AdminUserRepositoryInterface.php
    - app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php
    - app/Application/Auth/Actions/LoginAdminAction.php
    - app/Presentation/Http/Controllers/Auth/AuthController.php
    - tests/Feature/Auth/LoginTest.php
    - tests/Feature/Auth/LogoutTest.php
decisions:
  - createToken returns array{token, expires_at} propagated through all Clean Architecture layers
  - Login response wrapped under data key following consistent envelope pattern
metrics:
  duration: ~4min
  completed: 2026-03-28
  tasks_completed: 2
  files_modified: 6
---

# Quick 260328-pl9: Fix Login Response Structure Summary

**One-liner:** Login response now returns `{ success: true, data: { token, expires_at } }` by propagating array return type through Interface → Repository → Action → Controller layers.

## Tasks Completed

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Update createToken to return array with token and expires_at | c78920b | AdminUserRepositoryInterface.php, EloquentAdminUserRepository.php, LoginAdminAction.php |
| 2 | Update AuthController response and tests | b19f210 | AuthController.php, LoginTest.php |

## What Was Built

The `createToken` method previously returned a plain `string` (the token value). It now returns `array{token: string, expires_at: string}`. The change propagates through all Clean Architecture layers:

1. **Domain Interface** (`AdminUserRepositoryInterface`) — return type changed from `string` to `array` with PHPDoc shape annotation.
2. **Infrastructure Repository** (`EloquentAdminUserRepository`) — computes `$expiresAt` before calling Sanctum, returns both the `plainTextToken` and the ISO 8601 expiry string.
3. **Application Action** (`LoginAdminAction`) — return type updated from `string` to `array`, body unchanged (passes through).
4. **Presentation Controller** (`AuthController`) — renamed `$token` to `$tokenData`, returns `{ success: true, data: { token, expires_at } }`.

Login tests updated to assert `data.token` and `data.expires_at` structure.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed LogoutTest referencing old token path**
- **Found during:** Overall verification (running full auth suite)
- **Issue:** `LogoutTest.php` line 30 had `$loginResponse->json('token')` — after the login response moved to `data.token`, this returned `null`, causing the logout request to send a null Bearer token and receive 401 instead of 200.
- **Fix:** Changed `json('token')` to `json('data.token')` in LogoutTest.
- **Files modified:** `tests/Feature/Auth/LogoutTest.php`
- **Commit:** c485fd0

## Verification Results

- All 7 auth tests pass (5 login + 2 logout)
- PHPStan level 6 passes on all modified files with no errors

## Known Stubs

None.

## Self-Check: PASSED

- c78920b exists in git log
- b19f210 exists in git log
- c485fd0 exists in git log
- All 6 modified files are committed
