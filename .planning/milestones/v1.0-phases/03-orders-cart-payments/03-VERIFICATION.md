---
phase: 03-orders-cart-payments
verified: 2026-03-29T00:00:00Z
status: passed
score: 18/18 must-haves verified
re_verification: false
---

# Phase 3: Orders, Cart & Payments — Verification Report

**Phase Goal:** Customers can build a cart and place orders without overselling; admin can enter manual orders; orders follow a strict state machine; payment status is tracked independently; admin is notified by email on every new order.
**Verified:** 2026-03-29
**Status:** passed
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Customer can create a cart (UUID token) and add/update/remove items | VERIFIED | CartController + 5 routes + CartTest (12 tests passing) |
| 2 | Adding to cart does NOT decrement product stock | VERIFIED | AddToCartAction has no stock call; CartTest asserts `stock_quantity` unchanged |
| 3 | POST /api/v1/checkout atomically locks stock with lockForUpdate and bcadd/bccomp | VERIFIED | PlaceOrderAction uses `DB::transaction` + `findByIdForUpdate` + sorted product IDs + `bcadd`/`bccomp` |
| 4 | Same Idempotency-Key header returns the same order without duplication | VERIFIED | `infinitypaul/idempotency-laravel` 1.0.5 installed; middleware on checkout + admin orders routes; CheckoutTest asserts `assertDatabaseCount('orders', 1)` |
| 5 | Admin can create manual orders with identical lock mechanism | VERIFIED | AdminPlaceOrderAction mirrors same `DB::transaction + lockForUpdate` pattern; AdminOrderTest passing |
| 6 | Orders follow 5-state machine with guard validation | VERIFIED | OrderStatus enum with `allowedNextStates()`, `canTransitionTo()`, `isCancellable()`; OrderStatusTest (14 tests) all passing |
| 7 | Invalid state transitions are rejected with Vietnamese error | VERIFIED | UpdateOrderStatusAction throws `InvalidOrderTransitionException`; AdminOrderTest covers invalid transitions |
| 8 | Admin can step back exactly 1 state | VERIFIED | `allowedNextStates()` includes back-step for XacNhan and DangGiao; AdminOrderTest verifies D-11 |
| 9 | Cancelling an order restores stock in the same DB::transaction | VERIFIED | CancelOrderAction wraps stock restore + status update in single transaction; CancelOrderTest asserts stock restored |
| 10 | Cancellation rejected for hoan_thanh orders | VERIFIED | `isCancellable()` returns false for HoanThanh; CancelOrderTest confirms rejection |
| 11 | Payment status tracked independently from order status | VERIFIED | Separate `payment_status` column; PaymentTest confirms independence |
| 12 | Admin can confirm payment — payment_status changes to da_thanh_toan | VERIFIED | ConfirmPaymentAction; PATCH /admin/orders/{id}/payment-status route; PaymentTest passing |
| 13 | Admin can set delivery method (noi_tinh or ngoai_tinh) | VERIFIED | PATCH /admin/orders/{id}/delivery-method route; AdminOrderTest validates |
| 14 | Admin receives Vietnamese email on every new order | VERIFIED | NewOrderNotification dispatched outside transaction with `$afterCommit = true`; NotificationTest (5 tests) all passing |
| 15 | Email is in Vietnamese with product names, quantities, and delivery address | VERIFIED | `new-order.blade.php` contains `Khach hang`, `Dia chi giao hang`, `Tong cong`, product table loop |
| 16 | Domain layer has zero Laravel imports | VERIFIED | `grep -rn "use Illuminate" app/Domain/Order/` returns no output |
| 17 | PHPStan level 6 passes | VERIFIED | `phpstan analyse` exits 0 with "No errors" |
| 18 | Cart cleanup command prunes expired carts on schedule | VERIFIED | `cart:prune-expired` command runs; scheduled daily in routes/console.php |

**Score:** 18/18 truths verified

---

### Required Artifacts

All artifacts from PLAN frontmatter verified at all 3 levels (exists, substantive, wired):

