# Phase 2: Product & Inventory - Research

**Researched:** 2026-03-28
**Domain:** Laravel 12 / Clean Architecture — Product catalog, gallery image upload (S3), 2-tier categories, DECIMAL inventory with audit log
**Confidence:** HIGH

## Summary

Phase 2 builds the product and inventory backbone on top of the auth scaffolding established in Phase 1. The domain spans four tables (`categories`, `products`, `product_images`, `stock_adjustments`), image upload to S3/R2 with auto-thumbnail generation via Intervention Image 3.x, and a public read-only product API.

The established Clean Architecture pattern (Domain entity → Repository interface → Eloquent model + Mapper → Application Action → Presentation Controller) is the mandatory template for every aggregate in this phase. All nine requirements (PROD-01..05, INVT-01..04) fit cleanly into this pattern with no architectural surprises. The largest implementation risk is the S3+thumbnail pipeline — specifically ensuring PHPStan level 6 generics annotations on new Eloquent models, and keeping image processing logic out of the Domain layer.

The two new packages needed are `intervention/image:^3.11` (GD driver already present on host PHP) and `league/flysystem-aws-s3-v3:^3.32` (S3 Flysystem adapter). Both are stable, well-maintained, and Laravel 12-compatible.

**Primary recommendation:** Follow the Phase 1 entity/action/repository pattern for each of the three aggregates (Category, Product, StockAdjustment). Use synchronous thumbnail generation in the upload action (not queued) — the business scale does not justify queue complexity for a single image resize. Validate category max-depth in the Application layer (CreateCategoryAction), not the Domain entity, to avoid leaking query logic into the domain.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**D-01:** Ảnh sản phẩm lưu trên cloud storage (S3 hoặc Cloudflare R2) — không dùng disk nội bộ
**D-02:** Mỗi sản phẩm có gallery nhiều ảnh; lưu trong bảng `product_images` riêng với `product_id`, `path`, `is_primary`, `sort_order`
**D-03:** 1 ảnh chính (is_primary = true) + các ảnh phụ. Chỉ 1 ảnh được is_primary tại một thời điểm
**D-04:** Backend tự động tạo thumbnail khi upload (dùng Intervention Image hoặc tương đương). Upload file gốc + thumbnail lên S3. Public API trả về cả URL gốc và URL thumbnail

**D-05:** Danh mục phân cấp 2 tầng: cha + con (ví dụ: Gia cầm > Chim bồ câu). Không cho phép danh mục con của con
**D-06:** Bảng `categories` có cột `parent_id` nullable. Enforce max depth 2 ở application layer
**D-07:** Mỗi sản phẩm thuộc đúng 1 danh mục (foreign key `category_id` trên bảng `products`). Không có many-to-many

**D-08:** Điều chỉnh tồn kho ghi theo delta: lưu số lượng thay đổi (ví dụ: +50, -3), không phải giá trị tuyệt đối mới
**D-09:** Mỗi điều chỉnh phải có `adjustment_type` từ enum: `nhap_hang` / `kiem_ke` / `hu_hong` / `khac`
**D-10:** Có thêm trường `note` (text, nullable) cho admin ghi chú tự do
**D-11:** Bảng `stock_adjustments` lưu: `product_id`, `admin_user_id`, `delta`, `adjustment_type`, `note`, `stock_before`, `stock_after`, `created_at`

**D-12:** Public API trả về số lượng tồn kho cụ thể để show lên cho người dùng xem
**D-13:** Public product list hỗ trợ filter theo `category_id`. Sort mặc định theo tên. Pagination chuẩn (per_page mặc định 20)
**D-14:** Public API chỉ trả về sản phẩm có `is_active = true`

### Claude's Discretion

