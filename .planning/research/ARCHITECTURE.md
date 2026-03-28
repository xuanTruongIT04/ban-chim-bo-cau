# Architecture Patterns

**Domain:** Laravel e-commerce backend — poultry/bird sales with inventory management
**Project:** Ban Chim Bo Cau
**Researched:** 2026-03-28
**Confidence:** HIGH (verified against Laravel official docs, multiple community sources)

---

## Recommended Architecture

Clean Architecture with four explicit layers. Laravel's framework code (routing, ORM, queues)
is an infrastructure detail — it serves the domain, it does not define it.

The central rule: **inner layers know nothing about outer layers.**

```
┌──────────────────────────────────────────────────────┐
│                 PRESENTATION LAYER                   │
│   Controllers, FormRequests, API Resources           │
│   Routes, Middleware                                 │
├──────────────────────────────────────────────────────┤
│                 APPLICATION LAYER                    │
│   Actions (Use Cases), DTOs, Events                  │
│   Application Service interfaces                     │
├──────────────────────────────────────────────────────┤
│                   DOMAIN LAYER                       │
│   Entities, Value Objects, Domain Events             │
│   Repository interfaces, Domain exceptions           │
├──────────────────────────────────────────────────────┤
│                INFRASTRUCTURE LAYER                  │
│   Eloquent models, Repository implementations        │
│   External services, Queue jobs, Notifications       │
└──────────────────────────────────────────────────────┘

Dependency direction: all arrows point INWARD (toward Domain)
```

---

## Folder / Namespace Organization

```
app/
├── Domain/                          # Pure business logic — no Laravel dependencies
│   ├── Product/
│   │   ├── Entities/
│   │   │   └── Product.php          # POPO: id, name, type, price, stock unit type
│   │   ├── ValueObjects/
│   │   │   ├── StockQuantity.php    # Wraps int/float, enforces non-negative
│   │   │   ├── StockUnit.php        # Enum: UNIT | KILOGRAM
│   │   │   └── Money.php            # Price with VND currency
│   │   ├── Exceptions/
│   │   │   └── InsufficientStockException.php
│   │   └── Repositories/
│   │       └── ProductRepositoryInterface.php
│   │
│   ├── Order/
│   │   ├── Entities/
│   │   │   ├── Order.php            # Aggregate root
│   │   │   └── OrderItem.php        # Line item with quantity snapshot
│   │   ├── ValueObjects/
│   │   │   ├── OrderStatus.php      # Enum: PENDING | CONFIRMED | DELIVERING | DONE | CANCELLED
│   │   │   ├── DeliveryType.php     # Enum: LOCAL | FREIGHT (xe khach)
│   │   │   └── PaymentMethod.php    # Enum: COD | BANK_TRANSFER
│   │   ├── Events/
│   │   │   ├── OrderPlaced.php      # Domain event — carries Order entity / ID
│   │   │   ├── OrderConfirmed.php
│   │   │   └── OrderCancelled.php
│   │   ├── Exceptions/
│   │   │   ├── OrderNotFoundException.php
│   │   │   └── InvalidOrderTransitionException.php
│   │   └── Repositories/
│   │       └── OrderRepositoryInterface.php
│   │
│   └── Shared/
│       └── ValueObjects/
│           └── Address.php          # Customer delivery address VO
│
├── Application/                     # Orchestrates domain + infrastructure via interfaces
│   ├── Product/
│   │   └── Actions/
│   │       ├── CreateProductAction.php
│   │       ├── UpdateProductAction.php
│   │       ├── UpdateStockAction.php    # Manual stock adjustment by admin
│   │       └── DeleteProductAction.php
│   │
│   ├── Order/
│   │   └── Actions/
│   │       ├── PlaceOrderAction.php     # Core use case — does inventory check + deduction
│   │       ├── ConfirmOrderAction.php
│   │       ├── DeliverOrderAction.php
│   │       ├── CompleteOrderAction.php
│   │       └── CancelOrderAction.php   # Restores stock if applicable
│   │
│   └── Shared/
│       └── DTOs/                        # Data Transfer Objects (plain PHP, no Eloquent)
│           ├── PlaceOrderDTO.php
│           ├── OrderItemDTO.php
│           └── CreateProductDTO.php
│
├── Infrastructure/                  # Laravel-specific implementations
│   ├── Persistence/
│   │   ├── Eloquent/
│   │   │   ├── Models/
│   │   │   │   ├── ProductModel.php     # Eloquent model (NOT the domain entity)
│   │   │   │   ├── OrderModel.php
│   │   │   │   ├── OrderItemModel.php
│   │   │   │   ├── CategoryModel.php
│   │   │   │   └── CustomerModel.php
│   │   │   └── Repositories/
│   │   │       ├── EloquentProductRepository.php
│   │   │       └── EloquentOrderRepository.php
│   │   └── Mappers/
│   │       ├── ProductMapper.php        # Eloquent model <-> Domain entity
│   │       └── OrderMapper.php
│   │
│   ├── Notifications/
│   │   └── NewOrderNotification.php    # Email / webhook on OrderPlaced
│   │
│   └── Providers/
│       └── RepositoryServiceProvider.php  # Binds interfaces to implementations
│
└── Presentation/                    # HTTP delivery mechanism
    ├── Http/
    │   ├── Controllers/
    │   │   ├── Api/
    │   │   │   ├── ProductController.php
    │   │   │   ├── CategoryController.php
    │   │   │   ├── CartController.php
    │   │   │   ├── OrderController.php
    │   │   │   └── Admin/
    │   │   │       ├── AdminOrderController.php
    │   │   │       ├── AdminProductController.php
    │   │   │       └── AdminInventoryController.php
    │   │   └── Auth/
    │   │       └── AuthController.php
    │   │
    │   ├── Requests/                    # FormRequest validation (Laravel-aware)
    │   │   ├── PlaceOrderRequest.php
    │   │   ├── CreateProductRequest.php
    │   │   └── UpdateStockRequest.php
    │   │
    │   └── Resources/                  # API Resources (transform Eloquent -> JSON)
    │       ├── ProductResource.php
    │       ├── OrderResource.php
    │       ├── OrderItemResource.php
    │       └── CategoryResource.php
    │
    └── routes/
        ├── api.php                     # Public + auth routes
        └── admin.php                   # Admin-only routes (Sanctum guarded)
```

