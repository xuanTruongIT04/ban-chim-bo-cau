# Phase 3: Orders, Cart & Payments - Research

**Researched:** 2026-03-29
**Domain:** Laravel Cart / Order State Machine / Atomic Inventory / Email Notifications
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Cart (D-01 through D-07)**
- D-01: Cart uses UUID token — `POST /cart` returns `cart_token`. Client sends `X-Cart-Token` header. No sessions, no Sanctum guest token.
- D-02: Cart expires after 7 days of inactivity. Scheduled job or lazy cleanup.
- D-03: No per-product quantity limit in cart. Stock checked only at checkout.
- D-04: Adding a product already in cart accumulates quantity (3 + 2 = 5).
- D-05: Cart does NOT lock prices — always reads current product price. Price changes are immediately effective.
- D-06: Inactive products (`is_active=false`) in cart show as "hết hàng"; rejected at checkout.
- D-07: Cart does NOT decrement stock — only a confirmed order does.

**Order State Machine (D-08 through D-12)**
- D-08: 5 states: `cho_xac_nhan` → `xac_nhan` → `dang_giao` → `hoan_thanh` | `huy`. Enum values in snake_case, no diacritics.
- D-09: Only admin can cancel. Customers contact mom via Zalo/phone.
- D-10: Cancellation allowed at any state except `hoan_thanh`. Stock restored in same transaction.
- D-11: Admin may step back 1 state (e.g., `dang_giao` → `xac_nhan`). No more than 1 step back.
- D-12: Invalid transitions rejected with Vietnamese error.

**Checkout (D-13 through D-16)**
- D-13: Required checkout fields: họ tên, số điện thoại (10 digits, starts with 0), địa chỉ giao hàng (free text).
- D-14: No order notes in v1.
- D-15: `delivery_method` nullable; admin sets it after receiving order (not customer).
- D-16: No shipping fee in v1. Total = sum of product prices. Mom negotiates shipping separately.

**Payment (D-17 through D-20)**
- D-17: Customer selects `cod` or `chuyen_khoan` at checkout.
- D-18: Both methods start `payment_status = chua_thanh_toan`. Admin confirms → `da_thanh_toan`.
- D-19: On `chuyen_khoan` selection, API response includes bank account info (name, STK, bank name).
- D-20: Bank info stored in config/env, not DB. Single account.

**Idempotency & Atomic Order (D-21 through D-23)**
- D-21: Order API requires `Idempotency-Key` UUID header. Same key = same response, no new order.
- D-22: PlaceOrderAction runs full flow (check stock → decrement → create order) in single `DB::transaction` with `lockForUpdate` per product row. Use `bcadd`/`bccomp` for DECIMAL precision.
- D-23: Admin manual order uses same lock mechanism — no code path bypasses inventory lock.

**Email Notification (D-24 through D-25)**
- D-24: New order triggers Vietnamese email to admin listing products, quantities, delivery address.
- D-25: Email sent via queued job, dispatched after transaction commit. Database queue driver.

### Claude's Discretion

- Schema for `orders`, `order_items`, `carts`, `cart_items` — Claude decides detail
- State machine implementation (enum + guard method, or package) — Claude's choice
- Idempotency implementation (middleware or inline) — can use `infinitypaul/idempotency-laravel`
- Email template layout — Claude's choice, must be Vietnamese with all required info
- Cart cleanup mechanism (scheduled command or lazy delete) — Claude's choice

### Deferred Ideas (OUT OF SCOPE)

- Shipping fee calculation by delivery type (nội tỉnh / ngoại tỉnh) — v2
- Customer order notes — add when FE requests
- Stock reservation (hold stock during cart lifetime) — INVT-V2-02
- Dynamic QR code for bank transfer — PAYM-V2-02
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| CART-01 | Anonymous cart with session token, no registration | UUID token pattern in D-01; `X-Cart-Token` header middleware |
| CART-02 | Cart displays quantity, price, total | Price always from product (D-05); total = SUM(quantity × current_price) |
| CART-03 | Update quantity or remove items from cart | Standard PATCH/DELETE cart item endpoints |
| CART-04 | Cart does NOT decrement stock | Stock only decremented inside PlaceOrderAction transaction |
| ORDR-01 | Atomic stock check + decrement in single DB transaction with lockForUpdate | Direct reuse of AdjustStockAction pattern; lock ALL product rows before checking any |
| ORDR-02 | Idempotency key — no duplicate orders | `infinitypaul/idempotency-laravel` v1.0.5, Laravel 12 compatible |
| ORDR-03 | Admin manual order entry, same lock mechanism | Separate AdminPlaceOrderAction shares same atomic core |
| ORDR-04 | 5-state order state machine | PHP backed enum `OrderStatus`; guard method on Domain entity |
| ORDR-05 | Cancellation restores stock in same transaction | CancelOrderAction wraps status change + stock restore in DB::transaction |
| ORDR-06 | Admin views order detail | GET /admin/orders/{id} with full eager-loaded resource |
| ORDR-07 | Admin updates order status, invalid transitions rejected | UpdateOrderStatusAction validates allowed transitions from domain enum |
| PAYM-01 | Separate `payment_status` field | Enum `PaymentStatus` on orders table: `chua_thanh_toan`, `cho_xac_nhan` (unused v1), `da_thanh_toan` |
| PAYM-02 | COD payment flow | COD starts `chua_thanh_toan`; admin confirms → `da_thanh_toan` |
| PAYM-03 | Bank transfer support | `chuyen_khoan` method; bank info from config returned in checkout response |
| PAYM-04 | Admin confirms payment received | PATCH /admin/orders/{id}/payment-status endpoint |
| DELV-01 | Delivery address at checkout (name, phone, address) | Required fields on CheckoutRequest; phone regex `^0\d{9}$` |
| DELV-02 | Delivery method: nội tỉnh or ngoại tỉnh | Nullable `delivery_method` enum on orders; admin sets via PATCH |
| NOTI-01 | Admin email on new order, via queued job after transaction commit | Laravel Notification + ShouldQueue + `$this->afterCommit = true` |
| NOTI-02 | Vietnamese email with products, quantities, address | Markdown/Blade mail template in Vietnamese |
| TECH-05 | Auto API docs via Scribe | Scribe ^5.9 already installed; add docblocks to new controllers |
</phase_requirements>

