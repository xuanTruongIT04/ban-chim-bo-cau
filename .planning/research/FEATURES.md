# Feature Landscape

**Domain:** Small e-commerce backend — live poultry and meat product sales (family business, Vietnam)
**Project:** Ban Chim Bo Cau — Laravel Backend
**Researched:** 2026-03-28
**Overall confidence:** HIGH (confirmed against established e-commerce patterns + project-specific requirements)

---

## Table Stakes

Features the system cannot function without. Missing any of these makes the product unusable as a business tool.

### Product Catalog

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Product CRUD (name, price, description, images) | Baseline — nothing works without products | Low | Vietnamese-language descriptions required |
| Product type flag (sống / thịt / gia cầm) | Orders and inventory behave differently per type | Low | Enum field on Product; not a full variant system |
| Category management | Navigation, filtering — customers need to browse by type | Low | Flat or 1-level hierarchy is sufficient; recursive tree is overkill here |
| Product images (upload + serve) | Customers buying live animals want photos; without images, trust is low | Medium | Store on disk or S3; return URLs in API. Resizing as job, not blocking upload |
| Product active/inactive toggle | Seasonal or sold-out products must be hideable without deletion | Low | Boolean `is_active` flag; soft-delete is overkill |
| Per-product unit type (con / kg) | Core business reality: bồ câu sold by unit, thịt sold by kg | Low | Enum `unit_type` on Product; drives how quantity is displayed and validated |

### Inventory Management

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Real-time stock level per product | Core value of the system — cannot oversell | Low | Single `stock_quantity` integer (for unit products) or decimal (for kg products) |
| Mixed unit support: unit (con) vs weight (kg) | Business reality — not all products use same unit | Medium | `unit_type` drives how `quantity` is stored and decremented |
| Stock decrement on order confirmation | Prevent oversell between order creation and confirmation | Medium | Must happen inside DB transaction; see Pitfalls |
| Stock increment on order cancellation | Returns inventory to available when order is cancelled | Low | Triggered by status transition to "hủy" |
| Stock reservation on cart checkout | Prevent two customers buying last unit simultaneously | High | Pessimistic lock: `SELECT FOR UPDATE` inside transaction; this is the hardest part of the system |
| Admin stock adjustment (manual correction) | Mẹ receives new stock — she needs to update system | Low | Simple endpoint: set absolute quantity or delta |

### Cart and Order Placement

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Customer cart (session/token-based) | Online customers need to accumulate items before placing order | Medium | Cart stored in DB or cache (Redis); token-keyed, not tied to user account |
| Add/remove/update cart items | Baseline cart operations | Low | Each mutation re-validates stock availability |
| Cart-to-order conversion | Checkout flow converts cart to Order entity | Medium | Must validate stock atomically at conversion time using DB transaction |
| Idempotency key on order creation | Prevents duplicate orders from double-taps / retries | Medium | Client sends UUID with request; server de-dupes within 24h window using DB unique key |
| Admin manual order entry | Mẹ takes orders via Zalo/phone — must be enterable in system | Medium | Admin API endpoint: create order with customer info + line items, bypasses cart; same stock lock applies |
| Delivery address capture | Required for fulfillment, especially ngoại tỉnh orders | Low | Street, ward, district, province fields; no geocoding needed |
| Delivery method selection (nội tỉnh / ngoại tỉnh) | Two different fulfillment workflows; affects logistics | Low | Enum field on Order |

### Order Lifecycle

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Order status machine | System unusable without defined lifecycle | Low | States: chờ xác nhận → xác nhận → đang giao → hoàn thành / hủy |
| Status transition enforcement | Prevent invalid transitions (e.g. completed → pending) | Low | Validate allowed next states in application layer |
| Order detail view (admin) | Mẹ needs to see what was ordered and by whom | Low | Includes line items, customer contact, delivery info, payment method |
| Order list with filters | Mẹ needs to work through order queue | Low | Filter by status, date range; pagination required |
| Order history per customer | Repeat customer recognition; helps mẹ remember relationships | Low | Filter orders by customer phone/name |

### Payment

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| COD (tiền mặt khi nhận hàng) | Dominant in Vietnam — still ~35-67% of transactions | Low | Payment method flag on Order; no external integration |
| Bank transfer with static QR | Growing in Vietnam; mẹ already uses this informally | Low | Store bank account info in config; generate or store static QR image per account |
| Manual payment confirmation by admin | Mẹ confirms transfer received before shipping | Low | Admin sets `payment_status` to "đã thanh toán" on order |
| Payment method field on order | Required for fulfillment decisions | Low | Enum: COD / bank_transfer |
| Payment status tracking | Know which orders are paid vs unpaid | Low | Separate `payment_status` from `order_status` |

### Notifications

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| New order notification to admin | Without this, mẹ won't know orders exist if not logged in | Medium | Minimum: email notification. Webhook optional for future bot integration |
| Order status change notification to customer | Customer needs to know when order is confirmed, shipped | Medium | Email or SMS; SMS needs external provider, email is simpler for v1 |

