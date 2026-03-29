---
phase: 02-product-inventory
plan: 02
subsystem: product-api
tags: [product, crud, admin, public-api, spatie-query-builder, feature-tests]
dependency_graph:
  requires: [02-01]
  provides: [admin-product-crud, public-product-api]
  affects: [02-03, 02-04]
tech_stack:
  added: [spatie/laravel-query-builder]
  patterns: [action-pattern, api-resource, query-builder-filter, pagination]
key_files:
  created:
    - app/Application/Product/Actions/CreateProductAction.php
    - app/Application/Product/Actions/UpdateProductAction.php
    - app/Application/Product/Actions/DeleteProductAction.php
    - app/Application/Product/Actions/ToggleProductActiveAction.php
    - app/Presentation/Http/Controllers/Admin/ProductController.php
    - app/Presentation/Http/Controllers/Public/ProductController.php
    - app/Presentation/Http/Requests/CreateProductRequest.php
    - app/Presentation/Http/Requests/UpdateProductRequest.php
    - app/Presentation/Http/Resources/ProductResource.php
    - app/Presentation/Http/Resources/ProductDetailResource.php
    - app/Presentation/Http/Resources/ProductImageResource.php
    - tests/Feature/Admin/ProductTest.php
    - tests/Feature/Public/PublicProductTest.php
  modified:
    - routes/api.php
decisions:
  - "new QueryBuilder(ProductModel::query(), $request) instead of QueryBuilder::for() — Larastan overrides the return type of ::for() to Eloquent Builder, causing PHPStan to miss allowedFilters(). Using the constructor directly gives PHPStan the correct Spatie\\QueryBuilder\\QueryBuilder type."
  - "ProductModel::factory()->create(['category_id' => $category->id]) instead of ->for($category) — Eloquent's for() derives relationship method name from model class by stripping 'Model', yielding categoryModel() which doesn't exist. Direct category_id assignment avoids the naming mismatch."
  - "unit_type->value in resources — ProductModel casts unit_type to UnitType enum; PHPStan @mixin sees it always as UnitType so instanceof check is always-true error. Direct ->value call is both correct and PHPStan-clean."
metrics:
  duration: 25min
  completed: 2026-03-28
  tasks_completed: 2
  files_created: 13
  files_modified: 1
---

# Phase 02 Plan 02: Product API (Admin CRUD + Public List/Detail) Summary

**One-liner:** Admin product CRUD with unit_type (con/kg) and toggle-active, plus public product list with QueryBuilder filter/sort/pagination and stock_quantity exposed to public.

## What Was Built

### Task 1: Admin Product CRUD

Four application actions following the established CreateCategoryAction pattern:

- **CreateProductAction** — validates category exists via CategoryRepositoryInterface, calls products.create()
- **UpdateProductAction** — validates product and category both exist, does NOT update stock_quantity (reserved for inventory management in 02-03)
- **DeleteProductAction** — validates product exists before deleting
- **ToggleProductActiveAction** — flips is_active via `!$product->isActive`

Admin ProductController with 6 methods: index (paginated 20/page with category+images eager load), store (201), show (ProductDetailResource), update, destroy (204), toggleActive.

Form requests enforce `unit_type` as `Rule::in(['con', 'kg'])`, `price_vnd` as integer >= 0.

Three API resources:
- **ProductResource** — list view with primary_image (whenLoaded), stock_quantity
- **ProductDetailResource** — detail view with category relation and full images collection
- **ProductImageResource** — S3-URL wrapping for path and thumbnail_path

### Task 2: Public Product API

Public ProductController uses `new QueryBuilder(ProductModel::query()->where('is_active', true), $request)` to apply the active filter before passing to Spatie QueryBuilder, then chains:
- `allowedFilters(['category_id'])` — category filter via ?filter[category_id]=N
- `defaultSort('name')` — alphabetical default
- `allowedSorts(['name', 'price_vnd', 'created_at'])` — client can override
- `paginate($request->integer('per_page', 20))` — default 20 per page

Public show() returns 404 (PRODUCT_NOT_FOUND) for inactive products, and includes stock_quantity in the response per decision D-12.

Routes added:
- Admin: `apiResource('products', AdminProductController)` + `PATCH products/{id}/toggle-active`
- Public: `GET /api/v1/products` and `GET /api/v1/products/{id}` (no auth)

## Tests

**ProductTest (6 tests, all passing):**
- can create a product with unit_type con
- can create a product with unit_type kg
- can update a product
- can delete a product
- can toggle product is_active (two toggles: true→false→true)
- requires auth for product endpoints

**PublicProductTest (7 tests, all passing):**
- can list active products without auth
- does not list inactive products
- can filter products by category_id
- can view product detail (includes category and stock_quantity)
- shows stock quantity in public response (D-12 compliance)
- returns 404 for inactive product detail
- paginates with per_page parameter (meta key present)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] PHPStan method.notFound on QueryBuilder::for() return type**
- **Found during:** Task 2 PHPStan verification
- **Issue:** Larastan overrides `QueryBuilder::for()` return type to `Eloquent\Builder`, causing PHPStan level 6 to report `allowedFilters()` as undefined method
- **Fix:** Replaced `QueryBuilder::for(ProductModel::class)->where(...)` with `new QueryBuilder(ProductModel::query()->where(...), $request)` — constructor instantiation gives PHPStan the concrete `Spatie\QueryBuilder\QueryBuilder` type
- **Files modified:** `app/Presentation/Http/Controllers/Public/ProductController.php`

**2. [Rule 1 - Bug] PHPStan instanceof.alwaysTrue in resources**
- **Found during:** Task 1 PHPStan verification
- **Issue:** `@mixin ProductModel` with unit_type cast to UnitType enum means PHPStan knows the type is always `UnitType`, making `instanceof UnitType` always-true error
- **Fix:** Replaced ternary instanceof check with direct `$this->unit_type->value` call
- **Files modified:** `ProductResource.php`, `ProductDetailResource.php`

**3. [Rule 3 - Blocking] Test factory for() naming mismatch**
- **Found during:** Task 1 test run
- **Issue:** `ProductModel::factory()->for($category)` generates `categoryModel()` relationship call (strips Model suffix then lowercases), but relationship is named `category()`
- **Fix:** Replaced `->for($category)` with `->create(['category_id' => $category->id])` across all product tests
- **Files modified:** `tests/Feature/Admin/ProductTest.php`, `tests/Feature/Public/PublicProductTest.php`

## Known Stubs

None. All resources serve real database data. stock_quantity comes from the products table.

## Self-Check: PASSED

All 12 key files confirmed present on disk. Both task commits (56d4969, 3b66b54) confirmed in git log.
