# Phase 4: Admin Operations & Docs - Research

**Researched:** 2026-03-29
**Domain:** Laravel admin dashboard endpoint, spatie/laravel-query-builder v7, Scribe v5.9 docblock annotations
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

**Dashboard Endpoint**
- D-01: Dashboard trả về count đơn hàng cho tất cả trạng thái: `{cho_xac_nhan: N, xac_nhan: N, dang_giao: N, hoan_thanh: N, huy: N}`. Đáp ứng ADMN-01 (đơn chờ xử lý) và cho FE linh hoạt hiển thị thêm.
- D-02: Endpoint riêng `GET /api/v1/admin/dashboard` — không gộp vào order list.

**Lọc & Tìm Đơn Hàng**
- D-03: Gộp tất cả filter + search trong 1 endpoint `GET /api/v1/admin/orders`. Dùng `spatie/laravel-query-builder` để xử lý query params.
- D-04: Filter hỗ trợ: `filter[status]` (OrderStatus enum), `filter[date_from]` + `filter[date_to]` (date range), `filter[search]` (LIKE '%keyword%' trên cả customer_name và customer_phone).
- D-05: Sort mặc định: `created_at` giảm dần (đơn mới nhất trước). FE có thể override bằng `?sort=created_at` hoặc `?sort=-created_at`.
- D-06: Pagination chuẩn Laravel (per_page mặc định 20).

**Scribe API Documentation**
- D-07: Cover tất cả v1 endpoints — Auth, Product, Category, Inventory, Cart, Order, Dashboard. Annotate đầy đủ docblocks.
- D-08: Mô tả tiếng Việt — group descriptions, endpoint descriptions, bodyParam descriptions đều tiếng Việt. Nhất quán với project constraint TECH-04.
- D-09: Scribe v5.9 đã cài sẵn + `config/scribe.php` đã có. Chỉ cần bổ sung docblock annotations trên controllers và chạy `php artisan scribe:generate`.

**Thao tác hàng loạt**
- D-10: Không cần batch actions cho v1. Admin xử lý từng đơn một. Các endpoint hiện tại giữ nguyên xử lý single order.

### Claude's Discretion
- Cách implement dashboard query (raw SQL count group by, hay Eloquent groupBy) — tùy Claude
- Cách organize Scribe groups và thứ tự hiển thị — tùy Claude
- Query builder filter implementation details — tùy Claude, miễn dùng spatie/laravel-query-builder

### Deferred Ideas (OUT OF SCOPE)
- Batch confirm payment — có thể thêm khi mẹ có nhiều đơn hơn
- Báo cáo doanh thu (ADMN-V2-01) — v2 requirement
- Xuất Excel/CSV (ADMN-V2-02) — v2 requirement
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| ADMN-01 | Admin có dashboard hiển thị số đơn đang chờ xử lý | Dashboard endpoint với GROUP BY query trên `orders.order_status`; `cho_xac_nhan` count đáp ứng yêu cầu |
| ADMN-02 | Admin có thể lọc danh sách đơn hàng theo trạng thái và ngày | `spatie/laravel-query-builder` v7 với `AllowedFilter::exact('status', 'order_status')` + callback filter cho date range |
| ADMN-03 | Admin có thể tìm đơn hàng theo tên/SĐT khách | `AllowedFilter::callback('search', ...)` với OR LIKE trên `customer_name` và `customer_phone` |
| ADMN-04 | Admin có thể xác nhận thanh toán và cập nhật trạng thái giao hàng từ danh sách đơn | Endpoint `confirmPayment` và `updateStatus` đã tồn tại; chỉ cần thêm `index` list endpoint để admin navigate từ list đến detail action |
</phase_requirements>

---

## Summary

Phase 4 là phase kết thúc v1, bổ sung 2 chức năng mới vào lớp admin: (1) dashboard endpoint và order list endpoint với filter/search/sort/pagination, (2) hoàn thiện Scribe docblocks để generate API docs toàn bộ v1.

Codebase đã có infrastructure hoàn chỉnh từ Phase 3: `OrderModel`, `OrderStatus` enum với 5 cases, `OrderResource`, `EloquentOrderRepository`, `OrderRepositoryInterface`. Phase 4 chủ yếu là **extension** — thêm 2 method vào repository interface, implement chúng trong Eloquent layer, tạo 1 controller mới (DashboardController), thêm `index()` vào OrderController, và bổ sung Scribe annotations lên toàn bộ controllers.

