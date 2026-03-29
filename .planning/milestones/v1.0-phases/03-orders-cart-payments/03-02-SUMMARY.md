---
phase: 03-orders-cart-payments
plan: "02"
subsystem: cart-api
tags: [cart, middleware, actions, resources, pest-tests]
dependency_graph:
  requires: ["03-01"]
  provides: ["cart-crud-api"]
  affects: []
tech_stack:
  added: []
  patterns:
    - "X-Cart-Token header resolved via ResolveCartToken middleware"
    - "Domain Cart entity set on request attributes for downstream controllers"
    - "CartController loads Eloquent model with items.product for response"
    - "bcadd() for decimal quantity accumulation (D-04)"
key_files:
  created:
    - app/Application/Order/Actions/CreateCartAction.php
    - app/Application/Order/Actions/AddToCartAction.php
    - app/Application/Order/Actions/UpdateCartItemAction.php
    - app/Application/Order/Actions/RemoveCartItemAction.php
    - app/Presentation/Http/Controllers/Public/CartController.php
    - app/Presentation/Http/Middleware/ResolveCartToken.php
    - app/Presentation/Http/Requests/AddToCartRequest.php
    - app/Presentation/Http/Requests/UpdateCartItemRequest.php
    - app/Presentation/Http/Resources/CartResource.php
    - app/Presentation/Http/Resources/CartItemResource.php
    - tests/Feature/Public/CartTest.php
  modified:
    - routes/api.php
decisions:
  - "CartResource wraps Eloquent CartModel loaded with items.product for current prices — domain entity used for business logic in middleware/actions, Eloquent model used for Presentation layer (consistent with existing pattern)"
  - "ResolveCartToken middleware returns direct JSON response (not exception) to avoid double-handling in bootstrap/app.php — CART_TOKEN_REQUIRED and CART_NOT_FOUND are middleware-level responses, not domain exceptions"
  - "AddToCartAction calls refreshExpiry after addItem (new item path) but updateItemQuantity also refreshes internally — no double-refresh concern"
metrics:
  duration: "~3min"
  completed_date: "2026-03-29"
  tasks_completed: 2
  files_created: 11
  files_modified: 1
---

# Phase 3 Plan 2: Cart API Summary

**One-liner:** UUID-token cart API with X-Cart-Token middleware, 4 action classes, quantity accumulation (D-04), current price computation (D-05), and 12 passing feature tests covering CART-01..04.

## What Was Built

### Cart Actions (Application Layer)

- **CreateCartAction** — delegates to `CartRepositoryInterface::create()`, returns domain Cart
- **AddToCartAction** — finds product, checks `isActive` (throws `InactiveProductInCartException` if false), detects existing item via `findItemByCartAndProduct`, accumulates with `bcadd(..., 3)` per D-04, refreshes cart expiry
- **UpdateCartItemAction** — delegates to `updateItemQuantity`
- **RemoveCartItemAction** — delegates to `removeItem`

### ResolveCartToken Middleware

Resolves `X-Cart-Token` header before cart endpoints:
- Missing header → 401 `CART_TOKEN_REQUIRED`
- Token not found or expired → 404 `CART_NOT_FOUND`
- Found → sets `$request->attributes->set('cart', $domainCart)` for downstream

### CartController

5 endpoints:
- `POST /api/v1/cart` → creates cart, returns `{token, expires_at}` 201
- `GET /api/v1/cart` → loads Eloquent CartModel with `items.product`, returns CartResource with current prices
- `POST /api/v1/cart/items` → AddToCartAction + returns updated CartResource 201
- `PATCH /api/v1/cart/items/{item}` → UpdateCartItemAction + returns updated CartResource 200
- `DELETE /api/v1/cart/items/{item}` → RemoveCartItemAction, returns `{success, message}` 200

### CartResource / CartItemResource

- `CartItemResource`: `id`, `product_id`, `product_name`, `product_price_vnd` (current from DB), `quantity`, `subtotal` (qty * price integer), `is_available`
- `CartResource`: `id`, `token`, `expires_at`, `items[]`, `total_amount` (sum of subtotals, integer VND)
- No cached prices — always reflects current `ProductModel.price_vnd` (D-05)

### Form Requests

- `AddToCartRequest`: `product_id` (required integer min:1) + `quantity` (required numeric gt:0), Vietnamese messages
- `UpdateCartItemRequest`: `quantity` (required numeric gt:0), Vietnamese messages

## Feature Tests (12 passing)

| Test | Requirement |
|------|-------------|
| Creates cart, returns UUID token | CART-01 |
| 401 when X-Cart-Token missing | CART-01 |
| 404 when X-Cart-Token invalid | CART-01 |
| Adds product to cart | CART-01 |
| Accumulates quantity for same product | D-04 |
| Rejects inactive product (422) | D-06 |
| Stock NOT decremented when adding to cart | CART-04 |
| Empty cart returns `items: []` | CART-02 |
| Returns items with current prices and total | CART-02 |
| Updates item quantity | CART-03 |
| Removes item from cart | CART-03 |
| Rejects quantity <= 0 (422) | CART-03 |

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None — all cart endpoints are fully wired. Product prices are read from live DB (no stubs).

## Self-Check: PASSED

- app/Application/Order/Actions/CreateCartAction.php: FOUND
- app/Application/Order/Actions/AddToCartAction.php: FOUND
- app/Application/Order/Actions/UpdateCartItemAction.php: FOUND
- app/Application/Order/Actions/RemoveCartItemAction.php: FOUND
- app/Presentation/Http/Controllers/Public/CartController.php: FOUND
- app/Presentation/Http/Middleware/ResolveCartToken.php: FOUND
- app/Presentation/Http/Requests/AddToCartRequest.php: FOUND
- app/Presentation/Http/Requests/UpdateCartItemRequest.php: FOUND
- app/Presentation/Http/Resources/CartResource.php: FOUND
- app/Presentation/Http/Resources/CartItemResource.php: FOUND
- tests/Feature/Public/CartTest.php: FOUND
- Commit 6a8c312 (task 1): FOUND
- Commit 9ab3ce4 (task 2): FOUND
- `php artisan route:list --path=cart` shows 5 routes: VERIFIED
- `php artisan test --filter=CartTest` 12 passed (53 assertions): VERIFIED
- `php -d memory_limit=512M vendor/bin/phpstan analyse` no errors: VERIFIED
