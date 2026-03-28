---
phase: 02-product-inventory
plan: 04
subsystem: product-image-management
tags: [product-images, s3, intervention-image, thumbnail, admin, feature-tests]
dependency_graph:
  requires: [02-02]
  provides: [product-image-upload, product-image-primary, product-image-delete]
  affects: []
tech_stack:
  added: []
  patterns: [action-pattern, storage-fake, s3-upload, thumbnail-generation, db-transaction]
key_files:
  created:
    - app/Application/Product/Actions/UploadProductImageAction.php
    - app/Application/Product/Actions/SetPrimaryImageAction.php
    - app/Application/Product/Actions/DeleteProductImageAction.php
    - app/Presentation/Http/Controllers/Admin/ProductImageController.php
    - app/Presentation/Http/Requests/UploadProductImageRequest.php
  modified:
    - routes/api.php
    - tests/Feature/Admin/ProductImageTest.php
decisions:
  - Intervention Image 3.x uses read() not make() — enforced in UploadProductImageAction
  - Original image resized to max 1200px wide (bandwidth optimization), thumbnail at 400px
  - UploadProductImageAction returns ProductImageModel directly (not domain entity) — acceptable for Application layer actions that feed directly into Presentation resources
  - Auto-promote next image (by sort_order) when primary is deleted
metrics:
  duration: 18min
  completed: "2026-03-28"
  tasks: 1
  files: 7
---

# Phase 02 Plan 04: Product Image Management Summary

Product image gallery management using S3 storage and Intervention Image 3.x auto-thumbnail generation.

## What Was Built

Admin can upload product images to S3 with automatic thumbnail generation (Intervention Image 3.x), set one primary image per product (enforced via DB transaction), and delete images (removes both original and thumbnail from S3, promotes next image if primary was deleted). First image uploaded to a product auto-sets as primary.

## Tasks Completed

| # | Task | Commit | Files |
|---|------|--------|-------|
| 1 | Image upload, set primary, delete actions + endpoints | c97e9de | 7 files |

## Decisions Made

1. **Intervention Image 3.x API only**: `ImageManager::read()` is used throughout — never `::make()` (that is the v2 API which does not exist in 3.x).
2. **Synchronous thumbnail generation**: Per D-04, thumbnail is generated inline during upload. No async queue. Acceptable for family-business scale.
3. **UploadProductImageAction returns Eloquent model**: The action returns `ProductImageModel` directly since the controller wraps it in `ProductImageResource`. This avoids an unnecessary domain entity mapping for a non-domain output.
4. **Auto-promote on primary delete**: When the primary image is deleted, the next image by `sort_order` is automatically promoted to primary, preventing a state where a product has images but no primary.

## Verification Results

```
php artisan test --filter ProductImageTest
PASS  Tests\Feature\Admin\ProductImageTest
  7 tests, 34 assertions — all passed

php artisan test
Tests: 3 todos, 54 passed (258 assertions)

php -d memory_limit=512M ./vendor/bin/phpstan analyse --no-progress
[OK] No errors
```

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None — all data is wired. ProductImageResource returns real S3 URLs via `Storage::disk('s3')->url()`.

## Self-Check: PASSED

- [x] `app/Application/Product/Actions/UploadProductImageAction.php` — exists, contains `ImageManager`, `new Driver()`, `->read(`, `->scale(width: 400)`, `->toJpeg(`, `Storage::disk('s3')->put`
- [x] `app/Application/Product/Actions/SetPrimaryImageAction.php` — exists, contains `DB::transaction`, `'is_primary' => false`
- [x] `app/Application/Product/Actions/DeleteProductImageAction.php` — exists, contains `Storage::disk('s3')->delete` for both paths
- [x] `app/Presentation/Http/Controllers/Admin/ProductImageController.php` — exists, contains `store`, `setPrimary`, `destroy`
- [x] `app/Presentation/Http/Requests/UploadProductImageRequest.php` — exists, contains `'image' => ['required', 'image'`
- [x] `routes/api.php` — contains routes for `products/{product}/images`
- [x] `tests/Feature/Admin/ProductImageTest.php` — 7 tests, no `->todo()`, all use `Storage::fake('s3')`
- [x] Commit c97e9de exists
- [x] No `ImageManager::make()` calls anywhere
