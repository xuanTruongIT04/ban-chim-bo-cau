---
phase: 03-orders-cart-payments
plan: 01
subsystem: order-domain
tags: [domain, infrastructure, migrations, enums, state-machine, repositories]
dependency_graph:
  requires: [02-product-inventory]
  provides: [order-domain-layer, cart-domain-layer, order-migrations, order-repositories]
  affects: [03-02-cart-api, 03-03-order-api, 03-04-checkout, 03-05-notifications]
tech_stack:
  added: []
  patterns:
    - Pure PHP domain entities (no Eloquent extends)
    - PHP backed enums with guard methods for state machine
    - Static mapper pattern (CartMapper, OrderMapper)
    - @property annotations on Eloquent models for PHPStan enum type inference
key_files:
  created:
    - app/Domain/Order/Enums/OrderStatus.php
    - app/Domain/Order/Enums/PaymentStatus.php
    - app/Domain/Order/Enums/PaymentMethod.php
    - app/Domain/Order/Enums/DeliveryMethod.php
    - app/Domain/Order/Entities/Cart.php
    - app/Domain/Order/Entities/CartItem.php
    - app/Domain/Order/Entities/Order.php
    - app/Domain/Order/Entities/OrderItem.php
    - app/Domain/Order/Exceptions/CartNotFoundException.php
    - app/Domain/Order/Exceptions/CartExpiredException.php
    - app/Domain/Order/Exceptions/InvalidOrderTransitionException.php
    - app/Domain/Order/Exceptions/OrderNotFoundException.php
    - app/Domain/Order/Exceptions/InactiveProductInCartException.php
    - app/Domain/Order/Repositories/CartRepositoryInterface.php
    - app/Domain/Order/Repositories/OrderRepositoryInterface.php
    - app/Infrastructure/Persistence/Eloquent/Models/CartModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/CartItemModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/OrderModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/OrderItemModel.php
    - app/Infrastructure/Persistence/Mappers/CartMapper.php
    - app/Infrastructure/Persistence/Mappers/OrderMapper.php
    - app/Infrastructure/Persistence/Repositories/EloquentCartRepository.php
    - app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php
    - database/migrations/2026_03_29_000005_create_carts_table.php
    - database/migrations/2026_03_29_000006_create_cart_items_table.php
    - database/migrations/2026_03_29_000007_create_orders_table.php
    - database/migrations/2026_03_29_000008_create_order_items_table.php
    - database/factories/CartModelFactory.php
    - database/factories/OrderModelFactory.php
    - database/factories/OrderItemModelFactory.php
    - config/bank.php
    - tests/Unit/OrderStatusTest.php
  modified:
    - app/Infrastructure/Providers/RepositoryServiceProvider.php
    - bootstrap/app.php
    - .env.example
decisions:
  - "@property Carbon $expires_at on CartModel — PHPStan cannot infer Carbon type from datetime cast; annotation required for CartMapper.toDomain() to call toIso8601String()"
  - "Migration filenames use sequential 000005-000008 suffix to avoid conflicts with existing Phase 2 migrations (000001-000004)"
metrics:
  duration: 350min
  completed: "2026-03-29"
  tasks_completed: 2
  files_created: 33
  files_modified: 3
---

# Phase 3 Plan 01: Order/Cart Domain Foundation Summary

**One-liner:** OrderStatus state machine enum (5 states, back-step D-11), Cart/Order domain entities (pure PHP), 4 migrations, Eloquent models with enum casts, static mappers, repository interfaces + implementations — full foundation for Plans 02-05.

## Tasks Completed

| # | Task | Commit | Status |
|---|------|--------|--------|
| 1 | Domain layer — enums, entities, exceptions, repo interfaces | 5ee5bad | Done |
| 2 | Migrations, models, mappers, repositories, factories, config, exception handler | 49ebf82 | Done |

## What Was Built

### Domain Layer (Pure PHP, Zero Laravel Imports)

**4 Enums:**
- `OrderStatus` — 5 states (`cho_xac_nhan`, `xac_nhan`, `dang_giao`, `hoan_thanh`, `huy`) with `allowedNextStates()`, `canTransitionTo()`, `isCancellable()`, `label()` methods implementing D-08/D-10/D-11 rules
- `PaymentStatus` — `chua_thanh_toan`, `da_thanh_toan`
- `PaymentMethod` — `cod`, `chuyen_khoan`
- `DeliveryMethod` — `noi_tinh`, `ngoai_tinh`