- Cách implement thumbnail (queue job hay synchronous) — tùy Claude quyết định dựa trên complexity
- Cách validate max depth 2 cho category (middleware, service, hay domain rule) — tùy Claude
- Thứ tự field trong response (API resource shape) — tùy Claude, miễn nhất quán

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| PROD-01 | Admin có thể tạo, sửa, xóa sản phẩm với tên tiếng Việt, giá, mô tả, ảnh | Product entity + CRUD actions + ProductImageUploadAction (S3 + thumbnail) |
| PROD-02 | Mỗi sản phẩm có unit_type: `con` hoặc `kg` | PHP enum UnitType; DECIMAL(10,3) stock field on products table |
| PROD-03 | Admin có thể tạo và quản lý danh mục | Category entity + CRUD actions; max-depth-2 enforced in CreateCategoryAction |
| PROD-04 | Admin có thể bật/tắt hiển thị sản phẩm (is_active) | ToggleProductActiveAction; public queries filter on is_active=true |
| PROD-05 | Khách có thể xem danh sách và chi tiết sản phẩm đang bán | Public controller routes GET /api/v1/products (filter + pagination) and GET /api/v1/products/{id} |
| INVT-01 | Tồn kho lưu dạng DECIMAL(10,3) | Migration: `$table->decimal('stock_quantity', 10, 3)->default(0)` |
| INVT-02 | Admin có thể điều chỉnh tồn kho thủ công | AdjustStockAction with delta + DB::transaction; updates products.stock_quantity |
| INVT-03 | Hệ thống không bao giờ cho phép tồn kho xuống dưới 0 | Domain rule in AdjustStockAction: throw InsufficientStockException if stock_before + delta < 0 |
| INVT-04 | Admin có thể xem lịch sử điều chỉnh tồn kho | GET /api/v1/admin/products/{id}/stock-adjustments (paginated, newest first) |
</phase_requirements>

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.5.2 (host) | Runtime | Already on host; PHP 8.3+ on Laravel 12 |
| Laravel | 12.56.0 | Framework | Already installed; bundled tools used |
| spatie/laravel-data | ^4.20 | DTOs for Product, Category, StockAdjustment | Already installed per CLAUDE.md; eliminates manual DTO duplication |
| intervention/image | ^3.11 | Thumbnail generation on upload | Latest stable 3.11.7; GD driver confirmed present on host PHP |
| league/flysystem-aws-s3-v3 | ^3.32 | S3/R2 filesystem adapter | Latest stable 3.32.0; required for Laravel S3 disk driver; NOT yet installed |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| spatie/laravel-query-builder | ^6.3 | Filterable/sortable public product list | Public GET /products endpoint with category filter + name sort |
| spatie/laravel-permission | ^6.0 | Admin middleware on product/inventory routes | Already installed; `role:admin` or `auth:sanctum` on admin routes |

### Not Yet Installed (require install in Wave 0)
```
intervention/image        NOT in composer.json — must add
league/flysystem-aws-s3-v3  NOT in composer.json — must add
spatie/laravel-query-builder  NOT in composer.json — must add
```

**Version verification (confirmed against Packagist 2026-03-28):**
- `intervention/image` latest stable: 3.11.7 (use `^3.11`)
- `intervention/image` v4.0.0 exists but is a major rewrite with different API; stick with 3.x for stable/documented patterns
- `league/flysystem-aws-s3-v3` latest stable: 3.32.0 (use `^3.32`)

**Installation:**
```bash
composer require intervention/image:^3.11
composer require league/flysystem-aws-s3-v3:^3.32
composer require spatie/laravel-query-builder:^6.3
```

---

## Architecture Patterns

### Mandatory Layer Map (from Phase 1)
```
app/
├── Domain/
│   ├── Product/
│   │   ├── Entities/Product.php           # plain PHP, final class, readonly constructor
│   │   ├── Entities/Category.php
│   │   ├── Entities/ProductImage.php
│   │   ├── Entities/StockAdjustment.php
│   │   ├── Enums/UnitType.php             # PHP enum: Con, Kg
│   │   ├── Enums/AdjustmentType.php       # PHP enum: nhap_hang, kiem_ke, hu_hong, khac
│   │   ├── Exceptions/InsufficientStockException.php
│   │   ├── Exceptions/CategoryDepthExceededException.php
│   │   └── Repositories/
│   │       ├── ProductRepositoryInterface.php
│   │       ├── CategoryRepositoryInterface.php
│   │       └── StockAdjustmentRepositoryInterface.php
├── Application/
│   └── Product/
│       └── Actions/
│           ├── CreateProductAction.php
│           ├── UpdateProductAction.php
│           ├── DeleteProductAction.php
│           ├── ToggleProductActiveAction.php
│           ├── UploadProductImageAction.php    # S3 + thumbnail (synchronous)
│           ├── DeleteProductImageAction.php
│           ├── SetPrimaryImageAction.php
│           ├── CreateCategoryAction.php        # enforces max-depth-2
│           ├── UpdateCategoryAction.php
│           ├── DeleteCategoryAction.php
│           ├── AdjustStockAction.php           # delta + guard < 0 + DB::transaction
│           └── ListStockAdjustmentsAction.php
├── Infrastructure/
│   └── Persistence/
│       ├── Eloquent/Models/
│       │   ├── ProductModel.php
│       │   ├── CategoryModel.php
│       │   ├── ProductImageModel.php
│       │   └── StockAdjustmentModel.php
│       ├── Mappers/
│       │   ├── ProductMapper.php
│       │   ├── CategoryMapper.php
│       │   ├── ProductImageMapper.php
│       │   └── StockAdjustmentMapper.php
│       └── Repositories/
│           ├── EloquentProductRepository.php
│           ├── EloquentCategoryRepository.php
│           └── EloquentStockAdjustmentRepository.php
└── Presentation/
    └── Http/
        ├── Controllers/
        │   ├── Admin/
        │   │   ├── ProductController.php
        │   │   ├── CategoryController.php
        │   │   ├── ProductImageController.php
        │   │   └── StockAdjustmentController.php
        │   └── Public/
        │       └── ProductController.php
        ├── Requests/
        │   ├── CreateProductRequest.php
        │   ├── UpdateProductRequest.php
        │   ├── CreateCategoryRequest.php
        │   ├── AdjustStockRequest.php
        │   └── UploadProductImageRequest.php
        └── Resources/
            ├── ProductResource.php
            ├── ProductDetailResource.php
            ├── CategoryResource.php
            ├── ProductImageResource.php
            └── StockAdjustmentResource.php
```

