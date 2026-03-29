---
phase: 03-orders-cart-payments
plan: 05
subsystem: notifications
tags: [notification, email, queue, cart-cleanup, vietnamese]
dependency_graph:
  requires: ["03-01", "03-03"]
  provides: ["NOTI-01", "NOTI-02", "TECH-05"]
  affects: ["PlaceOrderAction", "AdminPlaceOrderAction"]
tech_stack:
  added: []
  patterns: ["Laravel Notifications", "ShouldQueue", "afterCommit", "Notification::fake()"]
key_files:
  created:
    - app/Infrastructure/Notifications/NewOrderNotification.php
    - resources/views/emails/orders/new-order.blade.php
    - app/Console/Commands/PruneExpiredCartsCommand.php
    - tests/Feature/Notification/NotificationTest.php
  modified:
    - app/Application/Order/Actions/PlaceOrderAction.php
    - app/Application/Order/Actions/AdminPlaceOrderAction.php
    - routes/console.php
    - .env.example
decisions:
  - "afterCommit set in constructor not as property — Queueable trait already declares $afterCommit; redeclaring causes PHP fatal"
  - "Notification dispatch OUTSIDE DB::transaction closure — makes intent clear, works with afterCommit=true"
  - "UserModel::first() to find admin recipient — v1 single-admin pattern, no config key needed"
metrics:
  duration: 15min
  completed_date: "2026-03-29"
  tasks: 2
  files: 8
---

# Phase 3 Plan 5: New Order Notification and Cart Cleanup Summary

**One-liner:** Queued Vietnamese email notification dispatched after transaction commit, with PruneExpiredCartsCommand scheduled daily.

## What Was Built

### NewOrderNotification
- `app/Infrastructure/Notifications/NewOrderNotification.php` — implements `ShouldQueue`, uses `Queueable` trait with `$afterCommit = true` set in constructor. Sends markdown email to admin using `emails.orders.new-order` template.

### Vietnamese Email Template
- `resources/views/emails/orders/new-order.blade.php` — markdown mail with Blade components. Shows: don hang moi ID, khach hang, so dien thoai, dia chi giao hang, phuong thuc thanh toan, product table (STT/Ten san pham/So luong/Don gia/Thanh tien), and Tong cong total.

### Dispatch from Actions
- `PlaceOrderAction` — notification dispatch added OUTSIDE the `DB::transaction` closure. `$order = DB::transaction(...)` then `$admin->notify(new NewOrderNotification($order))`.
- `AdminPlaceOrderAction` — same pattern applied for admin manual orders.

### PruneExpiredCartsCommand
- `app/Console/Commands/PruneExpiredCartsCommand.php` — artisan command `cart:prune-expired`. Injects `CartRepositoryInterface`, calls `deleteExpired()`, outputs count. Description: "Xoa cac gio hang da het han".
- `routes/console.php` — `Schedule::command('cart:prune-expired')->daily()` added.

### Notification Tests
- `tests/Feature/Notification/NotificationTest.php` — 5 Pest tests:
  1. Queues email after customer checkout (NOTI-01)
  2. Queues email after admin manual order (NOTI-01)
  3. Email contains product names, quantities, address, "Tong cong" (NOTI-02)
  4. `$afterCommit` is true (unit check)
  5. Email subject contains order ID

## Verification

- `php artisan cart:prune-expired` — exits 0, outputs "Da xoa 0 gio hang het han."
- `php artisan list | grep cart` — shows `cart:prune-expired` command
- `php artisan test --filter=NotificationTest` — 5/5 passed
- `php artisan test` — 108 tests passed (3 todos), 0 failures
- `php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress` — no errors

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed $afterCommit property conflict with Queueable trait**
- **Found during:** Task 2 (test run)
- **Issue:** Plan specified `public bool $afterCommit = true;` as class property, but `Queueable` trait already declares `$afterCommit`. PHP 8.3 raises fatal: "definition differs and is considered incompatible. Class was composed."
- **Fix:** Set `$this->afterCommit = true;` inside the constructor instead of redeclaring the property.
- **Files modified:** `app/Infrastructure/Notifications/NewOrderNotification.php`
- **Commit:** b2f3a23

**2. [Rule 1 - Bug] Fixed toContain() assertion on string in notification email test**
- **Found during:** Task 2 (test run)
- **Issue:** Pest's `expect($rendered)->toContain()` requires an iterable, but `$mail->render()` returns a string. Error: "Invalid expectation value type. Expected [iterable]."
- **Fix:** Used `\PHPUnit\Framework\assertStringContainsString()` for string content assertions.
- **Files modified:** `tests/Feature/Notification/NotificationTest.php`
- **Commit:** b2f3a23

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| Task 1 | d788feb | feat(03-05): add NewOrderNotification, email template, cart prune command |
| Task 2 | b2f3a23 | feat(03-05): add notification tests and fix afterCommit assignment |

## Known Stubs

None. All functionality is wired: notification dispatches to `UserModel::first()` (real DB query), email template renders with real `Order` domain entity data.

## Self-Check: PASSED
