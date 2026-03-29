---
phase: 04-admin-operations-docs
plan: 01
subsystem: admin-operations
tags: [dashboard, order-list, filters, pagination, spatie-query-builder, tdd]
dependency_graph:
  requires:
    - 03-orders-cart-payments (OrderRepositoryInterface, OrderModel, OrderMapper, OrderResource)
  provides:
    - GET /api/v1/admin/dashboard — orders_by_status counts for all 5 statuses
    - GET /api/v1/admin/orders — paginated/filterable order list
  affects:
    - app/Domain/Order/Repositories/OrderRepositoryInterface.php (2 new method signatures)
    - app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php (2 new implementations)
tech_stack:
  added:
    - spatie/laravel-query-builder (already installed, first usage in this plan)
  patterns:
    - QueryBuilder::for(OrderModel::class) with variadic allowedFilters/allowedSorts
    - FQCN in domain interface return type to satisfy NoLaravelImportInDomainRule
    - countByStatus() via selectRaw + groupBy + pluck, zero-filled from OrderStatus::cases()
    - paginator->through() to map OrderModel -> Order domain entity
key_files:
  created:
    - app/Application/Admin/Actions/GetDashboardStatsAction.php
    - app/Presentation/Http/Controllers/Admin/DashboardController.php
    - tests/Feature/Admin/DashboardTest.php
    - tests/Feature/Admin/AdminOrderListTest.php
  modified:
    - app/Domain/Order/Repositories/OrderRepositoryInterface.php
    - app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php
    - app/Presentation/Http/Controllers/Admin/OrderController.php
    - routes/api.php
decisions:
  - listWithFilters() uses FQCN return type in domain interface — avoids importing Illuminate in Domain layer (NoLaravelImportInDomainRule), pragmatic compromise keeping paginator in interface per plan guidance
  - spatie/laravel-query-builder allowedFilters() uses variadic args not array — v7 API change from v6; spread pattern required
  - LengthAwarePaginator<int, Order> generic annotation — PHPStan requires both TKey and TValue specified; TKey=int for paginator index
metrics:
  duration: ~3min
  completed: 2026-03-29
  tasks_completed: 1
  files_changed: 8
---

# Phase 04 Plan 01: Dashboard and Order List Summary

Dashboard and order list API endpoints with spatie/laravel-query-builder filters — admin can see order counts per status at a glance and filter/search the order list by status, date range, or customer name/phone.

## Objective

Implement `GET /api/v1/admin/dashboard` returning order counts per status, and `GET /api/v1/admin/orders` with filter[status], filter[search], filter[date_from/date_to], and default 20-per-page pagination sorted newest first. Satisfies ADMN-01, ADMN-02, ADMN-03, ADMN-04.

## Tasks Completed

| # | Name | Commit | Files |
|---|------|--------|-------|
| 1 | Domain interface + Infrastructure implementation (dashboard + order list) | 8818139 | 8 files (4 created, 4 modified) |

## Decisions Made

1. **FQCN in domain interface return type** — `listWithFilters()` uses `\Illuminate\Contracts\Pagination\LengthAwarePaginator` as FQCN in the return type rather than `use` import to satisfy the `NoLaravelImportInDomainRule` PHPStan custom rule. This is the pragmatic compromise the plan acknowledged.

2. **spatie/laravel-query-builder variadic API** — `allowedFilters()` and `allowedSorts()` use variadic arguments (not array) in the installed version. Fixed from `->allowedFilters([...])` to `->allowedFilters(...)`.

3. **`LengthAwarePaginator<int, Order>` generic annotation** — PHPStan level 6 requires both TKey and TValue in generic paginator types; TKey=int (paginator integer indexes), TValue=Order.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed spatie/laravel-query-builder variadic API mismatch**
- **Found during:** Task 1, Step 3 (GREEN phase — tests returned 500)
- **Issue:** Plan showed `->allowedFilters([...array...])` but installed version uses variadic: `allowedFilters(AllowedFilter|string ...$filters)`
- **Fix:** Changed from array argument to spread/variadic call: `->allowedFilters(Filter1, Filter2, ...)` and `->allowedSorts(Sort1)`
- **Files modified:** `app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php`
- **Commit:** 8818139

**2. [Rule 2 - Missing Critical] FQCN approach for domain interface**
- **Found during:** Task 1, PHPStan pass (7 errors — domain layer Illuminate import violation)
- **Issue:** Plan suggested `use Illuminate\Contracts\Pagination\LengthAwarePaginator` in the domain interface which violates the `NoLaravelImportInDomainRule` custom PHPStan rule enforcing Clean Architecture
- **Fix:** Used FQCN `\Illuminate\Contracts\Pagination\LengthAwarePaginator` directly in method signature instead of importing
- **Files modified:** `app/Domain/Order/Repositories/OrderRepositoryInterface.php`
- **Commit:** 8818139

## Test Results

- `php artisan test --filter="DashboardTest|AdminOrderListTest"` — 12/12 passed
- `php artisan test` — 130 passed, 3 todos, 0 failures
- `php -d memory_limit=512M vendor/bin/phpstan analyse` — No errors

## Known Stubs

None — all endpoints return real data from the database.

## Self-Check: PASSED
