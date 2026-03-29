---
phase: 03-orders-cart-payments
plan: 03
subsystem: order-placement
tags: [checkout, orders, idempotency, atomic-lock, payment, feature-tests]
dependency_graph:
  requires: [03-01-order-domain, 03-02-cart-api]
  provides: [checkout-endpoint, admin-order-endpoint, order-resources, idempotency]
  affects: [03-04-order-management, 03-05-notifications]
tech_stack:
  added:
    - infinitypaul/idempotency-laravel 1.0.5 — EnsureIdempotency middleware for POST checkout + admin orders
  patterns:
    - DB::transaction + sorted lockForUpdate (deadlock prevention) + bcadd/bccomp — same as Phase 2 AdjustStockAction
    - X-Cart-Token header resolves cart for public checkout
    - bank_info in response body for chuyen_khoan payment method
key_files:
  created:
    - app/Application/Order/Actions/PlaceOrderAction.php
    - app/Application/Order/Actions/AdminPlaceOrderAction.php
    - app/Presentation/Http/Controllers/Public/CheckoutController.php
    - app/Presentation/Http/Controllers/Admin/OrderController.php
    - app/Presentation/Http/Requests/CheckoutRequest.php
    - app/Presentation/Http/Requests/AdminPlaceOrderRequest.php
    - app/Presentation/Http/Resources/OrderResource.php
    - app/Presentation/Http/Resources/OrderItemResource.php
    - tests/Feature/Public/CheckoutTest.php
    - tests/Feature/Admin/AdminOrderTest.php
    - config/idempotency.php
  modified:
    - routes/api.php — added /checkout + /admin/orders routes with EnsureIdempotency
    - composer.json / composer.lock — added infinitypaul/idempotency-laravel
decisions:
  - PlaceOrderAction sorts product IDs ascending before locking to prevent deadlocks — same as AdjustStockAction pattern from Phase 2
  - bank_info returned inline in response body (not in data envelope) for chuyen_khoan — matches D-19
  - AdminPlaceOrderAction receives items[] array directly (no cart) — admin orders bypass cart flow but NOT inventory lock
metrics:
  duration: ~10min
  completed: 2026-03-29T00:38:11Z
  tasks_completed: 2
  files_created: 11
  files_modified: 2
  tests_added: 15
  tests_total: 95
---

# Phase 3 Plan 3: Atomic Order Placement & Admin Manual Orders Summary

**One-liner:** Atomic checkout with DB::transaction + sorted lockForUpdate + bcadd/bccomp, idempotency via infinitypaul middleware, bank info for bank transfer, admin manual order creation.

## What Was Built

### Task 1: Application Actions, Controllers, Requests, Resources, Routes

**PlaceOrderAction** (`app/Application/Order/Actions/PlaceOrderAction.php`):
- Wraps entire flow in `DB::transaction`
- Sorts product IDs ascending before locking to prevent deadlock
- `findByIdForUpdate` on each product row (pessimistic lock)
- Validates active status, checks stock with `bccomp`, decrements with `bcadd`
- Creates order with `OrderStatus::ChoXacNhan` and `PaymentStatus::ChuaThanhToan`
- Deletes cart on success

**AdminPlaceOrderAction** (`app/Application/Order/Actions/AdminPlaceOrderAction.php`):
- Identical atomic lock pattern — no code path bypasses inventory lock (D-23)
- Accepts items[] directly (no cart dependency)
- Sets `created_by = $adminUserId`

**CheckoutController** (`app/Presentation/Http/Controllers/Public/CheckoutController.php`):
- Reads `X-Cart-Token` header → resolves cart via `CartRepositoryInterface::findByToken`
- Returns `bank_info` from `config('bank')` when payment_method = `chuyen_khoan` (D-19)
- Route protected by `EnsureIdempotency` middleware

**OrderController** (`app/Presentation/Http/Controllers/Admin/OrderController.php`):
- `store()`: calls `AdminPlaceOrderAction`, returns 201
- `show()`: looks up order by ID, throws `OrderNotFoundException` if not found

**Requests**: `CheckoutRequest` with VN 10-digit phone regex, `AdminPlaceOrderRequest` adds items[] validation.

**Resources**: `OrderResource` with full Vietnamese enum labels, `OrderItemResource` for line items.

**Routes**: `POST /api/v1/checkout` and `POST /api/v1/admin/orders` both behind `EnsureIdempotency`. `GET /api/v1/admin/orders/{order}` for detail.

### Task 2: Feature Tests

**CheckoutTest** (10 tests):
- Atomic stock decrement verified via `assertDatabaseHas`
- Idempotency: second request with same UUID key returns cached response, `assertDatabaseCount('orders', 1)`
- Insufficient stock → 422 `INSUFFICIENT_STOCK`
- Inactive product in cart → 422 `INACTIVE_PRODUCT_IN_CART`
- VN phone validation → 422 `VALIDATION_ERROR`
- All required fields validation
- COD payment_status = `chua_thanh_toan`
- `chuyen_khoan` returns `bank_info.account_name`
- Cart deleted after checkout
- Price snapshot: order_items retains original name/price even after product update

**AdminOrderTest** (5 tests):
- Admin creates manual order, `created_by` = admin ID, stock decremented
- Admin views order detail, all fields present
- Insufficient stock → 422
- Missing order → 404 `ORDER_NOT_FOUND`
- Unauthenticated → 401

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None — all data is wired from real DB operations.

## Verification Results

- `php artisan test --filter=CheckoutTest` — 10 passed
- `php artisan test --filter=AdminOrderTest` — 5 passed
- `php artisan test` — 95 passed (443 assertions)
- `php -d memory_limit=512M vendor/bin/phpstan analyse` — No errors

## Self-Check: PASSED