Không có migration mới, không có domain entity mới, không có package cài thêm. `spatie/laravel-query-builder` v7.0.1 đã có sẵn trong vendor. Scribe v5.9.0 đã cài và `config/scribe.php` đã được cấu hình đầy đủ.

**Primary recommendation:** Implement `listWithFilters()` trực tiếp trong `EloquentOrderRepository` dùng `QueryBuilder::for(OrderModel::class)` với `allowedFilters` + `allowedSorts`, trả về `LengthAwarePaginator`. Dashboard dùng Eloquent `groupBy('order_status')->selectRaw('order_status, count(*) as total')` — một query duy nhất, không cần loop.

## Standard Stack

### Core (đã cài, không cần install thêm)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| spatie/laravel-query-builder | 7.0.1 | Filter/sort/paginate order list từ query params | Đã trong CLAUDE.md + vendor; v7 released 2026-03-16 |
| knuckleswtf/scribe | 5.9.0 | Generate API docs HTML + OpenAPI + Postman | Đã cài + configured; chỉ cần thêm annotations |
| Laravel Eloquent (bundled) | Laravel 12.56 | ORM cho dashboard GROUP BY query | groupBy + selectRaw là built-in |
| Laravel API Resources (bundled) | — | OrderResource đã có, pagination wrapper tự động | |

### No New Packages Required

Phase 4 không cần `composer require` bất kỳ package nào. Tất cả dependencies đã có sẵn.

## Architecture Patterns

### Recommended Project Structure (additions only)

```
app/
├── Application/Admin/Actions/
│   └── GetDashboardStatsAction.php   # NEW: 1 action = 1 use case
├── Domain/Admin/
│   └── (không cần — dashboard là query, không có domain logic phức tạp)
├── Domain/Order/Repositories/
│   └── OrderRepositoryInterface.php  # ADD: listWithFilters(), countByStatus()
├── Infrastructure/Persistence/Repositories/
│   └── EloquentOrderRepository.php   # ADD: implement 2 new methods
├── Presentation/Http/Controllers/Admin/
│   ├── DashboardController.php       # NEW
│   └── OrderController.php           # ADD: index() method
└── Presentation/Http/Resources/
    └── OrderResource.php             # Reuse as-is (no changes needed)
```

### Pattern 1: Dashboard Query — Eloquent GROUP BY

**What:** Đếm số đơn theo từng trạng thái trong 1 SQL query
**When to use:** Khi cần aggregate counts theo enum values

```php
// In EloquentOrderRepository::countByStatus()
// Source: Laravel 12.x docs — Eloquent groupBy + selectRaw
$rows = OrderModel::query()
    ->selectRaw('order_status, COUNT(*) as total')
    ->groupBy('order_status')
    ->pluck('total', 'order_status');

// Map to all statuses (including zeros for statuses with no orders)
$result = [];
foreach (OrderStatus::cases() as $status) {
    $result[$status->value] = (int) ($rows[$status->value] ?? 0);
}
return $result;
```

**Why this approach:** 1 query thay vì 5 queries; `pluck('total', 'order_status')` returns keyed collection; explicit loop qua `OrderStatus::cases()` đảm bảo tất cả 5 statuses luôn có trong response kể cả khi count = 0.

### Pattern 2: spatie/laravel-query-builder v7 — AllowedFilter

**What:** Translate `?filter[status]=cho_xac_nhan&filter[search]=nguyen` thành Eloquent query
**When to use:** Order list endpoint với multiple filter params

```php
// In EloquentOrderRepository::listWithFilters() — v7 API (verified from vendor source)
// Source: vendor/spatie/laravel-query-builder/src/AllowedFilter.php
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

$query = QueryBuilder::for(OrderModel::class)
    ->allowedFilters([
        AllowedFilter::exact('status', 'order_status'),
        AllowedFilter::callback('search', function (Builder $query, string $value): void {
            $query->where(function (Builder $q) use ($value): void {
                $q->where('customer_name', 'LIKE', "%{$value}%")
                  ->orWhere('customer_phone', 'LIKE', "%{$value}%");
            });
        }),
        AllowedFilter::callback('date_from', function (Builder $query, string $value): void {
            $query->whereDate('created_at', '>=', $value);
        }),
        AllowedFilter::callback('date_to', function (Builder $query, string $value): void {
            $query->whereDate('created_at', '<=', $value);
        }),
    ])
    ->allowedSorts([
        AllowedSort::field('created_at'),
    ])
    ->defaultSort('-created_at')
    ->with('items');

return $query->paginate(perPage: 20);
```