**4 Domain Entities** (readonly constructors, no Eloquent):
- `Cart` with `items: array<CartItem>`
- `CartItem` with DECIMAL quantity as string
- `Order` with enum-typed fields and `items: array<OrderItem>`
- `OrderItem` with price/subtotal as int, quantity as DECIMAL string

**5 Domain Exceptions** (extend `\DomainException`):
- `CartNotFoundException`, `CartExpiredException`, `InvalidOrderTransitionException`, `OrderNotFoundException`, `InactiveProductInCartException`

**2 Repository Interfaces** (in Domain layer, no Eloquent):
- `CartRepositoryInterface` — 9 methods including `create()`, `findByToken()`, `addItem()`, `refreshExpiry()`, `deleteExpired()`
- `OrderRepositoryInterface` — 5 methods including `create()`, `updateStatus()`, `updatePaymentStatus()`, `updateDeliveryMethod()`

### Infrastructure Layer

**4 Migrations:**
- `carts` — UUID token (UNIQUE), expires_at with index
- `cart_items` — UNIQUE(cart_id, product_id), FK to carts CASCADE, FK to products RESTRICT
- `orders` — 9 columns, index on order_status and customer_phone, FK to users SET NULL
- `order_items` — 7 columns, FK to orders CASCADE, FK to products RESTRICT

**4 Eloquent Models** with proper PHPStan annotations:
- `CartModel` — `@property Carbon $expires_at`, `items()` HasMany
- `CartItemModel` — casts quantity as decimal:3
- `OrderModel` — `@property OrderStatus|PaymentMethod|PaymentStatus|?DeliveryMethod`, enum casts, `items()` + `creator()` relations
- `OrderItemModel` — price/subtotal cast as integer

**2 Static Mappers:** `CartMapper::toDomain()`, `OrderMapper::toDomain()` — with item collection mapping

**2 Repository Implementations:** `EloquentCartRepository`, `EloquentOrderRepository`

**Config:** `config/bank.php` with account_name, account_number, bank_name from env

**Updated Files:**
- `RepositoryServiceProvider` — 2 new bindings
- `bootstrap/app.php` — 5 new exception handler arms (HTTP 404/410/422)
- `.env.example` — 3 BANK_* variables

**3 Factories:** `CartModelFactory`, `OrderModelFactory`, `OrderItemModelFactory`

## Verification Results

| Check | Result |
|-------|--------|
| `php artisan migrate:fresh --seed --force` | PASS — 12 migrations run |
| `php artisan test --filter=OrderStatusTest` | PASS — 14 passed (43 assertions) |
| `php -d memory_limit=512M vendor/bin/phpstan analyse` | PASS — No errors |
| No `use Illuminate` in Domain/Order/ | PASS — Zero imports |
| `config/bank.php` returns 3 keys | PASS |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 2 - Missing annotation] Add `@property Carbon $expires_at` to CartModel**
- **Found during:** Task 2 PHPStan run
- **Issue:** PHPStan could not infer that `expires_at` cast to `datetime` is a Carbon object; CartMapper.toDomain() calling `->toIso8601String()` on it caused `method.nonObject` error
- **Fix:** Added `@property Carbon $expires_at` annotation to CartModel (same pattern as `@property OrderStatus $order_status` on OrderModel)
- **Files modified:** `app/Infrastructure/Persistence/Eloquent/Models/CartModel.php`
- **Commit:** 49ebf82

**2. [Rule 3 - Blocking] Migration filenames use 000005-000008 suffix**
- **Found during:** Task 2 setup
- **Issue:** Plan specified filenames `2026_03_29_000001` through `000004` but those are already used by Phase 2 product/category migrations
- **Fix:** Used sequential numbers 000005-000008 to avoid collisions
- **Files modified:** Migration files
- **Commit:** 49ebf82

## Known Stubs

None — all interfaces are fully implemented with concrete Eloquent repositories. No placeholder data or TODO markers.

## Self-Check: PASSED