---

## Summary

Phase 3 builds on the established Clean Architecture from Phases 1-2. All patterns (DB::transaction + lockForUpdate, bcadd/bccomp, Action pattern, domain entities, repository interfaces, JSON envelope, Pest feature tests) are already proven in the codebase. The primary challenge is coordinating multiple pieces — cart token management, multi-product atomic locks, state machine transitions, idempotency, and post-commit notifications — without introducing complexity that violates the project's "family-business scale" constraint.

The `AdjustStockAction` pattern directly maps to `PlaceOrderAction`: the key extension is locking multiple product rows in one transaction (always in `ORDER BY id` sequence to prevent deadlocks), checking and decrementing each, then inserting order + order_items as a unit. Idempotency is best handled by the `infinitypaul/idempotency-laravel` package (v1.0.5, Laravel 12 compatible) which prevents duplicate transactions entirely at middleware level.

State machine lives in the Domain layer as a PHP backed enum with a guard method returning allowed next states — no external package needed for the simple 5-state machine defined. Email notification uses Laravel's built-in `ShouldQueue` on a Notification with `$this->afterCommit = true` to guarantee dispatch only after the order transaction commits successfully.

**Primary recommendation:** Reuse AdjustStockAction's DB::transaction + lockForUpdate + bcadd/bccomp pattern for PlaceOrderAction; lock multiple rows sorted by `id` to prevent deadlocks; use `infinitypaul/idempotency-laravel` for duplicate-order prevention; implement state machine as a Domain enum with guard methods; fire notification after commit via `ShouldQueue` + `afterCommit`.

---

## Standard Stack

### Core (already installed — confirm from composer.json)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | 12.56.0 (running) | Web framework | Already installed |
| Laravel Sanctum | ^4.0 | Admin auth | Already installed |
| spatie/laravel-data | ^4.20 | DTOs / validation | Already installed |
| spatie/laravel-query-builder | ^7.0 | Filterable endpoints | Already installed |
| spatie/laravel-permission | ^6.0 | Role-based access | Already installed |
| knuckleswtf/scribe | ^5.9 | API docs (TECH-05) | Already installed |

### New Package Required

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|-------------|
| infinitypaul/idempotency-laravel | ^1.0.5 | Duplicate order prevention (ORDR-02) | Laravel 12 compatible; lock-based concurrency; payload validation; matches D-21 |

### Supporting (built-in Laravel — no install)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel Queues (database driver) | bundled | Async email dispatch | Already configured in `.env` (QUEUE_CONNECTION=database) |
| Laravel Notifications | bundled | Email to admin | New order event (NOTI-01) |
| Laravel Scheduling | bundled | Cart expiry cleanup | `php artisan schedule:run` via cron or `schedule:work` in dev |
| PHP `bcadd`/`bccomp` | PHP 8.3 | DECIMAL arithmetic | Already used in AdjustStockAction for stock; same for order totals |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `infinitypaul/idempotency-laravel` | Inline cache check in action | Inline check: simpler but misses distributed locks, telemetry, payload validation; package adds 50 lines of config vs. ~200 lines hand-rolled |
| PHP backed enum + guard methods | `asantibanez/laravel-eloquent-state-machines` package | Package adds learning overhead; 5-state machine is too simple to justify external dep |
| `ShouldQueue` + `afterCommit` notification | `DB::afterCommit()` callback | Callback is less testable; Notification is the standard Laravel pattern for emails |

**Installation (only new package needed):**
```bash
cd /Users/toney/projects/ban-chim-bo-cau
composer require infinitypaul/idempotency-laravel:^1.0.5
php artisan vendor:publish --provider="Infinitypaul\Idempotency\IdempotencyServiceProvider"
```