---

## Component Boundaries

What each layer is allowed to know about:

| Layer | Can import | Cannot import |
|-------|-----------|---------------|
| Domain | Nothing (pure PHP) | Eloquent, Laravel facades, HTTP |
| Application | Domain | Eloquent, HTTP, Controllers |
| Infrastructure | Domain, Application interfaces, Eloquent | Controllers, FormRequests |
| Presentation | Application Actions (via DI), API Resources | Domain entities directly |

### Boundary rules in plain language

- **Controllers** call **Actions** only — never repositories, never Eloquent models directly.
- **Actions** accept **DTOs** as input, return domain entities or primitives.
- **Actions** depend on **Repository interfaces** (domain-defined), not Eloquent.
- **Repository implementations** (Infrastructure) convert between Eloquent models and domain entities via **Mappers**.
- **API Resources** (Presentation) receive Eloquent models or DTOs — they do NOT reach into domain internals.
- **Domain entities** are Plain Old PHP Objects (POPOs) — zero framework dependency.

---

## Data Flow: Key Operations

### 1. Place Order (most complex flow)

```
Client
  |
  | POST /api/orders  {items: [...], customer: {...}, idempotency_key: "uuid"}
  v
[Middleware: IdempotencyCheck] ── duplicate key? → return cached 200 response
  |
  v
PlaceOrderRequest (FormRequest)
  ── validates structure, required fields
  ── returns 422 with Vietnamese messages if invalid
  |
  v
OrderController::store()
  ── builds PlaceOrderDTO from validated request data
  ── calls PlaceOrderAction
  |
  v
PlaceOrderAction::handle(PlaceOrderDTO $dto)
  ── starts DB::transaction()
  |
  |── foreach item in $dto->items:
  |     ProductRepository::lockForUpdate(id)   ← acquires row-level lock
  |     check stock >= requested quantity
  |     if insufficient → throw InsufficientStockException (rolls back tx)
  |     ProductRepository::decrementStock(id, quantity)
  |
  |── OrderRepository::create(order data)
  |── dispatch(new OrderPlaced($order))        ← domain event
  |── commit transaction
  |
  v
OrderPlaced event dispatched
  |── [Queued] NewOrderNotification::send()    ← email/webhook to admin
  |── [Sync]   StoreIdempotencyResult::run()   ← cache response for key
  |
  v
OrderController returns OrderResource::make($order)
  |
  v
Client receives 201 { order_id, status, items, total, ... }
```

### 2. Check Stock / Product Detail

```
Client
  |
  | GET /api/products/{id}
  v
ProductController::show()
  ── ProductRepository::findById($id)
  ── maps Eloquent ProductModel → Product entity
  ── returns ProductResource::make($model)
  |
  v
Client receives { id, name, stock_quantity, stock_unit, price, ... }
```

### 3. Admin Manual Stock Update

```
Admin client
  |
  | PATCH /api/admin/inventory/{product_id}
  v
[Sanctum Auth middleware]
  |
  v
UpdateStockRequest (validate: quantity, reason)
  |
  v
AdminInventoryController::update()
  ── calls UpdateStockAction
  |
  v
UpdateStockAction::handle(int $productId, float $newQty, string $reason)
  ── DB::transaction()
  ── ProductRepository::lockForUpdate($productId)
  ── ProductRepository::setStock($productId, $newQty)
  ── StockAdjustmentRepository::log($productId, $oldQty, $newQty, $reason)
  ── commit
  |
  v
Returns updated ProductResource
```

