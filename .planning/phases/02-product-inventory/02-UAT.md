---
status: complete
phase: 02-product-inventory
source: [02-01-SUMMARY.md, 02-02-SUMMARY.md, 02-03-SUMMARY.md, 02-04-SUMMARY.md]
started: 2026-03-28T22:00:00+07:00
updated: 2026-03-28T23:30:00+07:00
---

## Current Test

[testing complete]

## Tests

### 1. Category CRUD (admin)
expected: POST /api/v1/admin/categories with name, slug, description creates a category (201). POST a child category with parent_id pointing to that category succeeds. POST a grandchild (depth 3) is rejected. PUT updates, DELETE removes (empty category only). GET lists all with children nested.
result: pass

### 2. Product admin CRUD with unit_type
expected: POST /api/v1/admin/products with name, price_vnd, unit_type='con', category_id creates a product (201). Same with unit_type='kg'. PUT updates fields. DELETE removes product (204). PATCH .../toggle-active flips is_active (true→false→true).
result: pass

### 3. Public product list with filtering
expected: GET /api/v1/products (no auth) returns only active products, paginated (default 20/page). GET /api/v1/products?filter[category_id]=N returns only products in that category. GET /api/v1/products?sort=-price_vnd sorts by price descending. Inactive products never appear.
result: pass

### 4. Public product detail with stock
expected: GET /api/v1/products/{id} (no auth) returns product detail including stock_quantity, category info, and images array. Requesting an inactive product returns 404 with PRODUCT_NOT_FOUND code.
result: pass

### 5. Stock adjustment — positive delta
expected: POST /api/v1/admin/products/{id}/stock-adjustments with delta='50.000', adjustment_type='nhap_hang' increases stock. Response (201) shows stock_before, stock_after, delta, adjustment_type, admin_user_id.
result: pass

### 6. Stock adjustment — negative stock prevention
expected: POST /api/v1/admin/products/{id}/stock-adjustments with a negative delta that exceeds current stock returns 422 with code INSUFFICIENT_STOCK. Stock remains unchanged.
result: pass

### 7. Stock adjustment history
expected: GET /api/v1/admin/products/{id}/stock-adjustments returns paginated list of all adjustments for that product, newest first. Each entry has delta, adjustment_type, note, stock_before, stock_after, created_at. Supports ?per_page=N.
result: pass

### 8. Product image upload with thumbnail
expected: POST /api/v1/admin/products/{id}/images with image file upload stores original (max 1200px) and thumbnail (400px) on S3. Response (201) includes url and thumbnail_url. First image auto-sets as primary (is_primary=true).
result: pass

### 9. Set primary image
expected: PATCH /api/v1/admin/products/{id}/images/{imageId}/primary sets that image as primary. Only one image can be primary at a time — previous primary is cleared.
result: pass

### 10. Delete product image
expected: DELETE /api/v1/admin/products/{id}/images/{imageId} removes the image from S3 (both original and thumbnail) and DB (204). If primary was deleted, next image by sort_order becomes primary.
result: pass

### 11. Auth required for admin endpoints
expected: All admin endpoints (products, categories, stock-adjustments, images) return 401 without a valid Sanctum token. Public product endpoints (list, detail) work without auth.
result: pass

## Summary

total: 11
passed: 11
issues: 0
pending: 0
skipped: 0
blocked: 0

## Gaps

[none]