### Admin Operations

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Admin authentication (Sanctum) | Protect all admin endpoints | Low | Token-based; separate from public API |
| Admin dashboard: pending orders count | First screen mẹ sees — must answer "what needs action?" | Low | Simple aggregate query; not a complex analytics dashboard |
| Order history log (audit trail) | Know who changed what status and when | Low | Created_at + updated_at on status transitions is sufficient |

### Technical Non-Negotiables

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| API documentation | Frontend dev (potentially someone else) must integrate | Medium | Scribe for Laravel is the standard choice; auto-generated from code |
| Vietnamese error messages and labels | Mẹ is the admin user — she reads Vietnamese | Low | Validation messages, status labels, notification text all in Vietnamese |
| Oversell prevention under concurrency | Core business value stated in PROJECT.md | High | DB transaction + `lockForUpdate()` on inventory row; tested under simulated concurrency |

---

## Differentiators

Features that match this specific business's workflow. Not universally expected, but make the system meaningfully better than a generic solution.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Zalo order intake flow (admin entry with customer phone) | Mẹ's primary order channel is Zalo; system bridges offline → online | Medium | Admin order entry endpoint captures customer phone as identifier; no Zalo API integration in v1 |
| Delivery method distinction (nội tỉnh tự giao / ngoại tỉnh xe khách) | This business has exactly two delivery modes — tracking which is which reduces errors | Low | Enum on Order; can add carrier name field for xe khách orders |
| Per-order delivery notes field | Live animals need special instructions (packaging, timing, care notes) | Low | Free text field on Order; helps mẹ remember special requests |
| Livestock-specific product type labels | "Sống" vs "thịt" is a meaningful distinction for pricing and handling | Low | Pre-seeded enum types; displayed in Vietnamese |
| Stock low-level alert | Mẹ should know before she runs out, not after oversell attempt | Medium | Threshold per product; alert when stock falls below threshold. Delivered via same notification channel as new orders |
| Weight-based quantity validation | Prevent orders for 0.1kg when minimum batch is 0.5kg | Low | `min_quantity` and `quantity_step` fields on Product; validated at cart add |
| Order search by customer name or phone | Mẹ will often look up "the lady who called this morning" | Low | Simple LIKE search on customer name / phone on orders table |

---

## Anti-Features

Things to deliberately NOT build in v1. Each has a reason.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Online payment gateway integration (MoMo, VNPay, ZaloPay) | Adds integration complexity, compliance overhead, testing burden; COD + bank transfer covers mẹ's actual workflow | Manual bank transfer confirmation in v1; add gateway in v2 only if customers demand it |
| Customer account registration / login | Adds auth flow, password reset, email verification complexity; this business is relationship-based, not account-based | Use phone number as customer identifier; no account required |
| Product variants / SKU matrix | Generic e-commerce products have color/size; bồ câu does not. A variant system adds DB complexity without business value | Use `unit_type` + `product_type` flags; variants are over-engineering for this domain |
| Coupon / discount code system | No evidence mẹ uses these; adds redemption logic, abuse prevention, expiry management | Not needed at this scale; if needed later, it is a contained addition |
| Customer reviews and ratings | Mẹ's customers are regulars who call her; public reviews are irrelevant | Out of scope permanently for a family business context |
| Wishlist / saved items | Session-based cart is sufficient; wishlists require customer accounts | Not applicable without accounts |
| Multi-vendor / marketplace features | Single seller only | Hard-coded single seller; no vendor abstraction layer |
| Loyalty points system | Overkill for hộ kinh doanh gia đình scale | Personal relationships replace this function |
| Real-time delivery tracking with GPS | Mẹ delivers herself locally, uses xe khách for inter-province; no fleet tracking system | Delivery method + notes field is sufficient; customer calls mẹ directly |
| Automated refund processing | Manual business; refunds are handled by phone | Admin can cancel order and update stock; no automated refund logic needed |
| Inventory forecasting / analytics | Small enough to manage by eye; analytics dashboards add build time without day-1 value | Simple order history export is enough for v1 |
| Product bundles / kits | No evidence of bundle sales; adds fulfillment complexity | Individual products only |
| Scheduled / recurring orders | Niche feature; not a pattern in this business | Out of scope; add only if a customer specifically requests it |
| i18n multi-language | Vietnamese only; no other languages needed | Hard-code Vietnamese; no translation layer overhead |
| Full audit log with diff tracking | Simple created_at/updated_at with who changed status is sufficient | Use Eloquent observer for status transition log only |

---

## Feature Dependencies

The build order is constrained by these dependency chains.