#### Plan 01 Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `app/Domain/Order/Enums/OrderStatus.php` | VERIFIED | `enum OrderStatus: string` with 5 cases; `allowedNextStates()`, `canTransitionTo()`, `isCancellable()`, `label()` |
| `app/Domain/Order/Entities/Order.php` | VERIFIED | `final class Order` with `OrderStatus`, `PaymentStatus` typed properties; zero Laravel imports |
| `database/migrations/2026_03_29_000005_create_carts_table.php` | VERIFIED | Ran (batch 3); carts table present |
| `database/migrations/2026_03_29_000007_create_orders_table.php` | VERIFIED | Ran (batch 3); orders table present |
| `tests/Unit/OrderStatusTest.php` | VERIFIED | 14 tests, 43 assertions; all passing |

#### Plan 02 Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `app/Presentation/Http/Controllers/Public/CartController.php` | VERIFIED | `class CartController`; store, show, addItem, updateItem, removeItem methods |
| `app/Presentation/Http/Middleware/ResolveCartToken.php` | VERIFIED | Reads `X-Cart-Token`; injects CartRepositoryInterface; calls `findByToken` |
| `tests/Feature/Public/CartTest.php` | VERIFIED | 12 tests; CART_TOKEN_REQUIRED, CART_NOT_FOUND, INACTIVE_PRODUCT_IN_CART, stock assertions |

#### Plan 03 Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `app/Application/Order/Actions/PlaceOrderAction.php` | VERIFIED | `DB::transaction` + `findByIdForUpdate` + `bcadd` + `bccomp` + sorted product IDs + notification dispatch outside transaction |
| `app/Presentation/Http/Controllers/Public/CheckoutController.php` | VERIFIED | `class CheckoutController`; bank_info returned for chuyen_khoan |
| `tests/Feature/Public/CheckoutTest.php` | VERIFIED | 10 tests; Idempotency-Key, stock decrement, insufficient stock, bank_info, PAYM states |

#### Plan 04 Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `app/Application/Order/Actions/CancelOrderAction.php` | VERIFIED | `DB::transaction` + `findByIdForUpdate` + `bcadd` + `isCancellable` + `sortBy('productId')` |
| `app/Application/Order/Actions/UpdateOrderStatusAction.php` | VERIFIED | `canTransitionTo` + `InvalidOrderTransitionException` |
| `tests/Feature/Admin/CancelOrderTest.php` | VERIFIED | 5 tests; stock restoration, all cancellable states, hoan_thanh rejection |

#### Plan 05 Artifacts

| Artifact | Status | Details |
|----------|--------|---------|
| `app/Infrastructure/Notifications/NewOrderNotification.php` | VERIFIED | `implements ShouldQueue`; `$this->afterCommit = true` in constructor; `markdown('emails.orders.new-order')` |
| `resources/views/emails/orders/new-order.blade.php` | VERIFIED | `Don hang moi`, `Khach hang`, `Dia chi giao hang`, `Tong cong`; product table loop |
| `app/Console/Commands/PruneExpiredCartsCommand.php` | VERIFIED | `cart:prune-expired`; `deleteExpired()`; Vietnamese output |
| `tests/Feature/Notification/NotificationTest.php` | VERIFIED | 5 tests; Notification::fake(), assertSentTo, afterCommit, Tong cong |

