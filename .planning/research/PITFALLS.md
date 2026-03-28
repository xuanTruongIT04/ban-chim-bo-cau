# Domain Pitfalls

**Domain:** Laravel e-commerce backend with Clean Architecture (poultry/livestock retail)
**Researched:** 2026-03-28
**Project:** Ban Chim Bo Cau — backend API for small-scale family poultry business

---

## Category 1: Clean Architecture in Laravel

### Pitfall 1.1: Eloquent Models Leaking into the Domain Layer

**What goes wrong:** Developers use `App\Models\Product` (an Eloquent model) directly as a domain entity. The domain layer now depends on the ORM — any Eloquent method, relationship, or event becomes a dependency of business logic.

**Why it happens:** Laravel generates Eloquent models by default, and it feels natural to pass them everywhere. The line between "the product" and "the Eloquent representation of a product" gets blurred.

**Consequences:**
- Unit testing the domain requires a database connection
- Business logic is polluted with `$product->save()`, `$product->load()` calls
- Swapping persistence (e.g., from MySQL to a different store) requires touching domain code
- Eloquent magic (observers, casts, lazy loading) fires unexpectedly during domain operations

**Prevention:**
- Define plain PHP domain entities (`Domain/Entities/Product.php`) with no Eloquent inheritance
- Create Eloquent models only in the Infrastructure layer (`Infrastructure/Persistence/Models/EloquentProduct.php`)
- Write a mapper class that converts between Eloquent model and domain entity — never let the two leak into each other
- Declare domain entity properties explicitly as typed PHP properties, not inherited from `Model`

**Warning signs:**
- Domain entity files contain `extends Model` or `use HasFactory`
- A service in `Application/` calls `$product->save()` directly
- Tests for use cases require `RefreshDatabase`

**Phase to address:** Phase 1 (foundation). Establish the entity/model split before writing any business logic. Retrofitting this later requires touching every layer.

---

### Pitfall 1.2: Repository Interfaces Defined in the Infrastructure Layer

**What goes wrong:** The `ProductRepositoryInterface` lives in `Infrastructure/Repositories/` rather than in `Domain/` or `Application/`. The application layer imports from infrastructure to satisfy the contract, reversing the dependency direction.

**Why it happens:** Developers place the interface next to its implementation because "they belong together."

**Consequences:** The Dependency Inversion Principle is violated — domain/application now depends on infrastructure, not the other way around. Circular dependencies can emerge.

**Prevention:**
- Define all repository interfaces inside `Domain/Contracts/` or `Application/Contracts/`
- Infrastructure implementations depend on the interface, never the reverse
- Enforce this with a simple project convention in code review

**Warning signs:**
- `use App\Infrastructure\Repositories\ProductRepositoryInterface;` appears in an Application service

**Phase to address:** Phase 1 (foundation/scaffolding).

---

### Pitfall 1.3: Fat Use Cases / Application Services That Do Everything

**What goes wrong:** The `PlaceOrderUseCase` becomes a 300-line class handling validation, stock checking, price calculation, order creation, notification dispatch, and audit logging — all in `execute()`.

**Why it happens:** It starts small and grows. Clean Architecture correctly forbids business logic in controllers, so it all migrates to the use case layer.

**Consequences:** Use cases become impossible to test in isolation. A change to notification logic breaks inventory tests. The use case re-implements things that should be domain logic.

**Prevention:**
- Each use case does one thing: orchestrate. Business rules live in domain entities and domain services.
- Extract `InventoryService`, `PriceCalculator` as domain services called by the use case
- Use domain events or callbacks for cross-cutting concerns (notifications), dispatched after the transaction commits
- Keep use case classes under ~80 lines as a forcing function

**Warning signs:**
- Use case `execute()` method exceeds 50 lines
- Use case directly formats notification messages
- Multiple try/catch blocks inside a single use case

**Phase to address:** Phase 2 (order processing). Most bloat accumulates during order and payment logic.

---

### Pitfall 1.4: Over-Engineering for This Scale

**What goes wrong:** Full CQRS, Event Sourcing, Hexagonal Architecture with explicit ports and adapters, SAGA patterns — applied to a family business handling tens of orders per day.

**Why it happens:** Developers read Clean Architecture books and apply every pattern simultaneously.

**Consequences:** 10x the boilerplate, 10x the time to add a field to a form, team overwhelm, eventual abandonment of the patterns.