**Important v7 note:** `AllowedFilter::callback()` signature is `callback(string $name, callable $callback, ?string $internalName = null)` — verified from `vendor/spatie/laravel-query-builder/src/AllowedFilter.php`. The `FiltersCallback` class accepts the closure and calls it with `(Builder $query, mixed $value, string $property)`.

### Pattern 3: Repository Interface Extension

**What:** Thêm 2 methods mới vào `OrderRepositoryInterface` mà không break existing code

```php
// In OrderRepositoryInterface — additions only
interface OrderRepositoryInterface
{
    // ... existing methods ...

    /**
     * @return \Illuminate\Pagination\LengthAwarePaginator<OrderModel>
     */
    public function listWithFilters(): \Illuminate\Pagination\LengthAwarePaginator;

    /**
     * @return array<string, int> keyed by OrderStatus::value
     */
    public function countByStatus(): array;
}
```

**Note on Clean Architecture:** `listWithFilters()` trả về `LengthAwarePaginator<OrderModel>` (Eloquent model), không phải domain entity. Đây là quyết định thực dụng: list endpoint chỉ dùng cho Presentation layer (render JSON), không có domain logic. Cân nhắc: nếu giữ strict CA thì phải map sang domain entities, nhưng cho phase này trả thẳng `OrderModel` từ repository là đủ — tương tự pattern đã dùng trong `ProductController::index()` (line 36 trong ProductController.php: `ProductModel::with([...])->paginate(20)`).

### Pattern 4: DashboardController

**What:** Controller đơn giản, không có Form Request validation (không có body params)

```php
// app/Presentation/Http/Controllers/Admin/DashboardController.php
/**
 * @group Admin > Dashboard
 *
 * Tổng quan đơn hàng cho admin
 */
final class DashboardController
{
    public function __construct(
        private readonly GetDashboardStatsAction $action,
    ) {}

    /**
     * Dashboard tổng quan đơn hàng
     *
     * Trả về số đơn hàng theo từng trạng thái.
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "orders_by_status": {
     *       "cho_xac_nhan": 5,
     *       "xac_nhan": 3,
     *       "dang_giao": 2,
     *       "hoan_thanh": 10,
     *       "huy": 1
     *     }
     *   }
     * }
     */
    public function index(): JsonResponse
    {
        $stats = $this->action->handle();

        return response()->json([
            'success' => true,
            'data'    => ['orders_by_status' => $stats],
        ]);
    }
}
```

### Pattern 5: OrderController::index() — List with Pagination

**What:** Thêm `index()` method vào OrderController hiện có; inject repository vào controller đã có

```php
/**
 * Danh sách đơn hàng (có filter/search/pagination)
 *
 * @queryParam filter[status] string Lọc theo trạng thái: cho_xac_nhan, xac_nhan, dang_giao, hoan_thanh, huy. Example: cho_xac_nhan
 * @queryParam filter[search] string Tìm theo tên hoặc SĐT khách. Example: Nguyễn
 * @queryParam filter[date_from] string Lọc từ ngày (YYYY-MM-DD). Example: 2026-01-01
 * @queryParam filter[date_to] string Lọc đến ngày (YYYY-MM-DD). Example: 2026-12-31
 * @queryParam sort string Sắp xếp: created_at (tăng dần) hoặc -created_at (giảm dần). Example: -created_at
 * @queryParam page integer Trang. Example: 1
 * @queryParam per_page integer Số bản ghi mỗi trang (mặc định 20). Example: 20
 *
 * @response 200 {"success": true, "data": [...], "meta": {...}, "links": {...}}
 */
public function index(): JsonResponse
{
    $paginator = $this->orders->listWithFilters();

    return response()->json([
        'success' => true,
        'data'    => OrderResource::collection($paginator),
        'meta'    => [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ],
        'links' => [
            'first' => $paginator->url(1),
            'last'  => $paginator->url($paginator->lastPage()),
            'prev'  => $paginator->previousPageUrl(),
            'next'  => $paginator->nextPageUrl(),
        ],
    ]);
}
```

