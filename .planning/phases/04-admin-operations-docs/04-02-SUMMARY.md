---
phase: 04-admin-operations-docs
plan: 02
subsystem: api-documentation
tags: [scribe, docblocks, api-docs, vietnamese, openapi]
dependency_graph:
  requires:
    - 04-01 (DashboardController, OrderController with list/filter endpoints)
    - 03-orders-cart-payments (CartController, CheckoutController, OrderController full)
    - 02-product-inventory (ProductController, CategoryController, StockAdjustmentController, ProductImageController)
  provides:
    - public/docs/index.html — browsable static API docs for all v1 endpoints
    - public/docs/openapi.yaml — OpenAPI 3.0.3 spec
    - public/docs/collection.json — Postman collection
  affects:
    - All Presentation layer controllers (docblock annotations only, no logic changes)
    - config/scribe.php (type changed to static, group ordering added, strategy fix)
tech_stack:
  added: []
  patterns:
    - "@group GroupName > SubGroup" — Scribe group hierarchy with > separator
    - "@bodyParam / @queryParam / @response / @unauthenticated" — Scribe v5.9 annotations
    - "removeStrategies(Defaults::BODY_PARAMETERS_STRATEGIES, [GetFromFormRequest::class])" — disable problematic validation rule parser
    - "scribe type: static" — generates public/docs/index.html directly
key_files:
  created:
    - public/docs/index.html (6577 lines — browsable HTML docs)
    - public/docs/openapi.yaml (OpenAPI 3.0.3 spec)
    - public/docs/collection.json (Postman collection)
  modified:
    - app/Presentation/Http/Controllers/Auth/AuthController.php
    - app/Presentation/Http/Controllers/Admin/CategoryController.php
    - app/Presentation/Http/Controllers/Admin/ProductController.php
    - app/Presentation/Http/Controllers/Admin/StockAdjustmentController.php
    - app/Presentation/Http/Controllers/Admin/ProductImageController.php
    - app/Presentation/Http/Controllers/Admin/OrderController.php
    - app/Presentation/Http/Controllers/Public/ProductController.php
    - app/Presentation/Http/Controllers/Public/CartController.php
    - app/Presentation/Http/Controllers/Public/CheckoutController.php
    - config/scribe.php
decisions:
  - "Switch Scribe type from laravel to static — plan required public/docs/index.html; laravel type generates Blade views, static generates browsable HTML files directly"
  - "Remove GetFromFormRequest body params strategy — Scribe's ValidationRules parser crashes on 'file' + 'max' and 'array' + 'min' rule combinations; all controllers have explicit @bodyParam annotations so FormRequest extraction is redundant"
  - "Omit Example: value for file @bodyParam — Scribe attempts fopen() on the example string which crashes for non-path strings like '(binary)'"
metrics:
  duration: ~15min
  completed: 2026-03-29
  tasks_completed: 1
  files_changed: 10
---

# Phase 04 Plan 02: API Documentation Summary

Complete Scribe API documentation with Vietnamese descriptions covering all 34 v1 endpoints — Auth, Dashboard, Orders, Products, Categories, Inventory, Images, Cart, Checkout — browsable at /docs with logical group ordering.

## Objective

Add Scribe docblock annotations to all v1 controllers, standardize group names, configure group ordering in scribe.php, and generate `public/docs/index.html` as the final v1 deliverable for frontend integration.

## Tasks Completed

| # | Name | Commit | Files |
|---|------|--------|-------|
| 1 | Standardize docblocks, fix Scribe config, generate static docs | b797b90 | 10 controllers + config/scribe.php + public/docs/* |

## Task 2 — Checkpoint

**Type:** human-verify
**Status:** Awaiting human verification

Human visual verification of the generated API docs is required before this plan is considered complete.

**How to verify:**
1. Start dev server: `php artisan serve`
2. Open: http://localhost:8000/docs
3. Verify all 10 groups appear in sidebar in correct order
4. Check that Vietnamese descriptions are present
5. Check Auth group has login/logout with request/response examples

## Decisions Made

1. **Switch Scribe type: laravel → static** — The plan's artifact requirement was `public/docs/index.html`. The existing scribe.php used `type: laravel` which generates Blade views at `resources/views/scribe/`. Switched to `type: static` to produce the required static HTML file.

2. **Disable GetFromFormRequest body params strategy** — Scribe's `ValidationRules` parser in `GetFromFormRequest` crashes with `Undefined array key "file"` on `['file', 'max:5120']` rules, and `Undefined array key "array"` on `['array', 'min:1']` rules. Since all POST/PATCH endpoints already have explicit `@bodyParam` docblock annotations in controllers, FormRequest-based extraction is redundant. Used `removeStrategies()` to exclude it.

3. **Omit Example: value for file @bodyParam** — Scribe attempts `fopen()` on the example string to create an `UploadedFile` for test requests. Strings like `(binary)` are not valid file paths and cause crashes. Removed the `Example:` from image upload params.

4. **Group names in Vietnamese with > separator** — Standardized `Admin - Orders` to `Admin > Đơn hàng` and `Checkout` to `Public > Checkout` to match the consistent `>` hierarchy pattern.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Scribe crash on file/array validation rules**
- **Found during:** Task 1, Step 4 (scribe:generate — exit code 2)
- **Issue:** `GetFromFormRequest` strategy throws `Undefined array key "file"` when parsing `'max:5120'` rule for an `image` param, and `Undefined array key "array"` for `'min:1'` on an `items` array param
- **Fix:** Disabled `GetFromFormRequest` strategy in `config/scribe.php` `strategies.bodyParameters` using `removeStrategies()`. All endpoints have explicit `@bodyParam` annotations.
- **Files modified:** `config/scribe.php`
- **Commit:** b797b90

**2. [Rule 1 - Bug] Fixed file @bodyParam example causing fopen crash**
- **Found during:** Task 1, Step 4 (scribe:generate from main project — image upload route FAIL)
- **Issue:** `@bodyParam image file required ... Example: (binary)` causes Scribe to call `fopen("(binary)", "r")` which fails
- **Fix:** Removed the `Example:` value from the image file param — Scribe handles missing examples gracefully for file params
- **Files modified:** `app/Presentation/Http/Controllers/Admin/ProductImageController.php`
- **Commit:** b797b90

**3. [Rule 2 - Infrastructure] Switched Scribe type from laravel to static**
- **Found during:** Task 1, Step 4 (plan requires public/docs/index.html artifact)
- **Issue:** `type: laravel` generates Blade views in `resources/views/scribe/` not `public/docs/index.html`
- **Fix:** Changed to `type: static` — generates static HTML in `public/docs/`
- **Files modified:** `config/scribe.php`
- **Commit:** b797b90

## Test Results

- `php artisan test` — 130 passed, 3 todos, 0 failures (verified from main project)
- `php -d memory_limit=512M vendor/bin/phpstan analyse` — No errors
- `php artisan scribe:generate` — exit 0, 34 routes processed, no errors
- `public/docs/index.html` — 6577 lines

## Known Stubs

None — all endpoint documentation uses real example values (Vietnamese names, phone numbers starting with 0, realistic prices in VNĐ).

## Self-Check: PASSED