**Prevention for this project:**
- Use Clean Architecture layers (Domain / Application / Infrastructure / Presentation) without strict CQRS separation
- Eloquent is fine in the Infrastructure layer — no need to build a custom Query Bus
- Domain events are optional for v1; use Laravel's built-in events in the Application layer instead
- No event sourcing — a status history table is sufficient for this scale
- Apply the architecture where it provides value (inventory, order logic) and use vanilla Laravel for simpler CRUD (categories, product images)

**Warning signs:**
- More time spent on infrastructure wiring than on actual features
- Adding a new product field requires changes to 7+ files

**Phase to address:** All phases. Establish "enough architecture" guidelines in Phase 1.

---

## Category 2: Inventory Management

### Pitfall 2.1: Oversell via Read-Modify-Write Without a Lock

**What goes wrong:** Two concurrent requests both read `stock = 5`, both check `5 >= 2` (passes), both write `stock = 3`. You sold 4 units from a stock of 5 — net oversell of 1.

**Why it happens:** Standard Eloquent reads are non-locking. The time gap between `find()` and `save()` is a race window.

**Consequences:** Negative stock, orders that cannot be fulfilled, customer complaints.

**Prevention:**
```php
// CORRECT: Wrap in transaction + pessimistic lock
DB::transaction(function () use ($productId, $quantity) {
    $product = Product::lockForUpdate()->find($productId);
    if ($product->stock < $quantity) {
        throw new InsufficientStockException();
    }
    $product->decrement('stock', $quantity);
});
```

- `lockForUpdate()` MUST be inside a `DB::transaction()` — it has no effect outside one
- For MySQL: use InnoDB (not MyISAM) — row-level locking requires InnoDB
- Alternative atomic approach for simple cases: `Product::where('id', $id)->where('stock', '>=', $quantity)->decrement('stock', $quantity)` — check affected rows count equals 1

**Warning signs:**
- `lockForUpdate()` used without a wrapping `DB::transaction()`
- Stock reads and writes are in separate database calls
- Unit tests pass but integration tests under load show negative stock

**Phase to address:** Phase 1 (inventory model). Lock strategy must be designed before any order flow is built.

---

### Pitfall 2.2: Mixed Unit Types Without a Single Source of Truth

**What goes wrong:** Products have a `stock` integer column. Pigeons are counted in `units` (birds), but pigeon meat (thịt) is measured in `kg`. The column stores "3" for both — but 3 what?

**This is specific to this project:** The product catalog explicitly mixes per-unit inventory (bồ câu sống — living pigeons) and per-weight inventory (thịt — meat sold by kg).

**Consequences:**
- Displaying "3 in stock" to a customer ordering by kg is meaningless
- Validation logic silently applies wrong rules (integer check on a decimal quantity)
- Reports mix unit counts and kg weights into nonsensical totals
- Adding a new unit type later requires a migration and business logic change

**Prevention:**
- Add an explicit `unit_type` column to products: `enum('unit', 'kg', 'gram')`
- Store stock as `DECIMAL(10, 3)` to handle fractional weights — never `INTEGER`
- Validation rules branch on `unit_type`: unit products reject decimal quantities, kg products accept them
- Display layer always appends the unit: "còn 3 con" vs "còn 2.5 kg"
- Enforce unit consistency in the domain entity: `Product` has a `UnitType` value object that carries its own validation rules

**Warning signs:**
- `stock` column is `INTEGER` in the migration
- A customer can order "0.5 birds"
- Reports show total inventory as a plain number with no unit label

**Phase to address:** Phase 1 (product model). The `unit_type` and stock precision must be in the initial migration — impossible to backfill cleanly.

---

### Pitfall 2.3: No Inventory Reservation State — Immediate Deduction on Place Order

**What goes wrong:** Stock is permanently decremented when an order is placed (status: `pending_confirmation`). If the order is cancelled, a manual `increment` must compensate. If the compensation fails (bug, exception), stock is permanently lost.

**Consequences:** Available stock understated during peak periods. Cancelled orders don't reliably restore stock.

**Prevention for this scale:**
- Deduct stock when order is placed (pessimistic, simple approach)
- Release stock immediately on cancellation or rejection — always in the same database transaction as the status change
- Keep it simple: for a family business doing tens of orders/day, a two-column approach works well: `stock_total` (physical count) and `stock_reserved` (committed to confirmed orders). Available = `stock_total - stock_reserved`
- Reservation approach: increment `stock_reserved` on order placement, decrement `stock_reserved` + `stock_total` on fulfilment, decrement only `stock_reserved` on cancellation