**Note:** `OrderResource` wraps domain `Order` entity, nhưng `listWithFilters()` trả về `LengthAwarePaginator<OrderModel>`. Hai lựa chọn:
- Option A: Wrap `OrderModel` directly — cần tạo `OrderListResource` mixin `OrderModel`, hoặc
- Option B: Map `OrderModel` sang domain entity trong repository trước khi trả về — consistent với existing `findById()` pattern

**Recommendation:** Option B — map sang domain entity trong repository, dùng `OrderMapper::toDomain($model)` trong loop. `OrderResource` wraps `Order` entity như đã làm ở tất cả endpoints khác. Consistent, không cần resource class mới.

### Pattern 6: Scribe Docblock Annotations

**What:** Annotate tất cả v1 controllers để Scribe generate đầy đủ docs
**Existing pattern (từ OrderController.php):** Class-level `@group`, method-level `@bodyParam`, `@queryParam`, `@response`

Scribe v5.9 supported annotation format (verified từ `config/scribe.php` strategies):

```php
/**
 * @group Admin > Orders
 *
 * Mô tả group tiếng Việt
 */
class SomeController
{
    /**
     * Tên endpoint tiếng Việt
     *
     * Mô tả chi tiết.
     *
     * @queryParam filter[status] string Lọc theo trạng thái. Example: cho_xac_nhan
     * @bodyParam field_name type required|optional Mô tả tiếng Việt. Example: value
     * @response 200 {"success": true, "data": {...}}
     * @response 422 {"code": "VALIDATION_ERROR", "message": "..."}
     * @unauthenticated  (cho public endpoints)
     */
    public function method(): JsonResponse { ... }
}
```

**Scribe groups order** (recommend for `config/scribe.php` `groups.order`):
```php
'order' => [
    'Auth',
    'Admin > Dashboard',
    'Admin > Orders',
    'Admin > Sản phẩm',
    'Admin > Danh mục',
    'Admin > Tồn kho',
    'Public > Sản phẩm',
    'Public > Giỏ hàng',
    'Public > Checkout',
],
```

### Anti-Patterns to Avoid

- **Không dùng `Auth::id()` hay `auth()->id()` trong repository** — repository không được phụ thuộc vào request context. `listWithFilters()` phải không có side effects từ auth.
- **Không gọi `QueryBuilder::for()` trong Application layer** — spatie QueryBuilder là Infrastructure concern (Eloquent-aware). Giữ trong `EloquentOrderRepository`, không rò ra Application.
- **Không dùng raw `->paginate()` với `->orderBy()` chồng lên `->defaultSort()`** — `defaultSort('-created_at')` của QueryBuilder xử lý sort; thêm `->orderBy()` thủ công có thể conflict.
- **Không dùng `@noauthentication` sai** — Scribe dùng `@unauthenticated` (không phải `@noauthentication`) cho public endpoints. Sai annotation thì docs hiển thị sai auth requirement.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Filter/sort từ query params | Manual `if ($request->has(...)) $query->where(...)` | `spatie/laravel-query-builder` `AllowedFilter` | Đã cài, handles injection prevention, whitelist-based |
| Multi-field LIKE search | Raw SQL string concatenation | `AllowedFilter::callback()` với closure | Type-safe, testable, consistent với query builder API |
| Pagination metadata | Manual count queries | `LengthAwarePaginator` methods (`total()`, `lastPage()`, etc.) | Built-in, correct, no extra queries |
| API documentation | Manual markdown file | Scribe docblocks + `php artisan scribe:generate` | Auto-generates từ code, stays in sync |
| Status count aggregation | 5 separate `->where('order_status', X)->count()` queries | Single `GROUP BY order_status` query | 1 query vs 5 queries |

**Key insight:** spatie/laravel-query-builder v7 `AllowedFilter::callback()` là đúng tool cho `filter[search]` (multi-field LIKE) và `filter[date_from]`/`filter[date_to]` (date range) vì không có built-in filter type cho những cases này.