### 4. Order Status Transition

```
Admin client
  |
  | PATCH /api/admin/orders/{id}/status  { status: "confirmed" }
  v
[Sanctum Auth]
  |
  v
ConfirmOrderAction::handle(Order $order)
  ── validates: current status must be PENDING
  ── throws InvalidOrderTransitionException if invalid
  ── OrderRepository::updateStatus($order->id, OrderStatus::CONFIRMED)
  ── dispatch(new OrderConfirmed($order))
  |
  v
OrderConfirmed event
  |── [Queued] NotifyCustomer (future: SMS/Zalo)

```

---

## Oversell Prevention — Architecture-Level Design

This is the most critical correctness constraint in the system. It is handled at three levels:

### Level 1: Database lock (primary guard)

```php
// Inside PlaceOrderAction, within DB::transaction()
$product = ProductModel::where('id', $productId)
    ->lockForUpdate()      // SELECT ... FOR UPDATE
    ->firstOrFail();

if ($product->stock < $requestedQty) {
    throw new InsufficientStockException($product, $requestedQty);
}

$product->decrement('stock', $requestedQty);
```

`lockForUpdate()` means: while this transaction holds the lock, no other transaction
can read this row with a lock, or write to it. Concurrent orders for the same product
queue up and execute serially. The second transaction will see the updated stock.

**Requires:** InnoDB (MySQL) or any PostgreSQL table. Row-level locking, not table-level.

### Level 2: Check-then-act inside the same transaction

The stock check and stock decrement must happen inside the same transaction. Never check
outside and then decrement inside — that window creates a race condition.

```
WRONG:
  $stock = getStock($id)          ← outside transaction
  if ($stock >= qty):
    DB::transaction(decrement)    ← race window here

CORRECT:
  DB::transaction:
    $stock = lockForUpdate(id)    ← check AND lock in same tx
    if ($stock >= qty):
      decrement()
```

### Level 3: Idempotency key (prevents duplicate order submission)

A client-generated UUID sent as `Idempotency-Key` header (or request field). The server:

1. Checks if key exists in `idempotency_keys` table (indexed).
2. If found and status = `completed` → return the original response, do not re-process.
3. If found and status = `processing` → return 409 (request in flight).
4. If not found → process, store key + response on success.

This blocks double-taps (network retry, user double-click) at the API boundary before
any business logic runs.

### Mixed Stock Units (unit vs kg)

Products have a `stock_unit` field: `unit` (bán theo con) or `kilogram` (bán theo kg).

The `StockQuantity` value object wraps a `float` to handle both:
- Unit products: integer-like quantities (1, 2, 10 con)
- Weight products: decimal quantities (0.5 kg, 1.25 kg)

The domain entity enforces: `StockQuantity` cannot be negative. The `Product` entity
exposes `canFulfill(float $quantity): bool` as a pure domain method, independent of
database concerns. The repository uses `float` columns in MySQL for both types.

---

## Build Order (Phase Dependencies)

Components must be built in this order because each depends on the previous:

```
Phase 1: Foundation
  ├── Domain entities + value objects (no dependencies)
  ├── Repository interfaces (no dependencies)
  ├── Database migrations (schema first)
  └── Eloquent models + Mappers (depends on migrations)

Phase 2: Infrastructure
  ├── Eloquent repository implementations (depends on Phase 1)
  ├── RepositoryServiceProvider bindings
  └── Sanctum auth setup

Phase 3: Application — Product Domain
  ├── CreateProductAction / UpdateProductAction
  ├── UpdateStockAction (with lockForUpdate)
  └── Product CRUD API (Controllers + Resources + Requests)

Phase 4: Application — Order Domain
  ├── PlaceOrderAction (depends on ProductRepository + OrderRepository)
  ├── Order status transition Actions
  ├── OrderPlaced domain event + NewOrderNotification listener
  ├── Idempotency middleware
  └── Order API endpoints (customer + admin)

Phase 5: Cart
  ├── Session/token cart (stateless, no DB dependency)
  └── CartController → feeds into PlaceOrderAction

Phase 6: Reporting + Polish
  ├── Order dashboard / filter endpoints
  └── API documentation (Scribe)
```

**Why this order:**
- Domain + interfaces first: Actions can be written and tested against interfaces before
  infrastructure exists (use in-memory fakes in tests).
- Products before Orders: Orders depend on product stock — repository must exist first.
- Cart last: It is a thin stateless layer that delegates to PlaceOrderAction. No Cart
  domain entity needed for v1 — session storage is sufficient.

---

## State Machine: Order Status

Valid transitions only (enforced in domain layer):

