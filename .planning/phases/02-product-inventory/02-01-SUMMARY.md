---
phase: 02-product-inventory
plan: 01
subsystem: product-domain
tags: [domain, infrastructure, migrations, category-crud, clean-architecture]
dependency_graph:
  requires: [01-02]
  provides: [product-domain, category-crud-endpoints, wave-0-test-stubs]
  affects: [02-02, 02-03, 02-04]
tech_stack:
  added:
    - intervention/image ^3.11
    - league/flysystem-aws-s3-v3 ^3.32
    - spatie/laravel-query-builder ^6.3
  patterns:
    - Domain entity (pure PHP, readonly constructor, zero Illuminate imports)
    - Repository interface in Domain, Eloquent implementation in Infrastructure
    - Mapper pattern (static toDomain() method)
    - PHPDoc @property annotations for Eloquent enum cast type inference
    - PHPDoc @mixin on JsonResource for PHPStan property access
key_files:
  created:
    - app/Domain/Product/Entities/Product.php
    - app/Domain/Product/Entities/Category.php
    - app/Domain/Product/Entities/ProductImage.php
    - app/Domain/Product/Entities/StockAdjustment.php
    - app/Domain/Product/Enums/UnitType.php
    - app/Domain/Product/Enums/AdjustmentType.php
    - app/Domain/Product/Exceptions/InsufficientStockException.php
    - app/Domain/Product/Exceptions/CategoryDepthExceededException.php
    - app/Domain/Product/Exceptions/CategoryNotFoundException.php
    - app/Domain/Product/Exceptions/ProductNotFoundException.php
    - app/Domain/Product/Repositories/CategoryRepositoryInterface.php
    - app/Domain/Product/Repositories/ProductRepositoryInterface.php
    - app/Domain/Product/Repositories/StockAdjustmentRepositoryInterface.php
    - app/Infrastructure/Persistence/Eloquent/Models/CategoryModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/ProductModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/ProductImageModel.php
    - app/Infrastructure/Persistence/Eloquent/Models/StockAdjustmentModel.php
    - app/Infrastructure/Persistence/Mappers/CategoryMapper.php
    - app/Infrastructure/Persistence/Mappers/ProductMapper.php
    - app/Infrastructure/Persistence/Mappers/ProductImageMapper.php
    - app/Infrastructure/Persistence/Mappers/StockAdjustmentMapper.php
    - app/Infrastructure/Persistence/Repositories/EloquentCategoryRepository.php
    - app/Infrastructure/Persistence/Repositories/EloquentProductRepository.php
    - app/Infrastructure/Persistence/Repositories/EloquentStockAdjustmentRepository.php
    - database/migrations/2026_03_29_000001_create_categories_table.php
    - database/migrations/2026_03_29_000002_create_products_table.php
    - database/migrations/2026_03_29_000003_create_product_images_table.php
    - database/migrations/2026_03_29_000004_create_stock_adjustments_table.php
    - database/factories/CategoryModelFactory.php
    - database/factories/ProductModelFactory.php
    - app/Application/Product/Actions/CreateCategoryAction.php
    - app/Application/Product/Actions/UpdateCategoryAction.php
    - app/Application/Product/Actions/DeleteCategoryAction.php
    - app/Presentation/Http/Controllers/Admin/CategoryController.php
    - app/Presentation/Http/Requests/CreateCategoryRequest.php
    - app/Presentation/Http/Requests/UpdateCategoryRequest.php
    - app/Presentation/Http/Resources/CategoryResource.php
    - tests/Feature/Admin/CategoryTest.php
    - tests/Feature/Admin/ProductTest.php
    - tests/Feature/Public/PublicProductTest.php
    - tests/Feature/Admin/StockAdjustmentTest.php
    - tests/Feature/Admin/StockAdjustmentHistoryTest.php
    - tests/Feature/Admin/ProductImageTest.php
  modified:
    - app/Infrastructure/Providers/RepositoryServiceProvider.php
    - bootstrap/app.php
    - routes/api.php
    - database/factories/UserModelFactory.php
    - phpstan.neon
