# Research Summary — Ban Chim Bồ Câu

**Project:** Laravel e-commerce backend for poultry/pet sales (family business, Vietnam)
**Synthesized:** 2026-03-28
**Source files:** STACK.md, FEATURES.md, ARCHITECTURE.md, PITFALLS.md, PROJECT.md

---

## Stack

PHP 8.3 + Laravel 12 on MySQL 8.0 is the correct and only stack to use — it matches PROJECT.md constraints, hits the performance sweet spot, and every ecosystem package targets this combination. Authentication is Laravel Sanctum (bundled, stateful for admin SPA, token-based for public API); do not use Passport or JWT. Key supporting libraries: `spatie/laravel-data` for DTOs, `spatie/laravel-query-builder` for filterable endpoints, `spatie/laravel-permission` for admin/guest roles, `infinitypaul/idempotency-laravel` for duplicate order prevention, Scribe for API docs, and Pest 3 as the test runner.

Queue driver is `database` (no Redis in v1 — zero ops overhead at family-business scale). Laravel Notifications handles new-order email alerts. PHPStan at level 6+ provides static analysis to validate Clean Architecture layer boundaries.

---

## Table Stakes

Must-have for the system to function as a business tool:

**Product catalog**
- Product CRUD: name, price, type (sống / thịt / gia cầm), images, Vietnamese description
- `unit_type` enum per product: `unit` (con) vs `kilogram` (kg) — drives all inventory and validation logic
- Category management (flat hierarchy, no tree needed)
- `is_active` toggle for seasonal or sold-out products

**Inventory**
- Real-time stock per product stored as `DECIMAL(10,3)` — never `INTEGER` (breaks kg products)
- Stock decrement inside `DB::transaction()` with `lockForUpdate()` on order placement — this is non-negotiable
- Stock release on cancellation in the same transaction as the status update
- Admin manual stock adjustment endpoint

**Cart and order placement**
- Token-based guest cart (advisory only — no stock deduction on cart add)
- Cart-to-order conversion with atomic inventory lock at checkout time
- Idempotency key on POST /orders (UUID from client, stored in DB) — prevents double-taps and Zalo retry duplicates
- Admin manual order entry endpoint (bypasses cart, same inventory lock applies)
- Delivery address fields; delivery method enum: nội tỉnh / ngoại tỉnh

**Order lifecycle**
- State machine with five states: `chờ xác nhận → xác nhận → đang giao → hoàn thành / hủy`
- Transition enforcement in domain layer — invalid transitions throw, never silently succeed
- Two separate fields: `status` (fulfilment lifecycle) and `payment_status` (unpaid / payment_pending / paid)

**Payment**
- COD and bank transfer (static QR) — no payment gateway in v1
- Admin endpoint to manually confirm payment receipt

**Notifications**
- New order email notification to admin (dispatched via queued job after transaction commits — never inside the transaction)

**Admin operations**
- Sanctum-protected admin routes (token expiry configured — not null)
- Dashboard: pending order count, order list with status/date filters
- API versioned at `/api/v1/` from day one

**Technical non-negotiables**
- Consistent JSON error envelope across all endpoints (define in Handler.php in Phase 1)
- Vietnamese validation messages, status labels, and notification text
- API documentation via Scribe (auto-generated from routes + resources)
- Concurrent oversell test and duplicate order idempotency test must pass before shipping

---

## Architecture

Clean Architecture with four explicit layers (Domain / Application / Infrastructure / Presentation), with dependency arrows pointing inward toward the Domain. Domain entities are plain PHP objects with zero framework dependency — Eloquent lives only in the Infrastructure layer, mapped to domain entities via explicit Mapper classes. Controllers call Actions only; Actions accept DTOs (spatie/laravel-data) and depend on repository interfaces defined in the Domain layer; Infrastructure implements those interfaces.

The critical data flow for order placement: IdempotencyCheck middleware → FormRequest validation → OrderController → PlaceOrderAction (opens DB transaction, `lockForUpdate()` on all product rows sorted by ID, checks stock, decrements, creates order, commits) → OrderPlaced event dispatched after commit → queued NewOrderNotification. Stock deduction is synchronous inside the transaction, never in a listener or queued job.

Order status transitions are enforced inside the `Order` domain entity via a `transitionTo()` method with an explicit allowed-transitions map. `spatie/laravel-model-states` mirrors this declaratively on the Eloquent model layer. Cart is a thin stateless layer with no domain entity — it delegates to PlaceOrderAction at checkout. Repository interfaces live in `Domain/`, not `Infrastructure/` (common mistake that reverses dependency direction).

---

## Watch Out For

1. **Eloquent leaking into the Domain layer** — Domain entities must be plain PHP objects; if a domain file contains `extends Model`, the architecture is broken. Establish the entity/Eloquent model split in Phase 1 — retrofitting later touches every layer.

2. **`lockForUpdate()` outside a transaction** — The lock has no effect without `DB::transaction()`. Stock check and stock decrement must happen inside the same transaction; checking outside and decrementing inside creates a race window. Also: lock all products in a single `whereIn()->lockForUpdate()->get()` call sorted by ascending ID to prevent deadlocks on multi-product orders.