## Common Pitfalls

### Pitfall 1: OrderResource wraps Order entity, bukan OrderModel

**What goes wrong:** `listWithFilters()` trả `LengthAwarePaginator<OrderModel>`, nhưng `OrderResource` dùng `/** @mixin Order */` và cast về domain `Order` entity ở line `$order = $this->resource`. PHP runtime sẽ không error ngay (dynamic properties), nhưng PHPStan level 6 sẽ catch mismatch.

**Why it happens:** Repository pattern mix — `findById()` maps sang domain entity, `listWithFilters()` trả thẳng `OrderModel` (lazy mapping).

**How to avoid:** Map `OrderModel` sang domain entity trong `listWithFilters()` trước khi trả về — dùng `OrderMapper::toDomain($model)` trong collection map. Consistent với tất cả existing repository methods.

**Warning signs:** PHPStan error về property access on `OrderModel` through `OrderResource` `@mixin Order`.

### Pitfall 2: spatie/laravel-query-builder `filter[date_from]` là custom filter name

**What goes wrong:** `filter[date_from]` và `filter[date_to]` không map trực tiếp sang column name `created_at`. Nếu dùng `AllowedFilter::exact('date_from')` thay vì `callback`, query builder sẽ tìm column `date_from` không tồn tại.

**Why it happens:** Mismatch giữa API parameter name và database column name.

**How to avoid:** Dùng `AllowedFilter::callback('date_from', ...)` với closure gọi `->whereDate('created_at', '>=', $value)`.

### Pitfall 3: `defaultSort` syntax trong QueryBuilder v7

**What goes wrong:** `->defaultSort('created_at')` sorts ascending (oldest first). Admin cần newest first.

**How to avoid:** `->defaultSort('-created_at')` — dấu `-` prefix = descending. Verified từ vendor source `SortsQuery` trait.

### Pitfall 4: Scribe response calls trên non-GET endpoints

**What goes wrong:** `config/scribe.php` có `Strategies\Responses\ResponseCalls::withSettings(only: ['GET *'])` — Scribe chỉ auto-generate response examples cho GET endpoints bằng cách call thật. POST/PATCH/DELETE endpoints cần `@response` docblock manual.

**Why it happens:** Response calls cho mutating endpoints sẽ change DB state trong docs generation context.

**How to avoid:** Tất cả POST/PATCH/DELETE endpoints phải có `@response` docblock với example JSON. GET endpoints có thể bỏ `@response` nếu factory data đủ (Scribe sẽ call endpoint với test DB).

### Pitfall 5: Scribe và factories cho OrderModel

**What goes wrong:** Scribe `examples.models_source: ['factoryCreate', 'factoryMake', 'databaseFirst']` — cần `OrderModelFactory` tồn tại và generate valid data (bao gồm enum values) để GET response calls hoạt động.

**Why it happens:** `OrderModel` có casts về custom enums. Factory phải trả về valid enum values.

**How to avoid:** `OrderModelFactory` đã tồn tại (thấy trong `OrderModel.php` `HasFactory<OrderModelFactory>`). Verify factory generates valid `order_status` enum string.

### Pitfall 6: Route placement — `GET /admin/orders` trước `GET /admin/orders/{order}`

**What goes wrong:** Nếu đặt `Route::get('/orders/{order}', ...)` trước `Route::get('/orders', ...)`, Laravel route matching không bị ảnh hưởng (literal `/orders` không match `{order}` wildcard). Nhưng nếu dùng `Route::apiResource` sau đó add manual route, thứ tự có thể tạo confusion.

**How to avoid:** Không dùng `Route::apiResource` cho orders (existing code đã dùng manual routes). Thêm `Route::get('/orders', [OrderController::class, 'index'])` vào đầu admin orders group, trước `GET /orders/{order}`.

## Code Examples

### Dashboard Response Format

```json
// GET /api/v1/admin/dashboard
{
  "success": true,
  "data": {
    "orders_by_status": {
      "cho_xac_nhan": 5,
      "xac_nhan": 3,
      "dang_giao": 2,
      "hoan_thanh": 47,
      "huy": 8
    }
  }
}
```

### Order List Response Format (paginated)

