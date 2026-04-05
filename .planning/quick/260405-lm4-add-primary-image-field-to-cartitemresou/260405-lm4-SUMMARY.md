---
phase: quick
plan: 260405-lm4
subsystem: presentation
tags: [cart, images, api-resource, eager-loading]
dependency_graph:
  requires: []
  provides: [primary_image field in cart item API responses]
  affects: [CartItemResource, CartController, CartTest]
tech_stack:
  added: []
  patterns: [constrained eager loading (items.product.images with where clause), private helper method for related-model image lookup]
key_files:
  created: []
  modified:
    - app/Presentation/Http/Resources/CartItemResource.php
    - app/Presentation/Http/Controllers/Public/CartController.php
    - tests/Feature/Public/CartTest.php
decisions:
  - Used private helper method primaryImage(ProductModel) instead of whenLoaded() inline — $this refers to CartItemModel via @mixin, not ProductModel; helper avoids the mixin confusion
  - Constrained eager load to is_primary=true in all three CartController methods — fetches only one image per product instead of all images
metrics:
  duration: ~8min
  completed: "2026-04-05T08:37:37Z"
  tasks_completed: 2
  files_modified: 3
---

# Quick Task 260405-lm4: Add primary_image to CartItemResource Summary

**One-liner:** Added primary_image field (url + thumbnail_url) to cart item API responses with constrained eager loading of product images in CartController.

## What Was Done

Added `primary_image` to `CartItemResource` so the cart API returns product images alongside cart items. The field follows the same pattern as `ProductResource.primary_image` but uses a private helper method since `$this` in CartItemResource is a `CartItemModel`, not a `ProductModel` — so `whenLoaded('images')` would check the cart item for images (wrong), not the product. The helper receives the already-resolved `$product` and calls `relationLoaded('images')` on it directly.

Updated `CartController` to eager load `items.product.images` constrained to `is_primary=true` in all three relevant methods (`show`, `addItem`, `updateItem`). This loads exactly one image record per product instead of all images, matching the pattern in `ProductController::index`.

## Tasks

| # | Task | Commit | Files |
|---|------|--------|-------|
| 1 | Add primary_image to CartItemResource + eager loading fix | 35ab8aa | CartItemResource.php, CartController.php |
| 2 | Add test coverage for primary_image in cart responses | ca26dfd | CartTest.php |

## Deviations from Plan

None — plan executed exactly as written.

## Known Stubs

None.

## Self-Check: PASSED

- `app/Presentation/Http/Resources/CartItemResource.php` — FOUND, contains `primary_image`
- `app/Presentation/Http/Controllers/Public/CartController.php` — FOUND, contains `items.product.images`
- `tests/Feature/Public/CartTest.php` — FOUND, 14 tests pass
- Commit 35ab8aa — FOUND
- Commit ca26dfd — FOUND