**Verified versions (2026-03-29):**
- `infinitypaul/idempotency-laravel` 1.0.5 — released 2025-06-16, Laravel 12 support confirmed via illuminate/support ^12.0

---

## Architecture Patterns

### Recommended Project Structure (new files for Phase 3)

```
app/
├── Domain/
│   └── Order/
│       ├── Entities/
│       │   ├── Cart.php
│       │   ├── CartItem.php
│       │   ├── Order.php
│       │   └── OrderItem.php
│       ├── Enums/
│       │   ├── OrderStatus.php          # cho_xac_nhan|xac_nhan|dang_giao|hoan_thanh|huy
│       │   ├── PaymentStatus.php        # chua_thanh_toan|da_thanh_toan
│       │   ├── PaymentMethod.php        # cod|chuyen_khoan
│       │   └── DeliveryMethod.php       # noi_tinh|ngoai_tinh
│       ├── Exceptions/
│       │   ├── CartNotFoundException.php
│       │   ├── CartExpiredException.php
│       │   ├── InvalidOrderTransitionException.php
│       │   ├── OrderNotFoundException.php
│       │   └── InactiveProductInCartException.php
│       └── Repositories/
│           ├── CartRepositoryInterface.php
│           └── OrderRepositoryInterface.php
│
├── Application/
│   └── Order/
│       └── Actions/
│           ├── CreateCartAction.php
│           ├── AddToCartAction.php
│           ├── UpdateCartItemAction.php
│           ├── RemoveCartItemAction.php
│           ├── PlaceOrderAction.php         # atomic; reuses lockForUpdate pattern
│           ├── AdminPlaceOrderAction.php    # same lock; no idempotency header
│           ├── UpdateOrderStatusAction.php  # validates state transitions
│           ├── CancelOrderAction.php        # restores stock in same transaction
│           └── ConfirmPaymentAction.php     # admin confirms payment received
│
├── Infrastructure/
│   └── Persistence/
│       ├── Eloquent/Models/
│       │   ├── CartModel.php
│       │   ├── CartItemModel.php
│       │   ├── OrderModel.php
│       │   └── OrderItemModel.php
│       ├── Mappers/
│       │   ├── CartMapper.php
│       │   ├── OrderMapper.php
│       │   └── OrderItemMapper.php
│       └── Repositories/
│           ├── EloquentCartRepository.php
│           └── EloquentOrderRepository.php
│
├── Presentation/
│   └── Http/
│       ├── Controllers/
│       │   ├── Public/CartController.php       # X-Cart-Token; no auth
│       │   ├── Public/CheckoutController.php   # places order from cart
│       │   └── Admin/OrderController.php       # list, show, update status, confirm payment
│       ├── Requests/
│       │   ├── AddToCartRequest.php
│       │   ├── UpdateCartItemRequest.php
│       │   ├── CheckoutRequest.php
│       │   ├── AdminPlaceOrderRequest.php
│       │   └── UpdateOrderStatusRequest.php
│       ├── Resources/
│       │   ├── CartResource.php
│       │   ├── OrderResource.php
│       │   └── OrderItemResource.php
│       └── Middleware/
│           └── ResolveCartToken.php    # reads X-Cart-Token header, binds cart or 404
│
├── Infrastructure/
│   └── Notifications/
│       └── NewOrderNotification.php   # implements ShouldQueue; afterCommit=true
│
database/
└── migrations/
    ├── XXXX_create_carts_table.php
    ├── XXXX_create_cart_items_table.php
    ├── XXXX_create_orders_table.php
    └── XXXX_create_order_items_table.php
```

### Pattern 1: OrderStatus Enum with Guard Methods (Domain Layer)

**What:** PHP backed enum with an `allowedTransitionsFrom()` method. Domain entity calls this before changing state.
**When to use:** Any state transition validation. Keeps business rules out of controllers.

```php
// Source: Domain layer — no Laravel imports
enum OrderStatus: string
{
    case ChoXacNhan = 'cho_xac_nhan';
    case XacNhan    = 'xac_nhan';
    case DangGiao   = 'dang_giao';
    case HoanThanh  = 'hoan_thanh';
    case Huy        = 'huy';

    /** @return array<OrderStatus> */
    public function allowedNextStates(): array
    {
        return match ($this) {
            self::ChoXacNhan => [self::XacNhan, self::Huy],
            self::XacNhan    => [self::ChoXacNhan, self::DangGiao, self::Huy],
            self::DangGiao   => [self::XacNhan, self::HoanThanh, self::Huy],
            self::HoanThanh  => [],
            self::Huy        => [],
        };
    }

    public function canTransitionTo(OrderStatus $next): bool
    {
        return in_array($next, $this->allowedNextStates(), true);
    }

    public function isCancellable(): bool
    {
        return $this !== self::HoanThanh;
    }
}
```

