# Phase 4: Admin Operations & Docs - Context

**Gathered:** 2026-03-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Admin có dashboard endpoint hiển thị tổng quan đơn hàng theo trạng thái, endpoint lọc/tìm kiếm đơn hàng (theo status, date range, tên/SĐT khách), và API documentation đầy đủ qua Scribe cho frontend dev tích hợp.

Không bao gồm: báo cáo doanh thu (ADMN-V2-01), xuất Excel (ADMN-V2-02), batch actions, cổng thanh toán online.

</domain>

<decisions>
## Implementation Decisions

### Dashboard Endpoint

- **D-01:** Dashboard trả về count đơn hàng cho **tất cả trạng thái**: `{cho_xac_nhan: N, xac_nhan: N, dang_giao: N, hoan_thanh: N, huy: N}`. Đáp ứng ADMN-01 (đơn chờ xử lý) và cho FE linh hoạt hiển thị thêm.
- **D-02:** Endpoint riêng `GET /api/v1/admin/dashboard` — không gộp vào order list.

### Lọc & Tìm Đơn Hàng

- **D-03:** Gộp tất cả filter + search trong 1 endpoint `GET /api/v1/admin/orders`. Dùng `spatie/laravel-query-builder` để xử lý query params.
- **D-04:** Filter hỗ trợ: `filter[status]` (OrderStatus enum), `filter[date_from]` + `filter[date_to]` (date range), `filter[search]` (LIKE '%keyword%' trên cả customer_name và customer_phone).
- **D-05:** Sort mặc định: `created_at` giảm dần (đơn mới nhất trước). FE có thể override bằng `?sort=created_at` hoặc `?sort=-created_at`.
- **D-06:** Pagination chuẩn Laravel (per_page mặc định 20).

### Scribe API Documentation

- **D-07:** Cover **tất cả v1 endpoints** — Auth, Product, Category, Inventory, Cart, Order, Dashboard. Annotate đầy đủ docblocks.
- **D-08:** Mô tả tiếng Việt — group descriptions, endpoint descriptions, bodyParam descriptions đều tiếng Việt. Nhất quán với project constraint TECH-04.
- **D-09:** Scribe v5.9 đã cài sẵn + `config/scribe.php` đã có. Chỉ cần bổ sung docblock annotations trên controllers và chạy `php artisan scribe:generate`.

### Thao tác hàng loạt

- **D-10:** Không cần batch actions cho v1. Admin xử lý từng đơn một — đủ dùng cho quy mô gia đình. Các endpoint hiện tại (confirmPayment, updateStatus, cancel) giữ nguyên xử lý single order.

### Claude's Discretion

- Cách implement dashboard query (raw SQL count group by, hay Eloquent groupBy) — tùy Claude
- Cách organize Scribe groups và thứ tự hiển thị — tùy Claude
- Query builder filter implementation details — tùy Claude, miễn dùng spatie/laravel-query-builder

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Foundation
- `.planning/REQUIREMENTS.md` — Requirements ADMN-01..04 là scope chính xác của phase này
- `.planning/ROADMAP.md` §Phase 4 — Success criteria (4 tiêu chí) là định nghĩa "done"
- `CLAUDE.md` — Tech stack, Clean Architecture constraints, spatie/laravel-query-builder config

### Prior Phase Patterns
- `.planning/phases/03-orders-cart-payments/03-CONTEXT.md` — Order domain decisions (state machine, payment flow)
- `app/Presentation/Http/Controllers/Admin/OrderController.php` — Existing order admin controller (store, show, updateStatus, cancel, confirmPayment, updateDeliveryMethod)
- `app/Domain/Order/Repositories/OrderRepositoryInterface.php` — Cần thêm list/filter/count methods
- `app/Domain/Order/Enums/OrderStatus.php` — 5 trạng thái enum dùng cho dashboard count + filter

### Packages
- `config/scribe.php` — Scribe configuration đã có sẵn
- `spatie/laravel-query-builder` — Đã trong tech stack (CLAUDE.md), dùng cho order list filter/sort

### Existing Infrastructure
- `routes/api.php` — Route structure hiện tại (admin group + public group)
- `app/Infrastructure/Providers/RepositoryServiceProvider.php` — Repository binding pattern

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `OrderController`: Đã có store, show, updateStatus, cancel, confirmPayment, updateDeliveryMethod — chỉ cần thêm `index` (list) và tạo `DashboardController`
- `OrderResource`: Đã có sẵn cho response transformation
- `OrderRepositoryInterface` + `EloquentOrderRepository`: Cần thêm `listWithFilters()` và `countByStatus()`
- `OrderStatus` enum: Đã có cases cho filter và dashboard count
- JSON envelope pattern: `{ success, data, meta }` — áp dụng cho list pagination

### Established Patterns
- Clean Architecture: Domain entities thuần PHP, repos interface, Eloquent ở Infrastructure
- Action pattern: 1 action = 1 use case
- Form Request validation + API Resource transformation
- Scribe docblocks trên OrderController (đã có mẫu chuẩn cho các endpoint hiện tại)

### Integration Points
- `routes/api.php`: Thêm `GET /admin/orders` (list) và `GET /admin/dashboard`
- `OrderController`: Thêm method `index()` cho order list
- Tạo mới `DashboardController` cho dashboard endpoint

</code_context>

<specifics>
## Specific Ideas

- Dashboard count nên dùng 1 query `SELECT status, COUNT(*) FROM orders GROUP BY status` — hiệu quả nhất
- Filter search dùng LIKE trên cả 2 field (customer_name, customer_phone) — UNION hay OR clause tùy Claude
- Scribe cần ví dụ response chính xác cho mọi endpoint — factories generate test data

</specifics>

<deferred>
## Deferred Ideas

- Batch confirm payment — có thể thêm khi mẹ có nhiều đơn hơn
- Báo cáo doanh thu (ADMN-V2-01) — v2 requirement
- Xuất Excel/CSV (ADMN-V2-02) — v2 requirement

</deferred>

---

*Phase: 04-admin-operations-docs*
*Context gathered: 2026-03-29*