### Pattern 1: Domain Entity (from Phase 1 template)
```php
// Source: app/Domain/Auth/Entities/AdminUser.php
declare(strict_types=1);

namespace App\Domain\Product\Entities;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly int $priceVnd,       // store cents or whole VND — consistent
        public readonly UnitType $unitType,  // PHP enum
        public readonly int $categoryId,
        public readonly float $stockQuantity, // maps to DECIMAL(10,3)
        public readonly bool $isActive,
    ) {}
}
```

**Critical:** No `use Illuminate\...` or `use Laravel\...` in Domain files. The PHPStan custom rule `NoLaravelImportInDomainRule` enforces this and will fail CI if violated.

### Pattern 2: Application Action
```php
// Source: app/Application/Auth/Actions/LoginAdminAction.php pattern
declare(strict_types=1);

final class AdjustStockAction
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly StockAdjustmentRepositoryInterface $adjustments,
    ) {}

    public function handle(int $productId, float $delta, AdjustmentType $type, ?string $note, int $adminUserId): StockAdjustment
    {
        return DB::transaction(function () use ($productId, $delta, $type, $note, $adminUserId) {
            $product = $this->products->findByIdForUpdate($productId); // lockForUpdate
            $stockBefore = $product->stockQuantity;
            $stockAfter = $stockBefore + $delta;

            if ($stockAfter < 0) {
                throw new InsufficientStockException($stockBefore, $delta);
            }

            $this->products->updateStock($productId, $stockAfter);
            return $this->adjustments->create(/*...*/);
        });
    }
}
```

### Pattern 3: Eloquent Model with PHPStan Generics Annotation
```php
// Source: app/Infrastructure/Persistence/Eloquent/Models/UserModel.php pattern
/** @use HasFactory<ProductModelFactory> */
use HasFactory;

// For relationships, explicit return types required:
/** @return \Illuminate\Database\Eloquent\Relations\HasMany<ProductImageModel, $this> */
public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(ProductImageModel::class);
}
```

PHPStan level 6 requires typed relationship return types and generic factory annotations. Larastan 3.x provides stubs for common Eloquent generics.

### Pattern 4: S3 Upload + Thumbnail (synchronous)
```php
// Intervention Image 3.x API
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

$manager = new ImageManager(new Driver());
$image = $manager->read($uploadedFile->getRealPath());

// Resize to thumbnail (e.g., 400px wide, aspect-preserved)
$thumb = $image->scale(width: 400);

$originalPath = "products/{$productId}/" . $filename;
$thumbPath = "products/{$productId}/thumb_" . $filename;

Storage::disk('s3')->put($originalPath, $image->toJpeg(quality: 85));
Storage::disk('s3')->put($thumbPath, $thumb->toJpeg(quality: 75));
```

**Note:** Intervention Image 3.x uses `ImageManager::read()` not `make()` (v2 API). The v3 API is a full rewrite from v2.

### Pattern 5: Single Primary Image Enforcement
When setting an image as primary (`is_primary = true`), use a DB transaction to first clear all `is_primary = true` on the product, then set the target image. This prevents race conditions with multiple concurrent admin uploads.

```sql
BEGIN;
UPDATE product_images SET is_primary = false WHERE product_id = ?;
UPDATE product_images SET is_primary = true WHERE id = ?;
COMMIT;
```

