---
phase: 03-orders-cart-payments
plan: 04
subsystem: order-management
tags: [state-machine, cancellation, stock-restoration, payment, delivery-method, feature-tests]
dependency_graph:
  requires: [03-03-order-placement]
  provides: [order-status-transitions, order-cancellation, payment-confirmation, delivery-method-assignment]
  affects: [03-05-notifications]
tech_stack:
  added: []
  patterns:
    - Domain enum guard methods (canTransitionTo, isCancellable) validate all state transitions
    - DB::transaction + sorted lockForUpdate (ascending productId) for atomic stock restoration on cancel
    - Idempotent ConfirmPaymentAction — returns existing state if already da_thanh_toan
    - Separate cancel endpoint (POST /cancel) vs status update (PATCH /status) — cancel needs stock restoration
key_files:
  created:
    - app/Application/Order/Actions/UpdateOrderStatusAction.php
    - app/Application/Order/Actions/CancelOrderAction.php
    - app/Application/Order/Actions/ConfirmPaymentAction.php
    - app/Presentation/Http/Requests/UpdateOrderStatusRequest.php
    - app/Presentation/Http/Requests/UpdateDeliveryMethodRequest.php
    - tests/Feature/Admin/CancelOrderTest.php
    - tests/Feature/Admin/PaymentTest.php
  modified:
    - app/Presentation/Http/Controllers/Admin/OrderController.php
    - tests/Feature/Admin/AdminOrderTest.php
    - routes/api.php
decisions:
  - CancelOrderAction rejects Huy->Huy (already cancelled): isCancellable() only guards HoanThanh, explicit Huy check added
  - UpdateOrderStatusRequest excludes huy from valid values: cancellation goes through dedicated endpoint with stock restoration
  - ConfirmPaymentAction is idempotent: second confirm call returns 200 unchanged (no error)
metrics:
  duration: 25min
  completed_date: "2026-03-29"
  tasks_completed: 2
  files_modified: 10
---

# Phase 3 Plan 4: Admin Order Management Summary

Admin order lifecycle management via state machine transitions, atomic stock restoration on cancellation, payment confirmation, and delivery method assignment.

## One-liner

State machine transitions validated by Domain enum guards, with atomic DB::transaction stock restoration on cancel and independent payment_status tracking.

## Tasks Completed

| # | Name | Commit | Files |
|---|------|--------|-------|
| 1 | UpdateOrderStatusAction, CancelOrderAction, ConfirmPaymentAction, controller methods, routes | d4e2496 | 7 files created/modified |
| 2 | State machine, cancellation, and payment feature tests | 2c453d4 | 4 files created/modified |

## What Was Built

### Action Classes

**UpdateOrderStatusAction** — validates state transitions using `OrderStatus::canTransitionTo()`. Handles both forward transitions and 1-step back (D-11). Does NOT handle cancellation (separate action required for stock restoration).

**CancelOrderAction** — cancels order and restores stock atomically in `DB::transaction`. Sorts order items by `productId` ascending before locking (same deadlock-prevention pattern as PlaceOrderAction). Rejects both `hoan_thanh` (via `isCancellable()`) and already-`huy` orders.

**ConfirmPaymentAction** — confirms payment idempotently. If `paymentStatus` is already `da_thanh_toan`, returns order unchanged. Rejects confirmation on cancelled orders.

### Controller Methods Added

- `updateStatus` — PATCH `/admin/orders/{order}/status`
- `cancel` — POST `/admin/orders/{order}/cancel`
- `confirmPayment` — PATCH `/admin/orders/{order}/payment-status`
- `updateDeliveryMethod` — PATCH `/admin/orders/{order}/delivery-method`

### Form Requests

- `UpdateOrderStatusRequest` — `in:cho_xac_nhan,xac_nhan,dang_giao,hoan_thanh` (huy excluded — use cancel endpoint)
- `UpdateDeliveryMethodRequest` — `in:noi_tinh,ngoai_tinh`

### Feature Tests (23 new tests across 3 files)

**AdminOrderTest** (+8 state machine tests): forward transition, full lifecycle chain, invalid transitions (rejected with INVALID_ORDER_TRANSITION), back-step, delivery method assignment.

**CancelOrderTest** (5 tests): stock restoration verified via `assertDatabaseHas` with exact decimal values, multi-state cancellation (cho_xac_nhan, xac_nhan, dang_giao), hoan_thanh rejection, already-cancelled rejection.

**PaymentTest** (5 tests): payment_status independence from order_status, admin confirmation, idempotency, cancelled order rejection.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] CancelOrderAction also rejects already-cancelled (huy) orders**
- **Found during:** Task 2 (test "rejects cancellation of already-cancelled order" failed with 200)
- **Issue:** `isCancellable()` only checks `$this !== HoanThanh`. Huy state passes that check, so re-cancelling a cancelled order returned success.
- **Fix:** Added explicit `$order->orderStatus === OrderStatus::Huy` check before `isCancellable()` guard.
- **Files modified:** app/Application/Order/Actions/CancelOrderAction.php
- **Commit:** 2c453d4

**2. [Rule 3 - Blocking] OrderItemModel has no HasFactory trait**
- **Found during:** Task 2 (test using `OrderItemModel::factory()` threw BadMethodCallException)
- **Fix:** Used `OrderItemModelFactory::new()->create()` directly in test instead of `OrderItemModel::factory()->create()`
- **Files modified:** tests/Feature/Admin/CancelOrderTest.php
- **Commit:** 2c453d4

## Known Stubs

None — all data flows are wired. Tests verify real database state via `assertDatabaseHas`.

## Self-Check: PASSED

Files exist:
- app/Application/Order/Actions/UpdateOrderStatusAction.php ✓
- app/Application/Order/Actions/CancelOrderAction.php ✓
- app/Application/Order/Actions/ConfirmPaymentAction.php ✓
- app/Presentation/Http/Requests/UpdateOrderStatusRequest.php ✓
- app/Presentation/Http/Requests/UpdateDeliveryMethodRequest.php ✓
- tests/Feature/Admin/CancelOrderTest.php ✓
- tests/Feature/Admin/PaymentTest.php ✓

Commits exist:
- d4e2496 ✓
- 2c453d4 ✓

All 118 tests pass. PHPStan level 6 exits 0.