**Warning signs:**
- Status transitions and stock adjustments are in separate database transactions
- No test covers: "place order, then cancel — does stock return to original value?"

**Phase to address:** Phase 2 (order flow). Design the reservation model alongside the order state machine.

---

### Pitfall 2.4: Forgetting to Lock When Checking Multiple Products in One Order

**What goes wrong:** An order contains 3 products. The code checks stock for product A, then B, then C in sequence — each as a separate query. Between checks, another order depletes product B.

**Prevention:**
- Lock all products in the order in a single query at the start of the transaction:
  `Product::whereIn('id', $productIds)->lockForUpdate()->get()`
- Always lock in a deterministic order (e.g., ascending by `id`) to prevent deadlocks when two concurrent orders share products

**Warning signs:**
- Stock check loop with individual `find()` calls per product inside a transaction
- No test with concurrent orders sharing products

**Phase to address:** Phase 2 (order placement logic).

---

## Category 3: Order Processing

### Pitfall 3.1: State Machine Without Enforced Transitions

**What goes wrong:** Order status is stored as a string column. Any code can set it to any value. An order goes from `pending` directly to `completed` skipping `confirmed` and `delivering`. A cancelled order gets marked `delivering`.

**Consequences:** Inventory adjustments that depend on state transitions fire incorrectly. Reports show impossible data. Manual corrections needed.

**Prevention:**
- Define valid transitions explicitly as a map:
  ```php
  protected array $transitions = [
      'pending_confirmation' => ['confirmed', 'cancelled'],
      'confirmed'            => ['delivering', 'cancelled'],
      'delivering'           => ['completed', 'cancelled'],
      'completed'            => [],
      'cancelled'            => [],
  ];
  ```
- Throw `InvalidOrderTransitionException` if a transition is not in the map
- All status changes go through a single `Order::transition(string $newStatus)` domain method — never set `$order->status = 'completed'` directly
- Use a lightweight state machine library (e.g., `spatie/laravel-model-states`) or implement the transition map in the domain entity

**Warning signs:**
- Direct assignment `$order->status = $newStatus` in controller or service
- No exception thrown on invalid transitions in tests
- Status is validated only at the HTTP layer, not in the domain

**Phase to address:** Phase 2 (order domain model). Build the state machine before the first status-change endpoint.

---

### Pitfall 3.2: Duplicate Orders from Network Retry

**What goes wrong:** Customer submits an order. The request times out (slow network), the frontend retries. Two identical orders are created. For manual entry via phone/Zalo, mom creates the same order twice.

**Consequences:** Double charges (for COD, double delivery attempts), double stock deduction, customer confusion.

**Prevention:**
- Require an `Idempotency-Key` header on POST /orders: UUID generated by the client
- Store processed idempotency keys in a `idempotency_keys` table with `(key, response_body, expires_at)`
- On duplicate key: return the cached response (same HTTP status + body) without re-processing
- For manual admin entry: add a server-side 60-second debounce per (admin user, product set, quantity) combination as a second layer
- Use `Cache::lock("order-create-{$idempotencyKey}", 10)` to prevent concurrent requests with the same key

**Warning signs:**
- POST /orders has no idempotency mechanism
- Double-submitting the create order form creates two orders in test
- No unique constraint or lookup on idempotency keys table

**Phase to address:** Phase 2 (order creation endpoint). Implement before exposing to the frontend.

---

### Pitfall 3.3: Payment Status Conflated with Order Status

**What goes wrong:** Order `status` encodes both fulfilment state and payment state. "Paid" becomes a status value alongside "delivering". This creates invalid transitions like: `paid → confirmed → cancelled` (was it refunded?) or `delivering → paid` (what does that mean?).

**This project's context:** COD and manual bank transfer are the only payment methods. Payment is always confirmed manually.

**Consequences:**
- Business logic to check "has this order been paid?" becomes complex string matching
- Adding a new payment method later requires adding status values and rewriting transition logic
- Reports cannot separate "delivery status" from "payment status"

**Prevention:**
- Maintain two separate fields: `status` (order fulfilment lifecycle) and `payment_status` (`unpaid | payment_pending | paid | refunded`)
- The order state machine governs `status` only
- Payment confirmation updates `payment_status` independently
- For COD: `payment_status` stays `unpaid` until mom manually marks it paid after delivery
- For bank transfer: `payment_status` moves to `payment_pending` when customer claims to have transferred, then `paid` when mom confirms