### Pattern 6: Category Max-Depth Validation (Application Layer)
```php
// In CreateCategoryAction::handle()
// D-06: enforce max depth 2 at application layer

if ($parentId !== null) {
    $parent = $this->categories->findById($parentId);
    if ($parent === null) {
        throw new CategoryNotFoundException($parentId);
    }
    if ($parent->parentId !== null) {
        // Parent is already a child — creating here would be depth 3
        throw new CategoryDepthExceededException();
    }
}
```

### Pattern 7: Service Provider Binding
```php
// app/Providers/AppServiceProvider.php — add bindings for each new interface:
$this->app->bind(
    \App\Domain\Product\Repositories\ProductRepositoryInterface::class,
    \App\Infrastructure\Persistence\Repositories\EloquentProductRepository::class,
);
// repeat for CategoryRepositoryInterface, StockAdjustmentRepositoryInterface
```

Currently AppServiceProvider has no bindings (Phase 1 bindings are deferred per STATE.md note). All three new interfaces must be bound here.

### Pattern 8: Exception → JSON Envelope
New domain exceptions must be registered in `bootstrap/app.php` inside the `withExceptions()` match block:
```php
$e instanceof \App\Exceptions\Product\InsufficientStockException => [
    422,
    'INSUFFICIENT_STOCK',
    $e->getMessage(),
    (object) [],
],
$e instanceof \App\Exceptions\Product\ProductNotFoundException => [
    404,
    'NOT_FOUND',
    'Sản phẩm không tồn tại.',
    (object) [],
],
```

### Anti-Patterns to Avoid

- **Domain importing Illuminate:** The `NoLaravelImportInDomainRule` PHPStan rule fails CI immediately. Never `use Illuminate\...` in `app/Domain/`.
- **Eloquent in Domain entities:** Domain entities are plain PHP objects. `Product` does NOT extend `Model`.
- **Storing images on local disk:** D-01 locks cloud storage; `local` disk is forbidden for product images.
- **Using Intervention Image v2 API:** `ImageManager::make()` is v2. The v3 API is `ImageManager::read()`. v3 and v2 cannot mix.
- **Missing PHPStan generics on relationships:** `hasMany()` without `@return HasMany<ProductImageModel, $this>` fails level 6.
- **Thumbnail in queue without Wave 0 setup:** The queue driver is `database`; if thumbnail is queued, Wave 0 must ensure `jobs` table exists (it already does from Phase 1 migration). But the Claude's Discretion decision is to keep it synchronous.
- **DECIMAL stored as PHP float:** Pass stock values as strings through the Mapper to avoid float precision loss. Cast the `products.stock_quantity` column to `'decimal:3'` in Eloquent model casts.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Image resize + format conversion | Custom GD wrapper | `intervention/image ^3.11` | GD raw API is 200+ lines; orientation correction, EXIF strip, quality control all handled |
| S3 upload abstraction | Custom S3 client | `Storage::disk('s3')` (Laravel Filesystem + flysystem-aws-s3-v3) | Laravel Filesystem handles retries, streams, URL generation, and test faking with `Storage::fake()` |
| Filterable API query params | Manual `if ($request->has('category_id'))` | `spatie/laravel-query-builder` | Prevents SQL injection in filter params; handles allowed-filter whitelist |
| DECIMAL precision in PHP | `bcmath` calls everywhere | Eloquent cast `'decimal:3'` + pass as string | Cast handles float-to-string conversion; bcmath only needed in domain if arithmetic is complex |

**Key insight:** Image processing is the most deceptively complex problem in this phase. Raw GD code silently mishandles EXIF rotation, progressive JPEGs, and transparency. Intervention Image 3.x solves all of these.

---

## Common Pitfalls

### Pitfall 1: Intervention Image API Confusion (v2 vs v3)
**What goes wrong:** Using `ImageManager::make()` (v2 API) with `intervention/image ^3.x` installed — throws fatal error at runtime.
**Why it happens:** Most Stack Overflow answers and tutorials use v2 examples. v3 is a complete rewrite.
**How to avoid:** Use `ImageManager::read($path)` not `make()`. Construct `ImageManager` with `new Driver()` instance.
**Warning signs:** `BadMethodCallException: make() does not exist` at runtime.

