# Roadmap: Ban Chim Bồ Câu

**Milestone:** v1.0 — Backend API hoàn chỉnh cho hệ thống bán gia cầm của mẹ
**Granularity:** Coarse
**Created:** 2026-03-28

---

## Phases

- [ ] **Phase 1: Foundation** — Clean Architecture skeleton, database migrations, Sanctum auth, global JSON error envelope, PHPStan + Pest scaffolding
- [ ] **Phase 2: Product & Inventory** — Full product catalog CRUD with category management, mixed-unit inventory with admin stock adjustment and audit log
- [ ] **Phase 3: Orders, Cart & Payments** — Atomic order placement with pessimistic locking, idempotency, cart, order state machine, payment tracking, delivery fields, new-order email notification
- [ ] **Phase 4: Admin Operations & Docs** — Admin dashboard, order filtering and search, payment confirmation, Scribe API documentation

---

## Phase Details

### Phase 1: Foundation

**Goal:** Developers can run the project with a working Clean Architecture skeleton, admin can authenticate via Sanctum, and all API responses follow a consistent JSON error envelope.
**Depends on:** Nothing (first phase)
**Requirements:** AUTH-01, AUTH-02, AUTH-03, AUTH-04, TECH-01, TECH-02, TECH-03, TECH-04, TECH-06, TECH-07

**Success Criteria** (what must be TRUE):
  1. `POST /api/v1/admin/login` returns a Sanctum token; the token expires after a configured duration and is invalidated by `POST /api/v1/admin/logout`
  2. Any invalid request to any endpoint returns a JSON body with the shape `{ code, message, errors }` — never an HTML error page or unstructured JSON
  3. All validation error messages and status labels are in Vietnamese (e.g., "Trường này là bắt buộc")
  4. `php artisan test` runs with Pest 4 and the test suite passes; `php artisan analyse` (PHPStan level 6) passes with zero Domain-layer Laravel imports flagged
  5. Guest requests to public endpoints do not require authentication; admin routes return 401 without a valid token

**Plans:** 2 plans

Plans:
- [ ] 01-01-PLAN.md — Project setup, Clean Architecture skeleton, PHPStan config, Wave 0 test stubs
- [ ] 01-02-PLAN.md — Sanctum auth (login/logout), JSON error envelope, Vietnamese localization, real tests

---

### Phase 2: Product & Inventory

**Goal:** Admin can manage the full product catalog and inventory levels; customers can browse products; inventory supports both per-unit (con) and per-weight (kg) products.
**Depends on:** Phase 1
**Requirements:** PROD-01, PROD-02, PROD-03, PROD-04, PROD-05, INVT-01, INVT-02, INVT-03, INVT-04

**Success Criteria** (what must be TRUE):
  1. Admin can create, update, and delete a product with Vietnamese name, price, description, image, `unit_type` (con / kg), and category — and the product appears or disappears from the public listing accordingly
  2. Admin can toggle `is_active` on a product; inactive products are hidden from the public product list
  3. Stock is stored as `DECIMAL(10,3)` — a product can have `1.500` kg stock, and the API rejects any adjustment that would bring stock below zero
  4. Admin can record a manual stock adjustment (nhập thêm hàng, kiểm kê) and view the full adjustment history for any product showing who changed what and when
  5. Customer can list all active products and view the detail of a single product without authentication

**Plans:** TBD
**UI hint**: no

---

### Phase 3: Orders, Cart & Payments

**Goal:** Customers can build a cart and place orders without overselling; admin can enter manual orders; orders follow a strict state machine; payment status is tracked independently; admin is notified by email on every new order.
**Depends on:** Phase 2
**Requirements:** CART-01, CART-02, CART-03, CART-04, ORDR-01, ORDR-02, ORDR-03, ORDR-04, ORDR-05, ORDR-06, ORDR-07, PAYM-01, PAYM-02, PAYM-03, PAYM-04, DELV-01, DELV-02, NOTI-01, NOTI-02, TECH-05