**Warning signs:**
- `status` enum contains values like `payment_confirmed` or `awaiting_payment`
- Business logic uses `$order->status === 'paid'` to determine if inventory should be released

**Phase to address:** Phase 2 (order + payment model design). Separate the two state machines from the start.

---

### Pitfall 3.4: Stock Not Released on Order Cancellation in the Same Transaction

**What goes wrong:** Order is cancelled. Code updates `order.status = 'cancelled'`, commits, then increments stock in a separate database call. If the second call fails (timeout, exception, deploy mid-request), order is cancelled but stock is never returned.

**Consequences:** Available stock permanently understated. Mom thinks she has less stock than she does and turns away customers.

**Prevention:**
- Wrap status update + stock release in a single `DB::transaction()`
- Domain event approach: dispatch `OrderCancelled` event inside the transaction; the listener that releases stock runs synchronously within the same transaction (not via a queue)
- Test: cancel an order 100 times, assert stock is always restored

**Warning signs:**
- Stock adjustment happens in a queued job triggered by order cancellation
- Status update and stock release are in separate try/catch blocks

**Phase to address:** Phase 2 (order state transitions).

---

## Category 4: API Design

### Pitfall 4.1: Inconsistent Error Response Format

**What goes wrong:** Validation errors return `{"errors": {"field": ["msg"]}}`, business logic errors return `{"message": "Insufficient stock"}`, and server errors return Laravel's default HTML error page or a bare `{"exception": "..."}`.

**Consequences:** The frontend must handle 3+ different error shapes. Localization of error messages is scattered. When a Vietnamese error message needs updating, it exists in multiple formats.

**Prevention:**
- Define one error envelope for all responses:
  ```json
  {
    "success": false,
    "code": "INSUFFICIENT_STOCK",
    "message": "Không đủ hàng để đặt",
    "errors": {}
  }
  ```
- Use a global exception handler (`app/Exceptions/Handler.php`) that normalizes all errors to this shape
- Use machine-readable error codes (all caps, snake_case) so the frontend can switch on them for localized display
- Never let Laravel's default HTML error page reach a JSON API client — set `Accept: application/json` handling in the exception handler

**Warning signs:**
- Frontend code contains `if (error.message)` in some places and `if (error.errors)` in others
- Some 400 responses return HTML (triggered by `abort(400)`)

**Phase to address:** Phase 1 (API foundation). Establish the error format before writing any endpoint.

---

### Pitfall 4.2: No API Versioning from the Start

**What goes wrong:** All routes are at `/api/products`. The frontend is built against this. Six months later, the product response shape changes (adding `unit_type`). Breaking change. The frontend breaks.

**Consequences:** Coordinated deploys between backend and frontend are required. Any future consumer (a mobile app, an integration) is broken.

**Prevention:**
- Prefix all routes with `/api/v1/` from day one — costs nothing at the start
- For this project scale, URL versioning is sufficient: `/api/v1/orders`
- Commit to not breaking `v1` once external consumers (the frontend) depend on it

**Warning signs:**
- Route file contains `Route::prefix('api')->group(...)`  without a version segment

**Phase to address:** Phase 1 (routing setup). One line of config, zero cost.

---

### Pitfall 4.3: Exposing Internal Domain Structure in API Responses

**What goes wrong:** The `/api/v1/orders` response includes `eloquent_model_id`, internal status enum integers, database foreign keys, and fields named after database columns. The frontend now depends on the database schema.

**Prevention:**
- Always pass API responses through Laravel API Resources (`JsonResource`)
- API Resources in the Presentation layer rename, reshape, and selectively expose fields
- Never return `$order->toArray()` directly from a controller
- Internal IDs (integer primary keys) can be exposed, but consider UUIDs if public-facing URLs should not be enumerable

**Warning signs:**
- Controllers return `return response()->json($order)` without a Resource wrapper
- The API response contains `created_at`, `updated_at`, and all raw database columns

**Phase to address:** Phase 1 (API foundation). Establish Resource classes from the first endpoint.

---

### Pitfall 4.4: Guest Cart Not Protected Against Inventory Inflation

**What goes wrong:** Public (unauthenticated) users can add items to a cart. Cart items are persisted with a session/token. Nothing prevents the same guest from opening 10 browser tabs and reserving the entire stock into carts that are never checked out.

**Consequences:** Available stock appears depleted to real customers. Mom sees false "out of stock" on her dashboard.