### Pitfall 2: PHPStan Failing on Eloquent Relationships
**What goes wrong:** `hasMany()`, `belongsTo()`, `hasOne()` return types not generic-annotated → PHPStan level 6 errors.
**Why it happens:** Larastan provides generics for base Eloquent, but custom model relationships need explicit `@return` docblocks.
**How to avoid:** Every relationship method must have `/** @return HasMany<RelatedModel, $this> */` annotation.
**Warning signs:** PHPStan `analyse` command errors like "Property ... does not have native type" or "Call to an undefined method on HasMany".

### Pitfall 3: Float Precision Loss on DECIMAL(10,3) Stock
**What goes wrong:** PHP `float` cannot represent all decimal fractions exactly. `1.500` may round to `1.4999999...` after arithmetic.
**Why it happens:** IEEE 754 float limitations. Storing `float` in PHP and reading back DECIMAL from MySQL creates invisible precision errors.
**How to avoid:** Cast `stock_quantity` as `'decimal:3'` in Eloquent model. In domain entity use `float` for arithmetic but be aware of display rounding. For the inventory guard (`stock_after >= 0`), the margin of error is negligible since the DB cast enforces 3dp.
**Warning signs:** Stock shows `0.999` instead of `1.000` in API response.

### Pitfall 4: Race on `is_primary` Flag
**What goes wrong:** Two concurrent requests both try to set `is_primary = true` on different images of the same product. Both read `is_primary=false` on the other, both write `true`, result: two primary images.
**Why it happens:** Non-atomic read-modify-write without a lock.
**How to avoid:** Wrap the clear-and-set in a `DB::transaction()` with a SELECT FOR UPDATE on the `product_images` row or use a single UPDATE statement: `UPDATE product_images SET is_primary = (id = ?) WHERE product_id = ?`.
**Warning signs:** `product_images` table has more than one row with `is_primary=true` per `product_id` (enforce unique partial index as a safeguard: `UNIQUE (product_id)` on a filtered index where `is_primary=true` — MySQL does not support filtered indexes, so enforce in application + add a DB-level check in tests).

### Pitfall 5: Forgetting to Register Exception Classes
**What goes wrong:** New domain exceptions (`InsufficientStockException`, `CategoryDepthExceededException`, etc.) not added to `bootstrap/app.php` match block → fall through to generic 500 SERVER_ERROR response.
**Why it happens:** Exception handler is a manual match block, not auto-discovered.
**How to avoid:** Each new exception class requires a corresponding `match` arm in `bootstrap/app.php`.
**Warning signs:** Test expects 422 INSUFFICIENT_STOCK but gets 500 SERVER_ERROR.

### Pitfall 6: `Storage::disk('s3')` in Tests Without Faking
**What goes wrong:** Tests make real S3 API calls (or fail with `InvalidArgumentException: disk not configured`).
**Why it happens:** S3 credentials not set in `.env.testing`.
**How to avoid:** Always call `Storage::fake('s3')` in Feature tests that exercise upload code paths.
**Warning signs:** Tests hang or fail with AWS credential errors.

### Pitfall 7: AppServiceProvider Missing Interface Bindings
**What goes wrong:** `BindingResolutionException` when controllers are resolved — interfaces have no concrete binding.
**Why it happens:** Phase 1 note in STATE.md shows `RepositoryServiceProvider bind commented out — EloquentAdminUserRepository not created until Plan 02`. The Auth binding was deferred; same pattern applies here.
**How to avoid:** Add all three new interface-to-implementation bindings in `AppServiceProvider::register()` in the same plan that creates the repository classes.
**Warning signs:** `Target [App\Domain\Product\Repositories\ProductRepositoryInterface] is not instantiable`.

---

## Code Examples

### Category Max-Depth Guard (Application Layer)
```php
// Source: Established project pattern + D-06 decision
declare(strict_types=1);

namespace App\Application\Product\Actions;

use App\Domain\Product\Exceptions\CategoryDepthExceededException;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;

final class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
    ) {}

    public function handle(string $name, ?int $parentId): \App\Domain\Product\Entities\Category
    {
        if ($parentId !== null) {
            $parent = $this->categories->findById($parentId);
            if ($parent?->parentId !== null) {
                throw new CategoryDepthExceededException();
            }
        }
        return $this->categories->create($name, $parentId);
    }
}
```