**Success Criteria** (what must be TRUE):
  1. A customer (no account) can add products to a cart using a session token, update quantities, and remove items — stock is NOT decremented at this point
  2. When a customer submits the cart as an order, stock is checked and decremented atomically inside a single `DB::transaction()` with `lockForUpdate()`; if stock is insufficient the order is rejected with a Vietnamese error message
  3. Sending the same order request twice with the same idempotency key creates exactly one order — the second call returns the existing order without creating a duplicate or decrementing stock twice
  4. Admin can create an order manually (for Zalo/phone customers) using the same atomic lock mechanism — no code path bypasses the inventory lock
  5. An order transitions correctly through `chờ xác nhận → xác nhận → đang giao → hoàn thành` or `hủy`; invalid transitions (e.g., jumping from `chờ xác nhận` to `hoàn thành`) are rejected with an error; cancellation restores stock in the same transaction
  6. `payment_status` is a separate field from `order status`; COD orders start as `chưa thanh toán`; bank-transfer orders move to `chờ xác nhận` then `đã thanh toán` when admin confirms
  7. Within seconds of a new order being placed, admin receives a Vietnamese email listing product names, quantities, and the delivery address — the email is dispatched via a queued job that fires after the transaction commits

**Plans:** TBD

---

### Phase 4: Admin Operations & Docs

**Goal:** Admin has a functional dashboard for daily order management and the API is fully documented for frontend integration.
**Depends on:** Phase 3
**Requirements:** ADMN-01, ADMN-02, ADMN-03, ADMN-04

**Success Criteria** (what must be TRUE):
  1. Admin can call a dashboard endpoint that returns the count of orders currently in `chờ xác nhận` status
  2. Admin can filter the order list by status and by date range, and search orders by customer name or phone number — all in a single paginated endpoint
  3. Admin can confirm payment receipt for any order; the `payment_status` updates to `đã thanh toán` and the response reflects the change immediately
  4. Running `php artisan scribe:generate` produces a browsable API reference that covers all v1 endpoints with accurate request/response examples

**Plans:** TBD
**UI hint**: no

---

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation | 0/2 | Planned | - |
| 2. Product & Inventory | 0/? | Not started | - |
| 3. Orders, Cart & Payments | 0/? | Not started | - |
| 4. Admin Operations & Docs | 0/? | Not started | - |

---

## Coverage

**v1 requirements:** 43 total
**Mapped:** 43/43

| Requirement | Phase |
|-------------|-------|
| AUTH-01 | Phase 1 |
| AUTH-02 | Phase 1 |
| AUTH-03 | Phase 1 |
| AUTH-04 | Phase 1 |
| PROD-01 | Phase 2 |
| PROD-02 | Phase 2 |
| PROD-03 | Phase 2 |
| PROD-04 | Phase 2 |
| PROD-05 | Phase 2 |
| INVT-01 | Phase 2 |
| INVT-02 | Phase 2 |
| INVT-03 | Phase 2 |
| INVT-04 | Phase 2 |
| CART-01 | Phase 3 |
| CART-02 | Phase 3 |
| CART-03 | Phase 3 |
| CART-04 | Phase 3 |
| ORDR-01 | Phase 3 |
| ORDR-02 | Phase 3 |
| ORDR-03 | Phase 3 |
| ORDR-04 | Phase 3 |
| ORDR-05 | Phase 3 |
| ORDR-06 | Phase 3 |
| ORDR-07 | Phase 3 |
| PAYM-01 | Phase 3 |
| PAYM-02 | Phase 3 |
| PAYM-03 | Phase 3 |
| PAYM-04 | Phase 3 |
| DELV-01 | Phase 3 |
| DELV-02 | Phase 3 |
| NOTI-01 | Phase 3 |
| NOTI-02 | Phase 3 |
| TECH-05 | Phase 3 |
| ADMN-01 | Phase 4 |
| ADMN-02 | Phase 4 |
| ADMN-03 | Phase 4 |
| ADMN-04 | Phase 4 |
| TECH-01 | Phase 1 |
| TECH-02 | Phase 1 |
| TECH-03 | Phase 1 |
| TECH-04 | Phase 1 |
| TECH-06 | Phase 1 |
| TECH-07 | Phase 1 |

---

*Roadmap created: 2026-03-28*
*Last updated: 2026-03-28 after Phase 1 planning*
