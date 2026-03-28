# Phase 2: Product & Inventory - Context

**Gathered:** 2026-03-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Admin quản lý toàn bộ danh mục sản phẩm (CRUD sản phẩm + quản lý ảnh gallery + danh mục phân cấp + bật/tắt hiển thị) và tồn kho (điều chỉnh thủ công với nhật ký kiểm toán). Khách hàng duyệt sản phẩm đang bán qua API công khai không cần xác thực.

Không bao gồm: giỏ hàng, đặt hàng, trừ tồn kho khi mua (Phase 3).

</domain>

<decisions>
## Implementation Decisions

### Lưu ảnh sản phẩm

- **D-01:** Ảnh sản phẩm lưu trên cloud storage (S3 hoặc Cloudflare R2) — không dùng disk nội bộ
- **D-02:** Mỗi sản phẩm có gallery nhiều ảnh; lưu trong bảng `product_images` riêng với `product_id`, `path`, `is_primary`, `sort_order`
- **D-03:** 1 ảnh chính (is_primary = true) + các ảnh phụ. Chỉ 1 ảnh được is_primary tại một thời điểm
- **D-04:** Backend tự động tạo thumbnail khi upload (dùng Intervention Image hoặc tương đương). Upload file gốc + thumbnail lên S3. Public API trả về cả URL gốc và URL thumbnail

### Cấu trúc danh mục

- **D-05:** Danh mục phân cấp 2 tầng: cha + con (ví dụ: Gia cầm > Chim bồ câu). Không cho phép danh mục con của con
- **D-06:** Bảng `categories` có cột `parent_id` nullable. Enforce max depth 2 ở application layer
- **D-07:** Mỗi sản phẩm thuộc đúng 1 danh mục (foreign key `category_id` trên bảng `products`). Không có many-to-many

### Nhật ký tồn kho

- **D-08:** Điều chỉnh tồn kho ghi theo delta: lưu số lượng thay đổi (ví dụ: +50, -3), không phải giá trị tuyệt đối mới
- **D-09:** Mỗi điều chỉnh phải có `adjustment_type` từ enum: `nhap_hang` / `kiem_ke` / `hu_hong` / `khac`
- **D-10:** Có thêm trường `note` (text, nullable) cho admin ghi chú tự do
- **D-11:** Bảng `stock_adjustments` lưu: `product_id`, `admin_user_id`, `delta`, `adjustment_type`, `note`, `stock_before`, `stock_after`, `created_at`

### API sản phẩm công khai

- **D-12:** Public API  trả về số lượng tồn kho cụ thể để show lên cho người dùng xem
- **D-13:** Public product list hỗ trợ filter theo `category_id`. Sort mặc định theo tên. Pagination chuẩn (per_page mặc định 20)
- **D-14:** Public API chỉ trả về sản phẩm có `is_active = true`

### Claude's Discretion

- Cách implement thumbnail (queue job hay synchronous) — tùy Claude quyết định dựa trên complexity
- Cách validate max depth 2 cho category (middleware, service, hay domain rule) — tùy Claude
- Thứ tự field trong response (API resource shape) — tùy Claude, miễn nhất quán

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Foundation
- `.planning/REQUIREMENTS.md` — Requirements PROD-01..05 và INVT-01..04 là scope chính xác của phase này
- `.planning/ROADMAP.md` §Phase 2 — Success criteria (5 tiêu chí) là định nghĩa "done"
- `CLAUDE.md` — Tech stack, Clean Architecture constraints, PHPStan level 6+

### Pattern Reference (đọc trước khi code)
- `app/Domain/Auth/Entities/AdminUser.php` — Template cho Domain Entity (final class, readonly constructor)
- `app/Application/Auth/Actions/LoginAdminAction.php` — Template cho Application Action
- `app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php` — Template cho Eloquent Repository
- `app/Infrastructure/Persistence/Mappers/UserMapper.php` — Template cho Domain Mapper

### External Packages (cần research)
- spatie/laravel-data — DTO pattern đã trong CLAUDE.md; cần dùng cho Product/Category/StockAdjustment
- Intervention Image — thumbnail generation (chưa cài, cần thêm vào composer)
- Laravel Filesystem S3 driver — đã bundled, cần config

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Domain/Auth/Entities/AdminUser.php` — pattern entity chuẩn: `final class`, `readonly` constructor params
- `app/Application/Auth/Actions/LoginAdminAction.php` — pattern action: constructor inject repository interface, `handle()` method
- `app/Infrastructure/Persistence/Repositories/EloquentAdminUserRepository.php` — pattern repository: implements interface, dùng Eloquent model + Mapper
- `app/Infrastructure/Persistence/Mappers/UserMapper.php` — pattern mapper: static `toDomain()` method

### Established Patterns
- `declare(strict_types=1)` trên mọi file PHP
- `final class` cho tất cả implementations
- Constructor injection (không dùng service locator)
- Domain entities: plain PHP objects, không extend Eloquent
- PHPStan level 6+ — cần generics annotations trên Eloquent models

### Integration Points
- Routes: `routes/api.php` — thêm routes mới vào `/api/v1/admin/` (admin) và `/api/v1/` (public)
- `app/Presentation/Http/Controllers/Admin/` — thư mục controllers admin (hiện trống, chờ Phase 2)
- `app/Presentation/Http/Controllers/Public/` — thư mục controllers public (hiện trống, chờ Phase 2)
- Migrations: tạo mới `products`, `categories`, `product_images`, `stock_adjustments`

</code_context>

<specifics>
## Specific Ideas

- Admin thêm ảnh vào gallery; 1 ảnh được đánh dấu is_primary; thứ tự ảnh phụ theo sort_order
- Adjustment types bằng tiếng Việt trong enum: `nhap_hang`, `kiem_ke`, `hu_hong`, `khac` (tương ứng: nhập hàng, kiểm kê, hư hỏng, khác)
- Danh mục cha: Gia cầm, Chim bồ câu, v.v. — danh mục con nested dưới cha

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 02-product-inventory*
*Context gathered: 2026-03-28*