3. **Stock deducted on cart add instead of order placement** — Cart is advisory only. Deducting on cart add allows a single user with 10 tabs to exhaust all stock. Deduct only inside the PlaceOrderAction transaction.

4. **Side effects inside the DB transaction** — Notification dispatch, mail sends, and webhook calls inside `DB::transaction()` create silent failure modes. Dispatch queued jobs after the transaction commits (use `DB::afterCommit()` or dispatch outside the transaction block). Swallowing exceptions inside a transaction is the most dangerous variant.

5. **Payment status mixed into order status** — Adding "paid" as an order status value poisons the fulfilment state machine. Maintain `status` and `payment_status` as separate columns from the initial migration. COD stays `unpaid` until mom marks it paid after delivery; bank transfer moves to `payment_pending` when customer claims transfer, then `paid` when confirmed.

---

## Build Order

Dependencies are strict — each phase requires the previous to be complete.

1. **Foundation** — Domain entities and value objects (Product, Order, OrderItem, StockQuantity, Money, OrderStatus enums); repository interfaces in `Domain/`; database migrations with `DECIMAL(10,3)` stock, `unit_type` enum, separate `status` and `payment_status` columns, `/api/v1/` route prefix; global JSON error envelope in Handler.php; Sanctum with token expiry configured; PHPStan + Pest scaffolding. Nothing else can be built correctly without this layer being right.

2. **Infrastructure wiring** — Eloquent models and Mapper classes; Eloquent repository implementations; RepositoryServiceProvider bindings; `spatie/laravel-permission` roles (admin / guest). Enable `Model::preventLazyLoading()` in dev.

3. **Product domain** — CreateProductAction, UpdateProductAction, UpdateStockAction (with lockForUpdate); Product CRUD API (controllers, FormRequests, API Resources); category endpoints; image upload.

4. **Order domain** — PlaceOrderAction with pessimistic locking (the hardest part); idempotency middleware; order state machine actions (Confirm, Deliver, Complete, Cancel — with stock release on cancel in the same transaction); order status API for admin; OrderPlaced event and queued NewOrderNotification.

5. **Cart and customer-facing API** — Token-based guest cart (no stock deduction on add); cart-to-order checkout delegating to PlaceOrderAction; admin manual order entry endpoint (same Action, different controller and DTO).

6. **Reporting and polish** — Admin order dashboard with status/date filters; order search by customer phone/name; payment confirmation endpoint; Scribe API documentation generation; stock low-level alert (v2 candidate).

---

## Open Questions

These require decisions before or during the relevant phase:

1. **Cart persistence backend** — Database table vs Laravel cache (Redis not available in v1). Database cart is simpler and survives restarts; cache cart is faster but ephemeral. Decision needed before Phase 5.

2. **Stock reservation model** — Simple deduction-on-placement (simple, some false "out of stock" during peak) vs two-column reservation (`stock_total` + `stock_reserved`, more correct but more logic). PITFALLS.md recommends the two-column approach for correctness; PROJECT.md is silent on this. Decision needed before Phase 4.

3. **Idempotency package evaluation** — `infinitypaul/idempotency-laravel` was released mid-2025 with limited community history. Evaluate at Phase 4 implementation time; fall back to hand-rolled idempotency key table if the package has gaps.

4. **Customer notification channel** — Email to customer on status change requires an email address at order time (currently only phone is captured). SMS requires an external provider (Twilio, ESMS.vn). Decide scope before Phase 4; email is the safer v1 choice if customers provide it, otherwise defer customer notifications to v2.

5. **Image storage** — Local disk (simple, no ops) vs S3-compatible storage (survives server replacement). Decision needed before Phase 3; `filesystems.php` must be configured consistently from the start.

6. **Integer vs UUID primary keys on public endpoints** — Integer order IDs are enumerable (customer can guess adjacent order IDs). UUIDs prevent this but add migration complexity. Low risk for a family business but worth a one-time decision before Phase 4.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Core stack (PHP 8.3, Laravel 12, MySQL 8.0) | HIGH | Verified against official docs and benchmark data |
| Sanctum auth approach | HIGH | Official docs + community consensus; PROJECT.md confirms |
| Oversell prevention (lockForUpdate in transaction) | HIGH | Multiple independent sources; verified against Laravel 12.x docs |
| Idempotency pattern | HIGH | Standard pattern; package choice is MEDIUM pending evaluation |
| Clean Architecture layer boundaries | HIGH | Well-documented; multiple 2025 sources confirm the approach |
| Feature scope (table stakes vs anti-features) | HIGH | Confirmed against PROJECT.md + Vietnam market data |
| Payment methods (COD + bank transfer) | HIGH | Matches Vietnam e-commerce data (COD 35-67% of transactions) |
| Domain-specific differentiators (Zalo intake, livestock types) | MEDIUM | Derived from PROJECT.md context; not externally verified against competitors |
| Queue database driver at scale | HIGH | Appropriate for tens of orders/day; well-documented |

**Overall confidence: HIGH** — Core decisions are well-grounded. The two MEDIUM items (differentiators, idempotency package) are low-risk and can be validated during implementation.