### AdjustStockAction with lockForUpdate
```php
// Source: CLAUDE.md lockForUpdate() pattern + D-08/D-11 decisions
declare(strict_types=1);

public function handle(int $productId, float $delta, AdjustmentType $type, ?string $note, int $adminUserId): StockAdjustment
{
    return \Illuminate\Support\Facades\DB::transaction(function () use ($productId, $delta, $type, $note, $adminUserId) {
        $productModel = ProductModel::lockForUpdate()->findOrFail($productId);
        $stockBefore = (float) $productModel->stock_quantity;
        $stockAfter = round($stockBefore + $delta, 3);

        if ($stockAfter < 0) {
            throw new \App\Domain\Product\Exceptions\InsufficientStockException($stockBefore, $delta);
        }

        $productModel->update(['stock_quantity' => $stockAfter]);

        return $this->adjustments->create(
            productId: $productId,
            adminUserId: $adminUserId,
            delta: $delta,
            type: $type,
            note: $note,
            stockBefore: $stockBefore,
            stockAfter: $stockAfter,
        );
    });
}
```

### Intervention Image 3.x Upload Pattern
```php
// Source: intervention/image v3 official docs
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;

$manager = new ImageManager(new Driver());

// Read from uploaded file
$image = $manager->read($request->file('image')->getRealPath());

$filename = \Illuminate\Support\Str::uuid() . '.jpg';
$originalPath = "products/{$productId}/{$filename}";
$thumbPath    = "products/{$productId}/thumb_{$filename}";

// Original (max 1920px wide, preserve aspect)
$original = $image->scaleDown(width: 1920);
Storage::disk('s3')->put($originalPath, (string) $original->toJpeg(quality: 85));

// Thumbnail (400px wide)
$thumb = $image->scaleDown(width: 400);
Storage::disk('s3')->put($thumbPath, (string) $thumb->toJpeg(quality: 75));

$originalUrl = Storage::disk('s3')->url($originalPath);
$thumbUrl    = Storage::disk('s3')->url($thumbPath);
```

### Public Product List with spatie/laravel-query-builder
```php
// Source: spatie/laravel-query-builder docs
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

$products = QueryBuilder::for(ProductModel::where('is_active', true))
    ->allowedFilters([
        AllowedFilter::exact('category_id'),
    ])
    ->defaultSort('name')
    ->allowedSorts(['name', 'price_vnd'])
    ->with(['category', 'images'])
    ->paginate($request->integer('per_page', 20));
```