```json
// GET /api/v1/admin/orders?filter[status]=cho_xac_nhan&sort=-created_at&page=1
{
  "success": true,
  "data": [
    {
      "id": 12,
      "customer_name": "Nguyễn Văn A",
      "customer_phone": "0901234567",
      "order_status": "cho_xac_nhan",
      "order_status_label": "Chờ xác nhận",
      "payment_status": "chua_thanh_toan",
      "total_amount": "150000",
      "created_at": "2026-03-29T10:00:00.000000Z",
      "items": [...]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 47
  },
  "links": {
    "first": "http://localhost/api/v1/admin/orders?page=1",
    "last": "http://localhost/api/v1/admin/orders?page=3",
    "prev": null,
    "next": "http://localhost/api/v1/admin/orders?page=2"
  }
}
```

### GetDashboardStatsAction (Application layer)

```php
// app/Application/Admin/Actions/GetDashboardStatsAction.php
final class GetDashboardStatsAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /** @return array<string, int> */
    public function handle(): array
    {
        return $this->orders->countByStatus();
    }
}
```

### Route additions to routes/api.php

```php
// Inside Route::middleware('auth:sanctum')->prefix('admin')->... group
// ADD before existing order routes:
Route::get('/orders', [OrderController::class, 'index'])
    ->name('orders.index');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard.index');
```

## Environment Availability

Step 2.6: SKIPPED — Phase 4 là code/config-only changes. Không có external tool, service, CLI utility, hay database migration mới. spatie/laravel-query-builder v7.0.1 và knuckleswtf/scribe v5.9.0 đã có trong vendor.

**Verified installed:**
- PHP 8.5.2 (tại `/usr/bin/php` trên dev machine)
- Laravel 12.56.0
- spatie/laravel-query-builder 7.0.1 (released 2026-03-16)
- knuckleswtf/scribe 5.9.0 (released 2026-03-21)
- `config/scribe.php` đã configured với correct routes prefix `api/*`

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 3.x (via PHPUnit 12) |
| Config file | `tests/Pest.php` — `uses(RefreshDatabase::class)->in('Feature')` |
| Quick run command | `php artisan test --filter="Dashboard\|AdminOrderList"` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ADMN-01 | Dashboard returns count for all 5 statuses | Feature | `php artisan test --filter="DashboardTest"` | ❌ Wave 0 |
| ADMN-01 | Dashboard count = 0 for status with no orders | Feature | `php artisan test --filter="DashboardTest"` | ❌ Wave 0 |
| ADMN-02 | Filter orders by status returns only matching orders | Feature | `php artisan test --filter="AdminOrderListTest"` | ❌ Wave 0 |
| ADMN-02 | Filter by date_from + date_to returns orders in range | Feature | `php artisan test --filter="AdminOrderListTest"` | ❌ Wave 0 |
| ADMN-03 | Search by customer_name finds order | Feature | `php artisan test --filter="AdminOrderListTest"` | ❌ Wave 0 |
| ADMN-03 | Search by customer_phone finds order | Feature | `php artisan test --filter="AdminOrderListTest"` | ❌ Wave 0 |
| ADMN-04 | Order list is paginated (default 20 per page) | Feature | `php artisan test --filter="AdminOrderListTest"` | ❌ Wave 0 |
| ADMN-04 | Unauthenticated request to list/dashboard returns 401 | Feature | `php artisan test --filter="AdminOrderListTest\|DashboardTest"` | ❌ Wave 0 |
| TECH-05 | `php artisan scribe:generate` exits 0, produces /docs | Smoke | Manual / CI | ❌ Wave 0 (manual) |