---

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `EloquentOrderRepository.php` | `OrderRepositoryInterface.php` | implements | WIRED | `implements OrderRepositoryInterface` confirmed |
| `RepositoryServiceProvider.php` | `OrderRepositoryInterface.php` | bind | WIRED | `OrderRepositoryInterface::class` binding at line 51 |
| `RepositoryServiceProvider.php` | `CartRepositoryInterface.php` | bind | WIRED | `CartRepositoryInterface::class` binding at line 46 |
| `CartController.php` | `AddToCartAction.php` | constructor injection | WIRED | `AddToCartAction` injected |
| `ResolveCartToken.php` | `CartRepositoryInterface.php` | findByToken | WIRED | `findByToken` call confirmed |
| `routes/api.php` | `CartController.php` | Route::prefix('cart') | WIRED | `CartController` in routes; 5 cart routes listed |
| `PlaceOrderAction.php` | `ProductRepositoryInterface.php` | findByIdForUpdate | WIRED | `findByIdForUpdate` call confirmed |
| `PlaceOrderAction.php` | `OrderRepositoryInterface.php` | create | WIRED | `$this->orders->create(...)` confirmed |
| `routes/api.php` | `CheckoutController.php` | EnsureIdempotency | WIRED | `EnsureIdempotency::class` at line 44 and 65 |
| `CancelOrderAction.php` | `ProductRepositoryInterface.php` | findByIdForUpdate + updateStock | WIRED | Both calls confirmed |
| `UpdateOrderStatusAction.php` | `OrderStatus.php` | canTransitionTo | WIRED | `canTransitionTo()` call at line 36 |
| `PlaceOrderAction.php` | `NewOrderNotification.php` | Notification::send after transaction | WIRED | `$admin->notify(new NewOrderNotification($order))` outside DB::transaction closure |
| `NewOrderNotification.php` | `resources/views/emails/orders/new-order.blade.php` | markdown mail | WIRED | `markdown('emails.orders.new-order')` confirmed |

---

### Data-Flow Trace (Level 4)

Applies to controllers/actions that process live DB data.

| Artifact | Data Variable | Source | Produces Real Data | Status |
|----------|---------------|--------|--------------------|--------|
| `CartController::show` | `$cart->items` | `CartRepositoryInterface::findByToken` + Eloquent eager load `items.product` | DB query via `EloquentCartRepository` | FLOWING |
| `PlaceOrderAction::handle` | `$cart->items` | domain Cart entity from middleware | Passed from resolved cart, not hardcoded | FLOWING |
| `OrderResource` | `$order->items` | `EloquentOrderRepository::findById` with eager loaded items | Real DB query | FLOWING |
| `CheckoutController::store` | bank_info | `config('bank')` | config/bank.php driven by env | FLOWING |

---

### Behavioral Spot-Checks

| Behavior | Command | Result | Status |
|----------|---------|--------|--------|
| OrderStatus unit tests | `php artisan test --filter=OrderStatusTest` | 14 passed, 43 assertions | PASS |
| Cart feature tests | `php artisan test --filter=CartTest` | 12 passed, 53 assertions | PASS |
| Checkout + idempotency tests | `php artisan test --filter=CheckoutTest` | 10 passed, 30 assertions | PASS |
| Admin order + state machine tests | `php artisan test --filter=AdminOrderTest` | 13 passed, 41 assertions | PASS |
| Cancellation + stock restore tests | `php artisan test --filter=CancelOrderTest` | 5 passed, 13 assertions | PASS |
| Payment independence tests | `php artisan test --filter=PaymentTest` | 5 passed, 17 assertions | PASS |
| Notification dispatch tests | `php artisan test --filter=NotificationTest` | 5 passed, 15 assertions | PASS |
| Full test suite | `php artisan test` | 118 passed, 3 todos, 512 assertions | PASS |
| PHPStan level 6 | `phpstan analyse --no-progress` | No errors | PASS |
| Cart prune command | `php artisan cart:prune-expired` | Exits 0; Vietnamese output | PASS |
| Cart routes registered | `php artisan route:list --path=cart` | 5 routes shown | PASS |
| Checkout route with idempotency | `php artisan route:list --path=checkout` | POST /api/v1/checkout with EnsureIdempotency | PASS |
| Admin order routes | `php artisan route:list --path=admin/orders` | 6 routes (store, show, cancel, delivery-method, payment-status, status) | PASS |

---

### Requirements Coverage

All 20 requirement IDs claimed in PLAN frontmatter verified:

| Requirement | Source Plan(s) | Description | Status | Evidence |
|-------------|---------------|-------------|--------|----------|
| CART-01 | 03-01, 03-02 | Anonymous cart with UUID token | SATISFIED | CartController + CartTest |
| CART-02 | 03-02 | Cart shows quantity, price, total | SATISFIED | CartResource with current product prices; CartTest |
| CART-03 | 03-02 | Update quantity or remove items | SATISFIED | PATCH/DELETE routes; CartTest |
| CART-04 | 03-01, 03-02 | Cart does NOT decrement stock | SATISFIED | AddToCartAction has no stock call; CartTest stock assertion |
| ORDR-01 | 03-03 | Atomic checkout with lockForUpdate | SATISFIED | PlaceOrderAction with DB::transaction + lockForUpdate; CheckoutTest |
| ORDR-02 | 03-03 | Idempotency key on checkout | SATISFIED | EnsureIdempotency middleware; CheckoutTest duplicate key test |
| ORDR-03 | 03-03 | Admin manual order with same lock | SATISFIED | AdminPlaceOrderAction; AdminOrderTest |
| ORDR-04 | 03-01, 03-04 | 5 order states | SATISFIED | OrderStatus enum with 5 cases; OrderStatusTest |
| ORDR-05 | 03-04 | Cancellation restores stock atomically | SATISFIED | CancelOrderAction transaction; CancelOrderTest |
| ORDR-06 | 03-03 | Admin views order detail | SATISFIED | GET /api/v1/admin/orders/{id}; AdminOrderTest |
| ORDR-07 | 03-04 | Admin updates status; invalid rejected | SATISFIED | UpdateOrderStatusAction; AdminOrderTest |
| PAYM-01 | 03-01, 03-03 | payment_status independent of order_status | SATISFIED | Separate column; PaymentTest independence test. Note: REQUIREMENTS.md specifies 3 payment states (including "chờ xác nhận") but design decision D-18 explicitly simplified to 2 states for v1. This is intentional scope reduction documented in 03-CONTEXT.md |
| PAYM-02 | 03-03 | COD starts chua_thanh_toan | SATISFIED | PlaceOrderAction sets PaymentStatus::ChuaThanhToan; CheckoutTest |
| PAYM-03 | 03-03 | chuyen_khoan returns bank info | SATISFIED | CheckoutController returns config('bank') for chuyen_khoan; CheckoutTest |
| PAYM-04 | 03-04 | Admin confirms payment | SATISFIED | ConfirmPaymentAction; PATCH route; PaymentTest |
| DELV-01 | 03-03 | Customer enters delivery address at checkout | SATISFIED | CheckoutRequest requires delivery_address; VN phone validation |
| DELV-02 | 03-01, 03-04 | noi_tinh or ngoai_tinh delivery method | SATISFIED | DeliveryMethod enum; UpdateDeliveryMethodRequest; PATCH route; AdminOrderTest |
| NOTI-01 | 03-05 | Queued email after new order (post-commit) | SATISFIED | NewOrderNotification with afterCommit=true; dispatch outside transaction; NotificationTest |
| NOTI-02 | 03-05 | Vietnamese email with product details and address | SATISFIED | new-order.blade.php; NotificationTest content assertions |
| TECH-05 | 03-03, 03-04, 03-05 | API documentation via Scribe | SATISFIED | @group, @bodyParam, @response annotations on CheckoutController and OrderController |

**Orphaned requirements check:** All 20 requirements listed in REQUIREMENTS.md traceability table for Phase 3 are covered by the 5 plans. No orphaned requirements.

---

### Anti-Patterns Found

| File | Pattern | Severity | Impact |
|------|---------|----------|--------|
| None found | — | — | — |

No TODOs, FIXMEs, empty returns, placeholder comments, or stub implementations found in Order domain, application, or presentation layer files.

**Note on PAYM-01 / D-18:** REQUIREMENTS.md specifies 3 payment states (`chưa thanh toán / chờ xác nhận / đã thanh toán`) but the implementation delivers 2 states (`chua_thanh_toan`, `da_thanh_toan`). This is documented intent: design decision D-18 in `03-CONTEXT.md` explicitly dropped the intermediate `chờ xác nhận` state for v1 simplicity. Not classified as a bug or gap — it is a deliberate scope reduction with written rationale.

---

### Human Verification Required

None. All behaviors are verifiable programmatically via the test suite (118 tests, 512 assertions, all green).

---

### Gaps Summary

No gaps. All 18 observable truths verified. All 20 requirement IDs satisfied. Test suite fully green (118 passing, 0 failing). PHPStan level 6 passes. All key links wired. Data flows confirmed end-to-end.

---

_Verified: 2026-03-29_
_Verifier: Claude (gsd-verifier)_