### Eloquent Model with Correct PHPStan Annotations
```php
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\ProductModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductModel extends Model
{
    /** @use HasFactory<ProductModelFactory> */
    use HasFactory;

    protected $table = 'products';

    protected $fillable = ['name', 'description', 'price_vnd', 'unit_type', 'category_id', 'stock_quantity', 'is_active'];

    protected function casts(): array
    {
        return [
            'price_vnd'      => 'integer',
            'stock_quantity' => 'decimal:3',
            'is_active'      => 'boolean',
        ];
    }

    /** @return HasMany<ProductImageModel, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id');
    }

    /** @return BelongsTo<CategoryModel, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id');
    }
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Intervention Image v2 (`make()`) | v3 (`ImageManager::read()`, fluent API) | 2023 (v3 stable 2023) | Must use new API — v2 methods throw at runtime on v3 install |
| `Storage::cloud()` helper | `Storage::disk('s3')` explicit | Laravel 9+ | `cloud()` still works but explicit disk name is clearer for multi-disk apps |
| Manual filter query params | `spatie/laravel-query-builder` | Package mature since 2017; v6 for Laravel 12 | Prevents injection, auto-handles pagination |

**Deprecated/outdated:**
- Intervention Image v2 `make()` method: replaced by `read()` in v3. Do not install `^2.x`.
- `intervention/imagick` driver: GD is confirmed present on host PHP (`php -m | grep gd`). GD is sufficient; Imagick not needed.

---

## Open Questions

1. **Cloudflare R2 vs AWS S3 for production**
   - What we know: D-01 locks "S3 or Cloudflare R2". Both use the S3-compatible API. Flysystem-aws-s3-v3 works with R2 by setting `endpoint` and `use_path_style_endpoint=true`.
   - What's unclear: Which one the user intends to use in production; R2 config differs in endpoint setup.
   - Recommendation: Plan uses `Storage::disk('s3')` with env vars. R2 vs S3 is a deployment config choice, not a code change.

2. **spatie/laravel-query-builder PHPStan compatibility**
   - What we know: Package is actively maintained; likely has larastan annotations.
   - What's unclear: Whether `QueryBuilder::for()` chaining satisfies PHPStan level 6 without ignoreErrors.
   - Recommendation: If PHPStan errors appear on query builder calls, add to `ignoreErrors` in `phpstan.neon` for those specific patterns only. This is a known tradeoff with spatie/laravel-query-builder and PHPStan strict mode.

---

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP GD extension | Intervention Image (image resize) | ✓ | Confirmed via `php -m` | Imagick driver (not on host) |
| MySQL | Database | ✓ | Laravel DB configured | — |
| S3/R2 bucket credentials | Image upload (D-01) | Config only | `.env` vars needed | `Storage::fake('s3')` in tests |
| `intervention/image` | Thumbnail generation | ✗ (not in composer.json) | — | Must install: `composer require intervention/image:^3.11` |
| `league/flysystem-aws-s3-v3` | S3 disk driver | ✗ (not in composer.json) | — | Must install: `composer require league/flysystem-aws-s3-v3:^3.32` |
| `spatie/laravel-query-builder` | Public product filtering | ✗ (not in composer.json) | — | Must install: `composer require spatie/laravel-query-builder:^6.3` |

**Missing dependencies with no fallback:**
- `intervention/image` — required for thumbnail generation per D-04; must be installed in Wave 0
- `league/flysystem-aws-s3-v3` — required for `Storage::disk('s3')` per D-01; must be installed in Wave 0
- `spatie/laravel-query-builder` — required for public product filter API per D-13; must be installed in Wave 0

**Missing dependencies with fallback:**
- S3 bucket credentials — tests use `Storage::fake('s3')`; production requires `.env` configuration

---

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 4.x (pestphp/pest ^4.0 in composer.json) |
| Config file | `tests/Pest.php` — `uses(RefreshDatabase::class)->in('Feature')` |
| Quick run command | `php artisan test --filter=Product` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| PROD-01 | Admin creates product with name/price/description/unit_type/category | Feature | `php artisan test --filter=ProductCrudTest` | ❌ Wave 0 |
| PROD-01 | Admin updates product fields | Feature | `php artisan test --filter=ProductCrudTest` | ❌ Wave 0 |
| PROD-01 | Admin deletes product (soft or hard) | Feature | `php artisan test --filter=ProductCrudTest` | ❌ Wave 0 |
| PROD-02 | unit_type accepts only `con` or `kg`; rejects others | Feature | `php artisan test --filter=ProductValidationTest` | ❌ Wave 0 |
| PROD-03 | Admin creates parent category | Feature | `php artisan test --filter=CategoryCrudTest` | ❌ Wave 0 |
| PROD-03 | Admin creates child category under parent | Feature | `php artisan test --filter=CategoryCrudTest` | ❌ Wave 0 |
| PROD-03 | Creating child-of-child rejected (max depth 2) | Feature | `php artisan test --filter=CategoryDepthTest` | ❌ Wave 0 |
| PROD-04 | Admin toggles is_active; inactive product hidden from public list | Feature | `php artisan test --filter=ProductVisibilityTest` | ❌ Wave 0 |
| PROD-05 | Public lists active products (no auth required) | Feature | `php artisan test --filter=PublicProductListTest` | ❌ Wave 0 |
| PROD-05 | Public filters by category_id | Feature | `php artisan test --filter=PublicProductListTest` | ❌ Wave 0 |
| PROD-05 | Public views single product detail | Feature | `php artisan test --filter=PublicProductDetailTest` | ❌ Wave 0 |
| INVT-01 | Stock stored as DECIMAL(10,3); 1.500 stored and returned correctly | Feature | `php artisan test --filter=StockDecimalTest` | ❌ Wave 0 |
| INVT-02 | Admin records nhap_hang adjustment; stock_quantity updated | Feature | `php artisan test --filter=StockAdjustmentTest` | ❌ Wave 0 |
| INVT-02 | Adjustment record has correct stock_before, stock_after, delta, type, note | Feature | `php artisan test --filter=StockAdjustmentTest` | ❌ Wave 0 |
| INVT-03 | Adjustment that would bring stock below 0 is rejected (422) | Feature | `php artisan test --filter=StockAdjustmentTest` | ❌ Wave 0 |
| INVT-04 | Admin views full adjustment history for a product | Feature | `php artisan test --filter=StockHistoryTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter` targeting the relevant test class
- **Per wave merge:** `php artisan test` (full suite)
- **Phase gate:** Full suite green + `php -d memory_limit=512M ./vendor/bin/phpstan analyse` before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Product/ProductCrudTest.php` — covers PROD-01 (create, update, delete)
- [ ] `tests/Feature/Product/ProductValidationTest.php` — covers PROD-02 (unit_type enum validation)
- [ ] `tests/Feature/Product/CategoryCrudTest.php` — covers PROD-03 (CRUD)
- [ ] `tests/Feature/Product/CategoryDepthTest.php` — covers PROD-03 (max depth 2 enforcement)
- [ ] `tests/Feature/Product/ProductVisibilityTest.php` — covers PROD-04 (is_active toggle)
- [ ] `tests/Feature/Product/PublicProductListTest.php` — covers PROD-05 (list + filter + pagination)
- [ ] `tests/Feature/Product/PublicProductDetailTest.php` — covers PROD-05 (detail endpoint)
- [ ] `tests/Feature/Product/StockDecimalTest.php` — covers INVT-01 (DECIMAL precision)
- [ ] `tests/Feature/Product/StockAdjustmentTest.php` — covers INVT-02 + INVT-03
- [ ] `tests/Feature/Product/StockHistoryTest.php` — covers INVT-04