Note: D-11 allows stepping BACK 1 state. The `allowedNextStates` array includes the previous state where applicable (`XacNhan` can go back to `ChoXacNhan`; `DangGiao` can go back to `XacNhan`).

### Pattern 2: PlaceOrderAction — Multi-Product Atomic Lock

**What:** Lock all product rows in ascending `id` order before any checks. This prevents deadlocks when two concurrent orders share some products but request locks in different orders.
**When to use:** Any code path that decrements stock for multiple products simultaneously.

```php
// Source: extends AdjustStockAction pattern from app/Application/Product/Actions/AdjustStockAction.php
// Key extension: sort product IDs ascending before locking

public function handle(Cart $cart, CheckoutData $data): Order
{
    return DB::transaction(function () use ($cart, $data): Order {
        // 1. Sort product IDs ascending to prevent deadlocks
        $productIds = collect($cart->items)
            ->pluck('productId')
            ->sort()   // ascending order
            ->values();

        // 2. Lock all rows before any check
        $products = [];
        foreach ($productIds as $productId) {
            $product = $this->products->findByIdForUpdate($productId);
            if ($product === null) {
                throw new ProductNotFoundException($productId);
            }
            if (! $product->isActive) {
                throw new InactiveProductInCartException($product->name);
            }
            $products[$productId] = $product;
        }

        // 3. Check and decrement each
        foreach ($cart->items as $item) {
            $product  = $products[$item->productId];
            $newStock = bcadd($product->stockQuantity, '-' . $item->quantity, 3);
            if (bccomp($newStock, '0', 3) < 0) {
                throw new InsufficientStockException($product->stockQuantity, $item->quantity);
            }
            $this->products->updateStock($item->productId, $newStock);
        }

        // 4. Create order + items, clear cart
        return $this->orders->createFromCart($cart, $data, $products);
    });
}
```

### Pattern 3: Notification Dispatched After Commit

**What:** Laravel `ShouldQueue` + `$afterCommit = true` property. Guarantees the job is only queued after the DB transaction commits. If the transaction rolls back, the job is never dispatched.
**When to use:** Any notification or job that depends on data created inside a transaction.

```php
// Source: Laravel 12.x official docs — Queues > Dispatching After Transactions
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;  // KEY: only queued after transaction commits

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Đơn hàng mới #' . $this->order->id)
            ->markdown('emails.orders.new-order', ['order' => $this->order]);
    }
}

// In PlaceOrderAction, after the transaction creates the order:
// $admin = UserModel::first(); // or from config
// Notification::send($admin, new NewOrderNotification($order));
```

### Pattern 4: Cart Token Middleware

**What:** Custom middleware reads `X-Cart-Token` header, loads cart, injects into request.
**When to use:** All cart endpoints.

```php
final class ResolveCartToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Cart-Token');
        if (! $token) {
            return response()->json([
                'success' => false, 'code' => 'CART_TOKEN_REQUIRED',
                'message' => 'Thiếu X-Cart-Token header.', 'errors' => (object)[],
            ], 401);
        }
        $cart = Cart::where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
        if (! $cart) {
            return response()->json([
                'success' => false, 'code' => 'CART_NOT_FOUND',
                'message' => 'Giỏ hàng không tồn tại hoặc đã hết hạn.', 'errors' => (object)[],
            ], 404);
        }
        $request->attributes->set('cart', $cart);
        return $next($request);
    }
}
```

### Pattern 5: Idempotency Middleware (from infinitypaul package)

**What:** Apply `EnsureIdempotency` middleware to the checkout endpoint. Package caches the response for the given `Idempotency-Key` (UUID). Subsequent calls with the same key return the cached response.

```php
// In routes/api.php, for the checkout route:
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware(\Infinitypaul\Idempotency\Middleware\EnsureIdempotency::class)
    ->name('checkout');
```

