---
phase: 02-product-inventory
plan: 03
subsystem: stock-adjustment
tags: [stock, inventory, adjustment, atomic-transaction, lockforupdate, audit-trail]
dependency_graph:
  requires: [02-01]
  provides: [stock-adjustment-endpoints, stock-adjustment-history]
  affects: [02-04]
tech_stack:
  added: []
  patterns:
    - DB::transaction wrapping lockForUpdate + updateStock + adjustment create (atomic inventory change)
    - bcadd/bccomp for DECIMAL precision arithmetic (avoids float rounding errors)
    - Domain entity passed directly to JsonResource (not Eloquent model)
    - Helper function `makeAdminTokenForHistory()` with unique name per test file to avoid collision
key_files:
  created:
    - app/Application/Product/Actions/AdjustStockAction.php
    - app/Application/Product/Actions/ListStockAdjustmentsAction.php
    - app/Presentation/Http/Controllers/Admin/StockAdjustmentController.php
    - app/Presentation/Http/Requests/AdjustStockRequest.php
    - app/Presentation/Http/Resources/StockAdjustmentResource.php
    - tests/Feature/Admin/StockAdjustmentTest.php
    - tests/Feature/Admin/StockAdjustmentHistoryTest.php
  modified:
    - app/Infrastructure/Persistence/Repositories/EloquentStockAdjustmentRepository.php
    - routes/api.php
decisions:
  - "EloquentStockAdjustmentRepository.create() passes created_at explicitly — model has timestamps=false so Eloquent does not populate created_at on the returned instance after create(); DB useCurrent() sets the DB value but the PHP model object stays null; passing now() ensures the returned domain entity has a valid createdAt"
  - "StockAdjustmentResource wraps domain entity directly — the controller receives StockAdjustment domain entities from AdjustStockAction; wrapping the domain entity avoids a second DB query to reload the Eloquent model"
metrics:
  duration: 15min
  completed_date: "2026-03-28"
  tasks_completed: 2
  files_created: 7
  files_modified: 2
---

# Phase 02 Plan 03: Stock Adjustment Endpoints Summary

**One-liner:** Delta-based stock adjustment with DB::transaction + lockForUpdate preventing negative stock, full audit trail (delta, type, stock_before, stock_after, admin_user_id), and paginated history endpoint ordered newest-first.

## What Was Built

### Task 1: AdjustStockAction + Adjustment Endpoint

**Application layer:**
- `AdjustStockAction`: wraps everything in `DB::transaction()`, calls `findByIdForUpdate()` (pessimistic lock), computes `stock_after = bcadd(stockBefore, delta, 3)`, throws `InsufficientStockException` if `bccomp(stockAfter, '0', 3) < 0`, then `updateStock()` + `adjustments->create()`
- DECIMAL precision: uses `bcadd`/`bccomp` with scale=3 — no floating-point rounding errors

**Presentation layer:**
- `AdjustStockRequest`: validates `delta` (required, numeric — allows negative), `adjustment_type` (Rule::in enum values), `note` (nullable, max 1000)
- `StockAdjustmentResource`: wraps domain entity directly, maps camelCase entity properties to snake_case JSON keys
- `StockAdjustmentController.store()`: extracts admin user ID from auth, parses `AdjustmentType::from()`, calls action, returns 201

**Route:**
- `POST /api/v1/admin/products/{product}/stock-adjustments`

**Tests (6 passing):**
- Positive delta increases stock correctly
- Negative delta decreases stock correctly
- Delta exceeding stock returns 422 `INSUFFICIENT_STOCK`
- `stock_before` and `stock_after` recorded accurately
- Missing `adjustment_type` returns 422 validation error
- Unauthenticated request returns 401

### Task 2: Stock Adjustment History Endpoint

**Application layer:**
- `ListStockAdjustmentsAction`: validates product exists via `findById()` (throws `ProductNotFoundException`), then delegates to `paginateByProduct()` — returns `{data, total}`

**Presentation layer:**
- `StockAdjustmentController.index()`: reads `per_page` (default 15) and `page` (default 1) query params, returns `{success, data, meta: {total, per_page, current_page}}`

**Route:**
- `GET /api/v1/admin/products/{product}/stock-adjustments`

**Tests (4 passing):**
- Lists adjustments for a product (3 items, all fields present)
- Pagination: 20 adjustments, `?per_page=5` returns 5 items + `meta.total=20`
- Newest first ordering verified across 3 sequential adjustments
- Unauthenticated request returns 401

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] EloquentStockAdjustmentRepository.create() — created_at null on returned model**
- **Found during:** Task 1 (tests failing with `toIso8601String() on null`)
- **Issue:** `StockAdjustmentModel` has `$timestamps = false`. When `create()` returns the model, `created_at` attribute is null (DB sets it via `useCurrent()` but Eloquent doesn't read it back). `StockAdjustmentMapper::toDomain()` then calls `->created_at->toIso8601String()` on null.
- **Fix:** Added `'created_at' => now()` to the create data array so the PHP model instance has the value populated.
- **Files modified:** `app/Infrastructure/Persistence/Repositories/EloquentStockAdjustmentRepository.php`
- **Commit:** 89654d0

## Known Stubs

None — all endpoints are fully wired with real data.

## Self-Check: PASSED

- `app/Application/Product/Actions/AdjustStockAction.php` — FOUND
- `app/Application/Product/Actions/ListStockAdjustmentsAction.php` — FOUND
- `app/Presentation/Http/Controllers/Admin/StockAdjustmentController.php` — FOUND
- `app/Presentation/Http/Requests/AdjustStockRequest.php` — FOUND
- `app/Presentation/Http/Resources/StockAdjustmentResource.php` — FOUND
- Commit 89654d0 — FOUND
- Commit eff1f79 — FOUND
- All 10 feature tests passing (6 StockAdjustmentTest + 4 StockAdjustmentHistoryTest)
- PHPStan: no errors
- Full test suite: 34 passed, 18 todos (future plans)