**Prevention:**
- Do NOT deduct stock when adding to cart — only deduct (or reserve) at order placement
- Cart is advisory only: "intended to buy" — not a stock reservation
- At checkout, re-validate stock live; if stock is insufficient since cart was loaded, return an error
- Rate-limit the "add to cart" and "place order" endpoints by IP for guest users

**Warning signs:**
- Stock is decremented in `CartController::addItem()`
- No re-validation of stock at order creation time

**Phase to address:** Phase 2 (cart + order flow integration).

---

## Category 5: Laravel-Specific Pitfalls

### Pitfall 5.1: N+1 Queries in Order and Product Listings

**What goes wrong:** Listing 20 orders: 1 query to get orders, then 20 queries to get each order's customer, 20 more for each order's items, 20 more for each item's product. 61+ queries for one page.

**Consequences:** Dashboard load time of 5–10 seconds. Mom's simple order list becomes unusable.

**Prevention:**
- Enable `Model::preventLazyLoading()` in `AppServiceProvider::boot()` for non-production environments — this throws an exception the moment a lazy load fires, catching N+1 issues at development time
- In production, use `Model::handleLazyLoadingViolationUsing()` to log instead of throw
- Always eager-load known relationships: `Order::with(['customer', 'items.product'])->paginate()`
- Use Laravel Debugbar or Telescope during development to confirm query counts per request

**Warning signs:**
- Order list endpoint issues 30+ queries for 10 records
- No `with()` clause on collection queries
- `$order->customer->name` appears in a loop without prior eager loading

**Phase to address:** Phase 3 (reporting and dashboard). Profile all list endpoints before shipping.

---

### Pitfall 5.2: Business Logic in Eloquent Observers or Model Events

**What goes wrong:** An Eloquent `Observer` on `Order` fires inventory adjustments on `created`, `updated`. This implicit logic is invisible when reading the use case code. It fires unexpectedly during database seeding, testing, or data migrations.

**Consequences:** Seeding test data adjusts real inventory. Running a migration that touches orders triggers business logic. Race conditions emerge because observers fire outside of the explicit transaction in the use case.

**Prevention:**
- No business logic in Eloquent observers or `boot()` methods
- Observers are permitted for infrastructure concerns: cache invalidation, search index updates
- Domain events (explicitly dispatched from use cases) replace observer-based logic
- In tests, if observers must be present, use `Event::fake()` to suppress them

**Warning signs:**
- `OrderObserver::created()` contains inventory adjustment logic
- Tests produce side effects (emails, stock changes) that weren't explicitly triggered

**Phase to address:** Phase 1 and 2. Establish the "no business logic in observers" rule before observers are written.

---

### Pitfall 5.3: Sanctum Token Expiry Not Configured

**What goes wrong:** Laravel Sanctum tokens never expire by default. A token issued to mom's browser in January is still valid in December. A token issued to a customer's guest session never expires.

**Consequences:** Stolen tokens grant permanent access. Guest carts accumulate forever. The `personal_access_tokens` table grows unbounded.