### Sampling Rate
- **Per task commit:** `php artisan test --filter="Dashboard\|AdminOrderList"`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Feature/Admin/DashboardTest.php` — covers ADMN-01
- [ ] `tests/Feature/Admin/AdminOrderListTest.php` — covers ADMN-02, ADMN-03, ADMN-04

*(No framework gaps — Pest + RefreshDatabase already configured in `tests/Pest.php`)*

## Project Constraints (from CLAUDE.md)

| Directive | Impact on Phase 4 |
|-----------|-------------------|
| Clean Architecture: Domain không phụ thuộc Laravel | `countByStatus()` và `listWithFilters()` implement ở Infrastructure (`EloquentOrderRepository`), interface ở Domain (no Laravel imports) |
| Language: Tiếng Việt trong messages, labels | Scribe `@group`, `@bodyParam`, `@response` descriptions đều tiếng Việt (D-08) |
| spatie/laravel-query-builder | Bắt buộc dùng cho order list filter (D-03) — không hand-roll |
| Scribe | Đã trong stack từ Phase 3 (TECH-05); phase này hoàn thiện annotations |
| MySQL SELECT FOR UPDATE | Không liên quan phase 4 (không có new inventory operations) |
| PHPStan level 6+ | `countByStatus()` return type `array<string, int>`, `listWithFilters()` return type phải annotated đúng để PHPStan pass |

## State of the Art

| Old Approach | Current Approach | Notes |
|--------------|------------------|-------|
| spatie/laravel-query-builder v6.3 (in CLAUDE.md) | v7.0.1 (installed) | Breaking change: none found for AllowedFilter API used here; public API stable |
| Scribe v4.x (in CLAUDE.md) | v5.9.0 (installed) | `config/scribe.php` đã được generate cho v5; dùng new `Knuckles\Scribe\Config\*` namespace |

**Version discrepancy note:** CLAUDE.md mentions `spatie/laravel-query-builder ^6.3` và `Scribe ^4.x`, nhưng installed versions là v7.0.1 và v5.9.0. Đây là versions thực tế trong project — research dựa trên installed versions.

## Open Questions

1. **PHPStan compatibility với QueryBuilder return type**
   - What we know: `QueryBuilder::for(OrderModel::class)->paginate()` trả về `LengthAwarePaginator<OrderModel>`. `OrderRepositoryInterface::listWithFilters()` sẽ cần return type annotation.
   - What's unclear: PHPStan level 6 có infer generic type parameter đúng không cho `LengthAwarePaginator` trả từ QueryBuilder method chain.
   - Recommendation: Annotate return type là `\Illuminate\Contracts\Pagination\LengthAwarePaginator` nếu generic gây issue, hoặc dùng `/** @return LengthAwarePaginator<int, Order> */` sau map.

2. **Scribe response calls cho `GET /admin/orders` (authenticated)**
   - What we know: Scribe response calls chỉ làm GET, và config có auth token via `SCRIBE_AUTH_KEY` env var.
   - What's unclear: Liệu SCRIBE_AUTH_KEY có được set trong `.env` hay không — nếu không thì response call sẽ return 401 thay vì 200.
   - Recommendation: Thêm `@response` docblock manual cho `GET /admin/orders` để đảm bảo docs có example kể cả khi auth token không available trong generate context.

## Sources

### Primary (HIGH confidence)
- vendor/spatie/laravel-query-builder/src/AllowedFilter.php — verified `callback()` method signature, all filter types available in v7.0.1
- vendor/spatie/laravel-query-builder/src/Filters/FiltersCallback.php — verified `__invoke(Builder $query, mixed $value, string $property)` signature
- config/scribe.php — verified Scribe v5.9 configuration, strategies, auth setup
- app/Presentation/Http/Controllers/Admin/OrderController.php — existing docblock pattern for Scribe annotations
- app/Domain/Order/Enums/OrderStatus.php — 5 enum cases for dashboard counts
- app/Infrastructure/Persistence/Repositories/EloquentOrderRepository.php — existing repository pattern to follow
- `composer show spatie/laravel-query-builder` — v7.0.1 confirmed installed
- `composer show knuckleswtf/scribe` — v5.9.0 confirmed installed

### Secondary (MEDIUM confidence)
- app/Presentation/Http/Controllers/Admin/ProductController.php — `@group Admin > Sản phẩm` pattern for Scribe group naming

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified via `composer show` against installed versions
- Architecture patterns: HIGH — patterns derived from inspecting existing codebase (EloquentOrderRepository, OrderController, Scribe config)
- Pitfalls: HIGH — derived from actual code inspection (OrderResource type, Scribe config strategies, QueryBuilder v7 source)
- Test gaps: HIGH — tests directory fully scanned, ADMN-* tests confirmed absent

**Research date:** 2026-03-29
**Valid until:** 2026-04-28 (stable ecosystem — Laravel 12, spatie QB v7, Scribe v5 all have stable APIs)