decisions:
  - "@property annotations on Eloquent models for enum cast type inference — larastan 3.x does not automatically infer types for properties cast via the casts() method (only the $casts property array); explicit @property annotations fix PHPStan level 6 compliance"
  - "@mixin CategoryModel on JsonResource — PHPStan cannot resolve magic property access through __get on JsonResource; @mixin annotation provides the model's properties without duplicating them"
  - "database/factories/ added to phpstan.neon paths — factory classes under database/ are not in app/ scan path; without this PHPStan reports all new factory class references as class.notFound"
  - "DeleteCategoryAction throws generic DomainException for product/children guards — these are business rule violations, not domain entity errors; CategoryNotFoundException was already a specific exception for entity not found"
  - "UpdateCategoryAction circular self-reference check uses parentId === id — the original circular check comparing parent.parentId to category id was always false (parent.parentId is null at that point due to prior depth check)"
metrics:
  duration: 11min
  completed_date: "2026-03-28"
  tasks_completed: 2
  files_created: 41
  files_modified: 5
---

# Phase 02 Plan 01: Product Domain, Migrations, Infrastructure + Category CRUD Summary

**One-liner:** Product domain layer (4 pure-PHP entities, 2 enums, 4 exceptions, 3 repository interfaces), 4 migrations with DECIMAL(10,3) stock, Infrastructure layer (Eloquent models, mappers, repositories), Category admin CRUD endpoints with max-depth-2 enforcement, Wave 0 test stubs for all Phase 2 test files.

## What Was Built

### Task 1: Domain + Migrations + Infrastructure + Packages

**Packages installed:** `intervention/image ^3.11`, `league/flysystem-aws-s3-v3 ^3.32`, `spatie/laravel-query-builder ^6.3`

**Domain layer (app/Domain/Product/):**
- Entities: `Product`, `Category`, `ProductImage`, `StockAdjustment` — all `final class`, readonly constructors, zero Illuminate imports
- Enums: `UnitType` (Con/Kg), `AdjustmentType` (NhapHang/KiemKe/HuHong/Khac)
- Exceptions: `InsufficientStockException`, `CategoryDepthExceededException`, `CategoryNotFoundException`, `ProductNotFoundException`
- Repository interfaces: `CategoryRepositoryInterface`, `ProductRepositoryInterface` (with `findByIdForUpdate`), `StockAdjustmentRepositoryInterface`

**Migrations:**
- `categories` — id, name, slug (unique), parent_id (nullable FK self-ref), description, sort_order, is_active, timestamps
- `products` — id, category_id (FK restrict), name, description, price_vnd, unit_type, **stock_quantity DECIMAL(10,3)**, is_active, timestamps
- `product_images` — id, product_id (FK cascade), path, thumbnail_path, is_primary, sort_order, timestamps
- `stock_adjustments` — id, product_id, admin_user_id, delta DECIMAL(10,3), adjustment_type, note, stock_before DECIMAL(10,3), stock_after DECIMAL(10,3), created_at (no updated_at — immutable), index on [product_id, created_at]

**Infrastructure layer:**
- Eloquent models with `casts()` for enum types and DECIMAL
- `@property` annotations on `ProductModel` and `StockAdjustmentModel` for PHPStan enum cast inference
- Mappers: static `toDomain()` methods, `(string)` cast for DECIMAL fields
- Repositories implementing interfaces: `findByIdForUpdate()` uses `lockForUpdate()`

**Bootstrap and providers:**
- `RepositoryServiceProvider`: 3 new bindings added
- `bootstrap/app.php`: 4 new exception cases (INSUFFICIENT_STOCK, CATEGORY_DEPTH_EXCEEDED, CATEGORY_NOT_FOUND, PRODUCT_NOT_FOUND)

### Task 2: Category CRUD + Wave 0 Test Stubs