The package uses distributed locks (via Laravel's cache lock) to prevent concurrent duplicate requests from both proceeding. Cache TTL is configurable.

### Anti-Patterns to Avoid

- **Locking product rows outside a transaction:** `lockForUpdate()` without `DB::transaction()` has no serialization effect. Always wrap in a transaction.
- **Locking rows in arbitrary order:** Two concurrent transactions locking products [1,3] and [3,1] will deadlock. Always sort IDs ascending first.
- **Dispatching email job inside the transaction:** If the queue table insert is inside the transaction and it commits but the email fails to enqueue, the order exists but no notification is sent. Use `$afterCommit = true` so the queue insert happens AFTER commit.
- **Decrementing stock during cart operations:** D-07 prohibits this. Only `PlaceOrderAction` and `AdminPlaceOrderAction` touch stock.
- **Using `session()` for cart state:** D-01 explicitly requires UUID token in header. Sessions are not appropriate for a stateless API.
- **Using float arithmetic for money/stock:** Project uses `DECIMAL(10,3)` with `bcadd`/`bccomp`. Never cast to float.

---

## Database Schema Design (Claude's Discretion)

### Recommended Schema

**`carts` table:**
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
token           VARCHAR(36) UNIQUE NOT NULL   -- UUID
expires_at      TIMESTAMP NOT NULL            -- NOW() + 7 days, refreshed on activity
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**`cart_items` table:**
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
cart_id         FK → carts.id (CASCADE DELETE)
product_id      FK → products.id (RESTRICT)
quantity        DECIMAL(10,3) NOT NULL        -- matches product unit type
created_at      TIMESTAMP
updated_at      TIMESTAMP
UNIQUE(cart_id, product_id)                  -- D-04: accumulate; enforce at DB level
```

**`orders` table:**
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
customer_name   VARCHAR(255) NOT NULL
customer_phone  VARCHAR(20) NOT NULL
delivery_address TEXT NOT NULL
order_status    VARCHAR(20) NOT NULL DEFAULT 'cho_xac_nhan'
payment_method  VARCHAR(20) NOT NULL          -- cod|chuyen_khoan
payment_status  VARCHAR(20) NOT NULL DEFAULT 'chua_thanh_toan'
delivery_method VARCHAR(20) NULLABLE          -- noi_tinh|ngoai_tinh; admin sets later
total_amount    DECIMAL(12,0) NOT NULL        -- VND, integer cents not needed (VND is integer)
created_by      BIGINT UNSIGNED NULLABLE FK → users.id  -- NULL = customer; set = admin entry
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

**`order_items` table:**
```sql
id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
order_id        FK → orders.id (CASCADE DELETE)
product_id      FK → products.id (RESTRICT)
product_name    VARCHAR(255) NOT NULL         -- snapshot at order time
price_vnd       INT UNSIGNED NOT NULL         -- snapshot at order time
quantity        DECIMAL(10,3) NOT NULL
subtotal_vnd    INT UNSIGNED NOT NULL         -- price_vnd * quantity (integer for VND)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

Key schema decisions:
1. `order_items` snapshots `product_name` and `price_vnd` — since D-05 means prices can change, the order must record what was charged.
2. `orders.created_by` NULL = anonymous customer order; non-null = admin manual entry (ORDR-03).
3. `total_amount` as DECIMAL(12,0) — VND has no decimal component in practice.
4. No `cart_id` FK on orders — cart is deleted/expired after order creation; order is self-contained.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Duplicate order prevention | Custom cache check + mutex | `infinitypaul/idempotency-laravel` | Package handles distributed lock + payload validation + telemetry; hand-rolling misses concurrent duplicate race condition |
| Queued job after DB commit | `DB::afterCommit()` in action | `ShouldQueue` + `$afterCommit = true` on Notification | Standard Laravel pattern; testable with `Notification::fake()`; handles rollback automatically |
| Cart cleanup | Manual query in controller | `php artisan schedule:run` with `PruneExpiredCartsCommand` | Separation of concerns; cron handles cleanup without request-time overhead |
| Vietnamese phone validation | Custom regex string in multiple places | `'regex:/^0\d{9}$/'` in a Request rule (reusable) | Single source of truth |

**Key insight:** The existing `AdjustStockAction` with `DB::transaction + lockForUpdate + bcadd/bccomp` is the hardest part already solved. Phase 3 is largely an orchestration problem, not a new technical problem.

---

## Common Pitfalls

### Pitfall 1: Dispatching Notification Inside the Transaction
**What goes wrong:** `Notification::send()` called inside `DB::transaction()` — if the transaction rolls back (e.g., stock check fails for second product after first passed), the queued job still exists in the `jobs` table and the admin gets a ghost notification for an order that was never created.
**Why it happens:** Laravel's database queue driver inserts into the `jobs` table inside the same connection/transaction.
**How to avoid:** Set `public bool $afterCommit = true` on the Notification class. Laravel's queue driver will buffer the job and only insert it after the transaction commits.
**Warning signs:** Admin receives email for orders that don't exist in the DB.

### Pitfall 2: Deadlock When Locking Multiple Product Rows
**What goes wrong:** Two concurrent orders each contain products A and B. Order 1 locks product A then tries to lock B; Order 2 locks B then tries to lock A. MySQL detects the deadlock and kills one transaction.
**Why it happens:** Lock acquisition order is not deterministic without explicit sorting.
**How to avoid:** Always sort product IDs ascending before the lock loop: `$productIds->sort()`. This ensures both transactions always try to acquire locks in the same sequence.
**Warning signs:** `Illuminate\Database\QueryException: SQLSTATE[40001]: Serialization failure: 1213 Deadlock found` in logs under concurrent load testing.

### Pitfall 3: Cart Price Used Instead of Current Product Price
**What goes wrong:** CartItem stores `price_at_add_time`. At checkout, that stored price is used for order total instead of querying fresh `products.price_vnd`.
**Why it happens:** It feels "natural" to snapshot the price when adding to cart.
**How to avoid:** D-05 is explicit — the cart does NOT store prices. At checkout time, `PlaceOrderAction` reads the current `products.price_vnd` from the locked product row and snapshots it in `order_items.price_vnd`.
**Warning signs:** Price changes by admin don't affect pending cart checkouts.

### Pitfall 4: Inactive Product Bypasses Checkout Validation
**What goes wrong:** `PlaceOrderAction` doesn't check `product.isActive` when locking rows, so a deactivated product (hidden from storefront) can still be ordered.
**Why it happens:** D-06 says inactive products are shown in cart as "hết hàng" but the check must happen inside the transaction after locking.
**How to avoid:** After `findByIdForUpdate()`, explicitly check `if (!$product->isActive)` before proceeding. Throw `InactiveProductInCartException` if found.
**Warning signs:** Orders created for products with `is_active = false`.

### Pitfall 5: PHPStan Fails on Enum Casts in OrderModel
**What goes wrong:** PHPStan reports `Cannot access property ... on mixed` for enum-casted properties on Eloquent models.
**Why it happens:** Known Larastan 3.x limitation — it doesn't infer enum types from the `casts()` method return array.
**How to avoid:** Add `@property OrderStatus $order_status` and other enum `@property` annotations to `OrderModel`. Established pattern from Phase 2 (`ProductModel` has `@property UnitType $unit_type`).
**Warning signs:** PHPStan level 6 failures on `$model->order_status->allowedNextStates()`.

### Pitfall 6: Idempotency Key Applied to Wrong Routes
**What goes wrong:** `EnsureIdempotency` middleware applied to cart endpoints (add/update/remove) in addition to checkout, causing unexpected 409 responses when the same product is added to cart twice.
**Why it happens:** Broad application of the middleware.
**How to avoid:** Apply `EnsureIdempotency` ONLY to `POST /v1/checkout` (and `POST /v1/admin/orders` for manual entry). Cart operations are naturally idempotent by design (D-04 accumulates qty).

---

## Code Examples

### Verified Pattern: lockForUpdate with sorting (anti-deadlock)

```php
// Source: Extends EloquentProductRepository::findByIdForUpdate() pattern
// found in app/Infrastructure/Persistence/Repositories/EloquentProductRepository.php
// Reference: Laravel Deadlocks article — always acquire locks in same order

$sortedProductIds = collect($cart->items)
    ->pluck('productId')
    ->unique()
    ->sort()      // ascending — prevents deadlock
    ->values()
    ->all();

$lockedProducts = [];
foreach ($sortedProductIds as $id) {
    $lockedProducts[$id] = $this->products->findByIdForUpdate($id);
}
```

### Verified Pattern: afterCommit on ShouldQueue Notification

```php
// Source: Laravel 12.x docs — Queues > Dispatching After Transactions
// https://laravel.com/docs/12.x/queues#jobs-and-database-transactions

final class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;
    // ...
}
```

### Verified Pattern: Existing JSON envelope (from bootstrap/app.php)

New domain exceptions for Phase 3 follow the same match arm pattern in `bootstrap/app.php`:

```php
// Add to the match block in bootstrap/app.php:
$e instanceof \App\Domain\Order\Exceptions\OrderNotFoundException => [
    404, 'ORDER_NOT_FOUND', $e->getMessage(), (object) [],
],
$e instanceof \App\Domain\Order\Exceptions\InvalidOrderTransitionException => [
    422, 'INVALID_ORDER_TRANSITION', $e->getMessage(), (object) [],
],
$e instanceof \App\Domain\Order\Exceptions\CartNotFoundException => [
    404, 'CART_NOT_FOUND', $e->getMessage(), (object) [],
],
$e instanceof \App\Domain\Order\Exceptions\InactiveProductInCartException => [
    422, 'INACTIVE_PRODUCT_IN_CART', $e->getMessage(), (object) [],
],
```

### Verified Pattern: Bank info from config (D-20)

```php
// config/bank.php
return [
    'account_name' => env('BANK_ACCOUNT_NAME', ''),
    'account_number' => env('BANK_ACCOUNT_NUMBER', ''),
    'bank_name' => env('BANK_NAME', ''),
];

// In CheckoutController response for chuyen_khoan:
'bank_info' => $request->payment_method === 'chuyen_khoan'
    ? config('bank')
    : null,
```

### Verified Pattern: Cart expiry refresh (lazy approach)

```php
// In EloquentCartRepository::findByToken():
$cart = CartModel::where('token', $token)
    ->where('expires_at', '>', now())
    ->first();

if ($cart) {
    // Refresh expiry on activity (lazy approach chosen over scheduled-only)
    $cart->update(['expires_at' => now()->addDays(7)]);
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Dispatching jobs inside transaction | `$afterCommit = true` on ShouldQueue | Laravel 8+ (mainstream by 2023) | Prevents ghost notifications on rollback |
| `DB::transaction()` without lock ordering | Always sort IDs before lockForUpdate loop | Industry best practice | Eliminates deadlock risk under concurrency |
| Hand-rolled idempotency cache check | `infinitypaul/idempotency-laravel` with distributed lock | Package v1.0.5 released 2025-06-16 | Handles race condition (two identical requests arrive simultaneously) that simple cache check misses |

**Deprecated/outdated for this project:**
- Session-based carts: D-01 explicitly forbids them. Token header is the chosen approach.
- Float for stock/price math: Never. `bcadd`/`bccomp` with DECIMAL(10,3) is established in Phase 2.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|-------------|-----------|---------|----------|
| PHP 8.x | Runtime | Yes | 8.5.2 | — |
| Laravel 12 | Framework | Yes | 12.56.0 | — |
| MySQL/SQLite | Database | Yes (SQLite for tests) | — | — |
| Database queue driver | NOTI-01 async email | Yes | configured in .env | — |
| Mail (log driver) | NOTI-01 email delivery | Yes (log driver for dev) | — | Configure SMTP for production |
| `infinitypaul/idempotency-laravel` | ORDR-02 idempotency | Not installed | — | Install via composer (no blocking dep) |

**Missing dependencies with no fallback:**
- None that block execution.

**Missing dependencies with fallback:**
- `infinitypaul/idempotency-laravel` not installed — `composer require infinitypaul/idempotency-laravel:^1.0.5` installs cleanly on PHP 8.x + Laravel 12.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.x (pestphp/pest ^4.0) |
| Config file | `phpunit.xml` / `tests/Pest.php` |
| Quick run command | `php artisan test --filter=OrderTest` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CART-01 | POST /cart creates cart with token | Feature | `php artisan test --filter=CartTest` | Wave 0 |
| CART-02 | GET /cart/{token} returns items with prices and totals | Feature | `php artisan test --filter=CartTest` | Wave 0 |
| CART-03 | PATCH/DELETE cart item updates/removes correctly | Feature | `php artisan test --filter=CartTest` | Wave 0 |
| CART-04 | Adding to cart does NOT change product.stock_quantity | Feature | `php artisan test --filter=CartTest` | Wave 0 |
| ORDR-01 | Concurrent orders don't oversell — stock decremented once | Feature | `php artisan test --filter=PlaceOrderConcurrentTest` | Wave 0 |
| ORDR-02 | Same idempotency key returns same order, no duplicate | Feature | `php artisan test --filter=IdempotencyTest` | Wave 0 |
| ORDR-03 | Admin manual order uses same atomic lock | Feature | `php artisan test --filter=AdminOrderTest` | Wave 0 |
| ORDR-04 | Valid transitions succeed; invalid transitions rejected | Unit | `php artisan test --filter=OrderStatusTest` | Wave 0 |
| ORDR-05 | Cancellation restores stock in same transaction | Feature | `php artisan test --filter=CancelOrderTest` | Wave 0 |
| ORDR-06 | GET /admin/orders/{id} returns full detail | Feature | `php artisan test --filter=AdminOrderTest` | Wave 0 |
| ORDR-07 | PATCH status rejects invalid transition with Vietnamese error | Feature | `php artisan test --filter=AdminOrderTest` | Wave 0 |
| PAYM-01 | payment_status is separate from order_status | Feature | `php artisan test --filter=PaymentTest` | Wave 0 |
| PAYM-02 | COD order starts chua_thanh_toan | Feature | `php artisan test --filter=PaymentTest` | Wave 0 |
| PAYM-03 | chuyen_khoan response includes bank info | Feature | `php artisan test --filter=CheckoutTest` | Wave 0 |
| PAYM-04 | Admin PATCH confirms payment | Feature | `php artisan test --filter=PaymentTest` | Wave 0 |
| DELV-01 | Checkout requires name, valid VN phone, address | Feature | `php artisan test --filter=CheckoutValidationTest` | Wave 0 |
| DELV-02 | delivery_method nullable; admin sets via PATCH | Feature | `php artisan test --filter=AdminOrderTest` | Wave 0 |
| NOTI-01 | New order queues email after transaction commit | Feature | `php artisan test --filter=NotificationTest` | Wave 0 |
| NOTI-02 | Email contains Vietnamese product list and address | Feature | `php artisan test --filter=NotificationTest` | Wave 0 |
| TECH-05 | Scribe generates docs for new routes | Smoke | Manual: `php artisan scribe:generate` | N/A |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=[relevant test class]`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green + PHPStan level 6 pass before `/gsd:verify-work`

### Wave 0 Gaps (all test files are new)
- [ ] `tests/Feature/Public/CartTest.php` — covers CART-01..04
- [ ] `tests/Feature/Public/CheckoutTest.php` — covers ORDR-01..02, PAYM-03, DELV-01
- [ ] `tests/Feature/Admin/AdminOrderTest.php` — covers ORDR-03, ORDR-06..07, DELV-02
- [ ] `tests/Feature/Admin/CancelOrderTest.php` — covers ORDR-05
- [ ] `tests/Feature/Admin/PaymentTest.php` — covers PAYM-01..04
- [ ] `tests/Feature/Notification/NotificationTest.php` — covers NOTI-01..02
- [ ] `tests/Unit/OrderStatusTest.php` — covers ORDR-04 (pure enum guard method test, no DB)

---

## Open Questions

1. **Cart cleanup strategy: scheduled vs. lazy**
   - What we know: D-02 says 7 days expiry; lazy cleanup means `expires_at` checked on each request; scheduled means a cron job deletes expired rows.
   - What's unclear: At this scale, either approach is fine. Lazy cleanup keeps the table small naturally. A scheduled command is cleaner.
   - Recommendation: Use BOTH — lazy (check `expires_at > now()` in repository query, don't extend stale carts) AND a weekly `php artisan cart:prune-expired` scheduled command. Minimal code; belt-and-suspenders.

2. **Idempotency key scope: checkout only, or also admin manual order?**
   - What we know: D-21 explicitly mentions the checkout API. D-23 says admin manual entry uses the same lock mechanism.
   - What's unclear: Should admin manual orders also require an `Idempotency-Key`? The context doesn't say explicitly.
   - Recommendation: Apply idempotency middleware to admin manual order creation too — admin might double-click "save" on a slow connection. Low cost, high safety.

3. **Order total rounding with DECIMAL stock × integer price**
   - What we know: `price_vnd` is `INT UNSIGNED`; `quantity` is `DECIMAL(10,3)`. Multiplying them gives a non-integer.
   - What's unclear: VND is always an integer. Does 2.500 kg × 150,000 VND/kg = 375,000 VND (exact). But 1.333 kg × 150,000 = 199,950 VND — acceptable? Or round to nearest 1000?
   - Recommendation: Store exact result as `DECIMAL(12,0)` rounded to nearest VND via `(int) round(bcmul($price, $qty, 3))`. Mom is the admin and sees the final total; she handles any sub-VND rounding case informally.

---

## Project Constraints (from CLAUDE.md)

All directives from CLAUDE.md that constrain Phase 3 planning:

| Directive | Impact on Phase 3 |
|-----------|------------------|
| **Laravel 12.x mandatory** | All packages must support Laravel 12; confirmed for `infinitypaul` |
| **MySQL `SELECT FOR UPDATE` / `lockForUpdate()`** | PlaceOrderAction MUST use this; no optimistic locking alternative |
| **Clean Architecture: Domain has NO Laravel imports** | `OrderStatus` enum, `Order` entity, `Cart` entity: pure PHP, no Eloquent/Laravel |
| **`bcadd`/`bccomp` for DECIMAL precision** | All stock decrement and order total calculation uses bc functions |
| **Database queue driver (not Redis)** | No Redis, no Horizon; `QUEUE_CONNECTION=database` is correct |
| **Vietnamese messages, labels, errors** | All `$e->getMessage()`, validation messages, email content in Vietnamese |
| **Sanctum for admin auth** | Admin order routes use `middleware('auth:sanctum')` |
| **JSON envelope `{ success, data, meta }` / `{ success: false, code, message, errors }`** | All new controller responses follow this pattern |
| **Pest ^4.x for tests** | All new test files use Pest functional syntax, `describe()` / `it()` |
| **PHPStan level 6+** | Add `@property` annotations on all new Eloquent models for enum casts |
| **Scribe for API docs (TECH-05)** | Add `@group`, `@bodyParam`, `@response` docblocks to all new controllers |
| **`infinitypaul/idempotency-laravel` mentioned in CLAUDE.md** | Use this package for ORDR-02; do not hand-roll |

---

## Sources

### Primary (HIGH confidence)
- Existing codebase — `AdjustStockAction.php`, `EloquentProductRepository.php`, `ProductModel.php`, `bootstrap/app.php`, `routes/api.php` — direct pattern reuse
- `composer.json` — verified installed packages and versions
- `.env.example` — confirmed `QUEUE_CONNECTION=database`, `MAIL_MAILER=log`
- Laravel 12.56.0 running locally — framework version confirmed

### Secondary (MEDIUM confidence)
- `infinitypaul/idempotency-laravel` Packagist page — v1.0.5, released 2025-06-16, Laravel 12 compatible via illuminate/support ^12.0 (verified via WebFetch)
- Laravel News article on dispatching events after DB transactions — confirmed `$afterCommit = true` pattern
- WebSearch: deadlock prevention via consistent lock ordering — multiple sources agree on sort-by-id approach

### Tertiary (LOW confidence)
- State machine via backed enum (DEV Community articles) — simple enough that no external verification needed; pure PHP feature

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified via composer.json + Packagist
- Architecture: HIGH — directly extends proven Phase 2 patterns
- Schema: HIGH — follows existing migration patterns; schema choices are straightforward
- Pitfalls: HIGH — deadlock and afterCommit pitfalls verified from multiple sources
- State machine design: HIGH — pure PHP feature, well-understood

**Research date:** 2026-03-29
**Valid until:** 2026-04-28 (30 days; stable Laravel ecosystem)