*(Note: Test files are created as part of implementation tasks, not Wave 0. Wave 0 creates migrations, installs packages, and sets up factory stubs. Test files are created alongside the feature they test.)*

---

## Project Constraints (from CLAUDE.md)

All directives from `CLAUDE.md` that apply to this phase:

| Directive | Impact on Phase 2 |
|-----------|-------------------|
| **Tech stack: Laravel 12, PHP 8.3+** | Use Laravel 12 features only; PHP 8.3+ syntax permitted |
| **Clean Architecture** | Domain entities: plain PHP. No Eloquent in Domain. Repositories in Infrastructure. |
| **`declare(strict_types=1)` on every file** | All new PHP files must start with this |
| **`final class` for all implementations** | All new Entities, Actions, Repositories, Mappers, Controllers are `final` |
| **Constructor injection only** | No service locator, no `app()` calls outside bootstrap |
| **PHPStan level 6+** | Relationship generics required; run `phpstan analyse` before each commit |
| **Vietnamese messages** | All `message` keys in validation, exceptions, and responses in Vietnamese |
| **spatie/laravel-data for DTOs** | Product/Category/StockAdjustment data objects use `Data` base class |
| **Pest 3/4 for tests** | All tests use Pest functional syntax with `describe()` + `it()` |
| **S3/cloud storage** | No `local` disk for product images (D-01) |
| **MySQL SELECT FOR UPDATE** | `lockForUpdate()` required in AdjustStockAction |
| **Database queue driver** | No Redis queue; thumbnail processing is synchronous (Claude's Discretion decision) |

---

## Sources

### Primary (HIGH confidence)
- `app/Domain/Auth/Entities/AdminUser.php` — Template entity pattern (verified in codebase)
- `app/Application/Auth/Actions/LoginAdminAction.php` — Template action pattern (verified in codebase)
- `app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php` — Template repository pattern (verified in codebase)
- `app/Infrastructure/Persistence/Mappers/UserMapper.php` — Template mapper pattern (verified in codebase)
- `bootstrap/app.php` — Exception handler pattern with JSON envelope (verified in codebase)
- `phpstan.neon` — PHPStan level 6 configuration + custom domain boundary rule (verified in codebase)
- `composer.json` — Installed packages confirmed: spatie/laravel-data ^4.20, spatie/laravel-permission ^6.0, pestphp/pest ^4.0 (verified)
- Packagist intervention/image: latest stable 3.11.7 (verified 2026-03-28)
- Packagist league/flysystem-aws-s3-v3: latest stable 3.32.0 (verified 2026-03-28)
- `php -m | grep gd` output: GD extension confirmed present on host PHP 8.5.2 (verified)
- `php artisan test` output: 16 tests passing, 0 failures — green baseline confirmed (verified)
- `phpstan analyse` output: No errors at level 6 (verified)

### Secondary (MEDIUM confidence)
- Intervention Image 3.x official docs — `ImageManager::read()` API pattern; GD driver constructor
- CLAUDE.md recommended stack table — intervention/image listed as required for thumbnail generation

### Tertiary (LOW confidence)
- spatie/laravel-query-builder PHPStan compatibility at level 6 — may require ignoreErrors entries; flag for validation during implementation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — packages verified against Packagist, GD confirmed present, existing packages verified in composer.json
- Architecture: HIGH — Phase 1 patterns studied directly from codebase; all new aggregates follow established template
- Pitfalls: HIGH for known PHP/Laravel pitfalls; MEDIUM for Intervention Image 3.x API specifics (based on docs + composer show output)
- PHPStan generics: HIGH — pattern verified in UserModel.php `@use HasFactory<UserModelFactory>` and custom rule confirmed active

**Research date:** 2026-03-28
**Valid until:** 2026-04-28 (stable ecosystem; Intervention Image and Flysystem are mature libraries)