```
Products + Categories
    └─→ Inventory (requires products to exist)
        └─→ Cart (requires products + inventory)
            └─→ Order Creation (requires cart + inventory lock)
                └─→ Payment Tracking (requires order to exist)
                └─→ Delivery Tracking (requires order to exist)
                └─→ Notifications (requires order to exist)
                └─→ Admin Order Entry (parallel path: no cart, direct to order + inventory lock)

Admin Auth (Sanctum)
    └─→ All Admin Endpoints (protected by auth)

Stock Reservation (pessimistic lock)
    └─→ Cart checkout
    └─→ Admin order entry
    (both paths converge on the same inventory lock mechanism)

Order Status Machine
    └─→ Stock increment on cancel
    └─→ Notifications on transition
    └─→ Payment status update on confirmation
```

Critical path (cannot build later steps before earlier steps):
1. Products schema
2. Inventory model with unit_type
3. Auth (Sanctum)
4. Cart with stock validation
5. Order creation with atomic inventory lock (the hardest part)
6. Order status transitions
7. Payment status tracking
8. Notifications
9. Admin dashboard and filters

---

## MVP Recommendation

**Build in v1 — the system is unusable without these:**

1. Product CRUD with category, unit_type, images
2. Inventory management with mixed unit support
3. Cart (token-based, no account required)
4. Order creation with pessimistic locking anti-oversell
5. Admin manual order entry (Zalo/phone order intake)
6. Idempotency key on order placement endpoint
7. Order status machine (5 states)
8. Payment method + payment status fields (COD / bank transfer)
9. Admin payment confirmation endpoint
10. Delivery method field (nội tỉnh / ngoại tỉnh)
11. New order email notification to admin
12. Admin order dashboard with status filters
13. Admin authentication (Sanctum)
14. API documentation (Scribe)

**Defer to v2 (validate demand first):**

- Stock low-level alerts — useful, but mẹ can check manually for now
- Order status notifications to customer — adds external provider complexity; verify if customers expect this
- Delivery notes / carrier name for xe khách — add when mẹ requests it
- Order search by customer name/phone — can start with basic date + status filter

**Defer permanently unless explicitly requested:**

- Payment gateway integration (MoMo, VNPay)
- Customer accounts
- Product variants
- Any feature in the Anti-Features section

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Table stakes features | HIGH | Confirmed against PROJECT.md requirements + standard e-commerce patterns |
| Payment method selection (COD + bank transfer) | HIGH | Matches Vietnam market data (COD still 35-67% of transactions as of 2025) |
| Oversell prevention mechanism | HIGH | Laravel `lockForUpdate()` is the correct and verified approach for this pattern |
| Idempotency for duplicate order prevention | HIGH | Standard pattern, multiple sources confirm; UUID key in DB is the right implementation |
| Differentiators (Zalo intake, livestock types) | MEDIUM | Derived from PROJECT.md context + domain reasoning; not externally verified against competitors |
| Anti-feature list | MEDIUM | Based on scope reasoning and PROJECT.md Out of Scope section; reasonable but not empirically tested |

---

## Sources

- [Pessimistic & Optimistic Locking in Laravel (DEV Community)](https://dev.to/tegos/pessimistic-optimistic-locking-in-laravel-23dk)
- [Managing Data Races with Pessimistic Locking in Laravel (Laravel News)](https://laravel-news.com/managing-data-races-with-pessimistic-locking-in-laravel)
- [4 Ways To Prevent Race Conditions in Laravel (Backpack for Laravel)](https://backpackforlaravel.com/articles/tutorials/4-ways-to-prevent-race-conditions-in-laravel)
- [Idempotency Keys in REST APIs (Zuplo)](https://zuplo.com/learning-center/implementing-idempotency-keys-in-rest-apis-a-complete-guide)
- [Idempotency: Preventing Double Charges and Duplicate Actions (DZone)](https://dzone.com/articles/art-of-idempotency-preventing-double-charges-and-duplicate)
- [Vietnam's E-commerce Payment Shift in 2025 (TGM Research)](https://tgmresearch.com/vietnam-ecommerce-payment-shift-2025.html)
- [Cash-on-delivery (COD) services boom in Vietnam (VietnamNet)](https://vietnamnet.vn/en/cash-on-delivery-cod-services-boom-in-vietnam-as-e-commerce-grows-E123131.html)
- [Farm E-Commerce Platform: 10 Must-Have Features (GrazeCart)](https://www.grazecart.com/blog/farm-e-commerce-platform)
- [Managing Product Variants in an E-Commerce Store with Laravel (Medium)](https://medium.com/@shaunthornburgh/managing-product-variants-in-an-e-commerce-store-with-laravel-fbb22fddb1b1)
- [Laravel eCommerce Admin Order Creation (Webkul)](https://webkul.com/blog/laravel-ecommerce-admin-order-creation/)
- [MVP Scope Definition: How to Scope MVPs Right (Softverysolutions)](https://www.softverysolutions.com/post/how-to-do-the-right-mvp-scope-so-it-doesn-t-fall-apart-after-launch)