**Prevention:**
- Set `expiration` in `config/sanctum.php`: admin tokens expire in 30 days, guest tokens in 7 days
- Schedule `Sanctum::pruneExpiredTokens()` (or manual cleanup) daily via Laravel Scheduler
- For admin (mom's interface): implement token refresh on activity — extend expiry on each authenticated request

**Warning signs:**
- `config/sanctum.php` has `expiration => null`
- `personal_access_tokens` table has rows from months ago

**Phase to address:** Phase 1 (authentication setup).

---

### Pitfall 5.4: Database Transactions Swallowing Exceptions Silently

**What goes wrong:**
```php
DB::transaction(function () {
    // ...
    try {
        $this->notificationService->notify($order);
    } catch (\Exception $e) {
        // silently ignore
    }
});
```
The notification fails. The catch block prevents the exception from bubbling. The transaction commits. The order is created but the notification never sent — and no one knows.

**Prevention:**
- Never silently catch exceptions inside a database transaction
- Side effects (notifications, emails, webhooks) that can fail should be dispatched AFTER the transaction commits, not inside it
- Use Laravel's `DB::afterCommit()` hook (Laravel 8+) or dispatch a queued job after commit:
  ```php
  DB::transaction(function () use (&$order) {
      $order = $this->createOrder();
  });
  // Outside transaction — safe to fail independently
  NewOrderNotification::dispatch($order);
  ```

**Warning signs:**
- `Mail::send()` or notification dispatch inside `DB::transaction()`
- Try/catch inside a transaction that catches `\Exception` broadly

**Phase to address:** Phase 2 (order creation + notifications).

---

### Pitfall 5.5: Eloquent `firstOrCreate` and `updateOrCreate` Are Not Atomic

**What goes wrong:** `Product::firstOrCreate(['sku' => $sku])` looks atomic but is not. Under concurrent requests: both check for existence (both find nothing), both attempt to insert. One succeeds, one throws a unique constraint violation.

**Consequences:** Duplicate record creation under load. Exception not handled, request fails.

**Prevention:**
- Do not use `firstOrCreate` for records that must be unique under concurrency (orders, inventory adjustments)
- For truly atomic upserts: use database unique constraints + `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE` via raw queries or `upsert()`
- For idempotency key storage: use a unique index on `key` and let the database enforce uniqueness

**Warning signs:**
- `firstOrCreate` used in order creation or inventory operations
- No unique index on columns that should be globally unique

**Phase to address:** Phase 2 (order and idempotency key management).

---

## Phase-Specific Warning Summary

| Phase | Topic | Likely Pitfall | Mitigation |
|-------|-------|---------------|------------|
| Phase 1 | Product model | INTEGER stock column breaks kg products | Use DECIMAL(10,3) + unit_type enum from the start |
| Phase 1 | Entity design | Eloquent model used as domain entity | Separate domain entity class + infrastructure model |
| Phase 1 | API foundation | No version prefix on routes | Add `/api/v1/` prefix in routes/api.php |
| Phase 1 | Auth | Sanctum tokens never expire | Set expiration in sanctum.php from day one |
| Phase 1 | Error handling | Multiple error response shapes | Define global error envelope in Handler.php |
| Phase 2 | Inventory lock | `lockForUpdate()` without `DB::transaction()` | Always wrap in transaction; test under concurrency |
| Phase 2 | Order creation | Duplicate orders from network retry | Implement Idempotency-Key header handling |
| Phase 2 | State machine | Direct status string assignment | Domain-enforced transition map; throw on invalid transitions |
| Phase 2 | Payment | `payment_status` mixed into `status` | Two separate fields: status + payment_status |
| Phase 2 | Notifications | Side effects inside DB transaction | Dispatch jobs/events after commit, not inside transaction |
| Phase 2 | Cart | Stock deducted on cart add | Cart is advisory; deduct only at order placement |
| Phase 3 | Dashboard | N+1 queries on order list | Enable preventLazyLoading() in dev; eager load all relations |
| Phase 3 | Reporting | Mixed unit totals (units + kg) | Always group reports by unit_type; never sum across types |

---

## Sources

- [4 Ways To Prevent Race Conditions in Laravel — Backpack for Laravel](https://backpackforlaravel.com/articles/tutorials/4-ways-to-prevent-race-conditions-in-laravel)
- [Pessimistic vs Optimistic Locking in Laravel – Complete Guide (2025)](https://www.techquestworld.com/blog/laravel-pessimistic-vs-optimistic-locking)
- [How to Implement Clean Architecture with Laravel — DEV Community](https://dev.to/bdelespierre/how-to-implement-clean-architecture-with-laravel-2f2i)
- [Idempotency in Laravel 12 (2025) — Medium](https://medium.com/@aiman.asfia/idempotency-in-laravel-12-2025-the-complete-guide-that-will-save-you-from-double-charges-3-am-0135d93f6dea)
- [Common Pitfalls in RESTful API Design — Zuplo (2025)](https://zuplo.com/blog/2025/03/12/common-pitfalls-in-restful-api-design)
- [Managing Inventory Reservation in SAGA Pattern — DEV Community](https://dev.to/jackynote/managing-inventory-reservation-in-saga-pattern-for-e-commerce-systems-2d14)
- [Magento 2 Inventory Reservation with Order Status](https://www.mgt-commerce.com/blog/magento-2-inventory-reservation/)
- [Laravel Eloquent Performance: Fix N+1 — Lexo.ch (2025)](https://www.lexo.ch/blog/2025/08/avoiding-n1-queries-and-query-bloat-in-laravel-master-eloquent-performance-optimization/)
- [Laravel API Security 2025: Sanctum vs Passport](https://onecodesoft.com/blogs/laravel-api-security-2025-sanctum-vs-passport-guide)
- [State Machines Best Practices — commercetools](https://docs.commercetools.com/learning-model-your-business-structure/state-machines/states-and-best-practices)
- [Clean Architecture in Laravel — Level Up Coding (Feb 2026)](https://levelup.gitconnected.com/clean-architecture-in-laravel-a5d0a7c39a1f)