```
PENDING ──── confirm ────► CONFIRMED ──── start_delivery ────► DELIVERING
   │                           │                                     │
   └── cancel ──► CANCELLED    └── cancel ──► CANCELLED     complete ▼
                                                              COMPLETED

No transitions out of CANCELLED or COMPLETED (terminal states)
```

Implemented via `OrderStatus` enum + transition guard in `Order` entity:

```php
// Domain/Order/Entities/Order.php
public function transitionTo(OrderStatus $newStatus): void
{
    if (!$this->status->canTransitionTo($newStatus)) {
        throw new InvalidOrderTransitionException($this->status, $newStatus);
    }
    $this->status = $newStatus;
}
```

This keeps transition rules in the domain, not scattered across controllers or actions.

Recommendation: Use `spatie/laravel-model-states` for the Eloquent model layer to
mirror these rules declaratively, but keep the canonical transition logic in the domain
entity. The Spatie package handles serialization and provides hooks for events.

---

## Scalability Considerations

This is a family business at small scale. The architecture is intentionally not over-engineered,
but it is correct by construction.

| Concern | Current approach | If scale grows |
|---------|-----------------|----------------|
| Oversell | DB transaction + lockForUpdate (row lock) | Same — InnoDB row locks scale to hundreds of concurrent orders |
| Duplicate orders | Idempotency key table | Add Redis cache layer in front of DB check |
| Notifications | Synchronous queued listener | Already queued — add Redis/SQS driver |
| Reporting | Direct DB queries with indexes | Add read replica, cache dashboard results |
| Traffic | Single server | Laravel Octane for long-running process, or horizontal scale behind load balancer |

At household-business scale (tens of orders/day), the pessimistic locking approach has
no meaningful throughput impact. A SELECT FOR UPDATE typically holds for < 10ms.

---

## Anti-Patterns to Avoid

### Fat Controller
**What:** Business logic (stock check, order creation) inside controllers.
**Why bad:** Untestable, not reusable from CLI/jobs, violates SRP.
**Instead:** Controller calls Action, Action contains logic.

### Eloquent in Domain
**What:** Domain entity extends `Model` or uses `Eloquent` relationships.
**Why bad:** Domain becomes tightly coupled to ORM — can't unit test without DB.
**Instead:** Domain entity is POPO. Eloquent lives only in Infrastructure.

### Check outside transaction
**What:** Stock check before starting transaction, decrement inside transaction.
**Why bad:** Creates race window — concurrent request can pass the check simultaneously.
**Instead:** Lock row and check inside the same transaction (see Oversell section above).

### Listener as business logic
**What:** Inventory deduction triggered by event listener on OrderPlaced.
**Why bad:** If listener fails, stock is not decremented — order and stock are inconsistent.
**Instead:** Stock deduction happens synchronously inside `PlaceOrderAction` before the
event is dispatched. Events are for side effects (notifications, logging), not core
business mutations.

### Single `orders` endpoint for both admin manual-entry and customer self-service
**What:** Same controller method handles both flows.
**Why bad:** Different validation, different auth, different DTO shapes.
**Instead:** Separate controller methods or separate controllers (AdminOrderController vs
OrderController), both calling the same underlying Action.

---

## Sources

- [Clean Architecture with Laravel — DEV Community](https://dev.to/bdelespierre/how-to-implement-clean-architecture-with-laravel-2f2i)
- [Clean Code Architecture in Laravel: A Practical Guide — DEV Community](https://dev.to/arafatweb/clean-code-architecture-in-laravel-a-practical-guide-ho2)
- [Preventing Data Races with Pessimistic Locking in Laravel](https://www.harrisrafto.eu/preventing-data-races-with-pessimistic-locking-in-laravel/)
- [Managing Data Races with Pessimistic Locking — Laravel News](https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel)
- [Action Pattern in Laravel: Concept, Benefits, Best Practices](https://nabilhassen.com/action-pattern-in-laravel-concept-benefits-best-practices)
- [Eloquent: API Resources — Laravel 12.x Official Docs](https://laravel.com/docs/12.x/eloquent-resources)
- [Events — Laravel 12.x Official Docs](https://laravel.com/docs/12.x/events)
- [Idempotency in Laravel: How to Avoid Duplicates](https://antoniocortes.com/en/2025/06/30/idempotency-in-laravel-how-to-avoid-duplicates-in-your-apis-with-elegance/)
- [Spatie Laravel Model States](https://spatie.be/docs/laravel-model-states/v2/01-introduction)
- [Domain-Driven Design with Laravel — States and Transitions](https://martinjoo.dev/domain-driven-design-with-laravel-states-and-transitions)
- [Pessimistic vs Optimistic Locking in Laravel — Complete Guide 2025](https://www.techquestworld.com/blog/laravel-pessimistic-vs-optimistic-locking)