**Application layer:**
- `CreateCategoryAction`: validates depth (max 2 levels), throws `CategoryDepthExceededException` if parent already has parent
- `UpdateCategoryAction`: same depth enforcement + self-reference guard (`parentId === id`)
- `DeleteCategoryAction`: guards against categories with products or children

**Presentation layer:**
- `CategoryController`: index (with children eager-loaded), store, show, update, destroy
- `CreateCategoryRequest` / `UpdateCategoryRequest`: slug regex validation, unique constraint (update ignores current id)
- `CategoryResource`: `@mixin CategoryModel` for PHPStan, includes `whenLoaded('children')`

**Routes:** `Route::apiResource('categories', CategoryController::class)` inside `auth:sanctum` admin group

**Tests:**
- `CategoryTest`: 8 real tests — all pass (create root, create child, reject grandchild, update, delete empty, reject with products, reject with children, auth required)
- Wave 0 stubs: ProductTest (6 todos), PublicProductTest (5 todos), StockAdjustmentTest (5 todos), StockAdjustmentHistoryTest (3 todos), ProductImageTest (4 todos)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] UpdateCategoryAction circular reference check was always false**
- **Found during:** Task 2, PHPStan analysis
- **Issue:** Original check `$parent->parentId === $id` compared null (parent's parentId, which was guaranteed null by the prior `!== null` check) to int — always false
- **Fix:** Replaced with self-reference check `$parentId === $id` which correctly prevents a category becoming its own parent
- **Files modified:** `app/Application/Product/Actions/UpdateCategoryAction.php`
- **Commit:** ac8771a

**2. [Rule 2 - Missing PHPStan config] Factories not in PHPStan scan paths**
- **Found during:** Task 1, PHPStan analysis
- **Issue:** `database/factories/` is outside `app/` which was the only scanned path; all factory class references reported as `class.notFound`
- **Fix:** Added `database/factories/` to `phpstan.neon` paths; added `@extends Factory<UserModel>` to UserModelFactory
- **Files modified:** `phpstan.neon`, `database/factories/UserModelFactory.php`
- **Commit:** 1ec6c56

**3. [Rule 1 - Bug] Larastan not inferring enum cast types from casts() method**
- **Found during:** Task 1, PHPStan analysis
- **Issue:** larastan 3.x infers property types from the `$casts` array property but not from the `casts(): array` method for enum cast resolution; `$model->unit_type` and `$model->adjustment_type` typed as `string` instead of their enum types
- **Fix:** Added `@property UnitType $unit_type` to `ProductModel` and `@property AdjustmentType $adjustment_type` + `@property Carbon $created_at` to `StockAdjustmentModel`
- **Files modified:** `ProductModel.php`, `StockAdjustmentModel.php`
- **Commit:** 1ec6c56

## Commits

| Task | Commit | Description |
|------|--------|-------------|
| Task 1 | 1ec6c56 | feat(02-01): domain layer, migrations, infrastructure, packages |
| Task 2 | ac8771a | feat(02-01): category CRUD admin endpoints + Wave 0 test stubs |

## Known Stubs

The following Wave 0 test stubs exist intentionally — they will be implemented in subsequent Phase 2 plans:

| File | Todos | Resolved by |
|------|-------|-------------|
| `tests/Feature/Admin/ProductTest.php` | 6 | Plan 02-02 |
| `tests/Feature/Public/PublicProductTest.php` | 5 | Plan 02-02 |
| `tests/Feature/Admin/StockAdjustmentTest.php` | 5 | Plan 02-03 |
| `tests/Feature/Admin/StockAdjustmentHistoryTest.php` | 3 | Plan 02-03 |
| `tests/Feature/Admin/ProductImageTest.php` | 4 | Plan 02-04 |

These stubs do NOT block the plan's goal (Category CRUD is fully delivered). They are intentional placeholders per the Wave 0 strategy in Phase 2 planning.

## Self-Check: PASSED

All key files verified present. Both commits (1ec6c56, ac8771a) confirmed in git log.
