# Phase 4: Admin Operations & Docs - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-29
**Phase:** 04-admin-operations-docs
**Areas discussed:** Dashboard endpoint, Lọc & tìm đơn hàng, Scribe API docs, Thao tác hàng loạt

---

## Dashboard Endpoint

### Q1: Dashboard nên trả về những dữ liệu gì?

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ đơn chờ xử lý | Đúng scope ADMN-01: đếm đơn đang ở trạng thái chờ_xác_nhận | |
| Tổng quan nhiều số liệu | Đơn chờ + đơn hôm nay + đơn chờ thanh toán + sản phẩm hết hàng | |
| Tổng quan + doanh thu | Như trên cộng doanh thu hôm nay/tuần — gần scope creep | |

**User's choice:** Chỉ đơn chờ xử lý
**Notes:** Đơn giản, đúng ADMN-01.

### Q2: Dashboard có nên đếm thêm các trạng thái khác?

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ cho_xac_nhan | Mẹ chỉ cần biết có bao nhiêu đơn mới | |
| Đếm tất cả trạng thái | Count cho mỗi trạng thái: {cho_xac_nhan: 3, xac_nhan: 5, ...} | ✓ |
| Claude quyết định | Tùy Claude chọn | |

**User's choice:** Đếm tất cả trạng thái
**Notes:** FE linh hoạt hơn khi có count cho mọi trạng thái.

---

## Lọc & Tìm Đơn Hàng

### Q1: Filter + search gộp chung hay tách riêng?

| Option | Description | Selected |
|--------|-------------|----------|
| Gộp chung 1 endpoint | GET /orders?filter[status]=...&filter[search]=... Dùng spatie/laravel-query-builder | ✓ |
| Tách endpoint search | GET /orders cho list, GET /orders/search cho tìm kiếm | |

**User's choice:** Gộp chung 1 endpoint
**Notes:** Đơn giản cho FE.

### Q2: Tìm kiếm theo tên/SĐT hoạt động như thế nào?

| Option | Description | Selected |
|--------|-------------|----------|
| Tìm gần đúng (LIKE) | LIKE '%keyword%' trên cả customer_name và customer_phone | ✓ |
| Tìm chính xác theo field | Tách riêng filter[customer_name] và filter[customer_phone] | |
| Claude quyết định | Tùy Claude chọn | |

**User's choice:** Tìm gần đúng (LIKE)
**Notes:** Đủ dùng cho quy mô nhỏ.

### Q3: Sắp xếp mặc định?

| Option | Description | Selected |
|--------|-------------|----------|
| Mới nhất trước | Sort created_at giảm dần. FE override bằng ?sort=... | ✓ |
| Đơn chờ xử lý lên trước | Sort theo status priority rồi ngày | |
| Claude quyết định | Tùy Claude chọn | |

**User's choice:** Mới nhất trước
**Notes:** Tự nhiên nhất cho mẹ.

---

## Scribe API Docs

### Q1: Phạm vi docs?

| Option | Description | Selected |
|--------|-------------|----------|
| Tất cả v1 endpoints | Annotate đầy đủ Auth, Product, Category, Inventory, Cart, Order, Dashboard | ✓ |
| Chỉ endpoints mới Phase 4 | Chỉ dashboard và order list/filter | |
| Claude quyết định | Tùy Claude đánh giá | |

**User's choice:** Tất cả v1 endpoints
**Notes:** FE dev cần docs đầy đủ để tích hợp.

### Q2: Ngôn ngữ mô tả?

| Option | Description | Selected |
|--------|-------------|----------|
| Tiếng Việt | Mô tả endpoint, bodyParam, response đều tiếng Việt | ✓ |
| Tiếng Anh | Docs tiếng Anh cho tính quốc tế | |
| Song ngữ | Tên tiếng Anh + mô tả tiếng Việt | |

**User's choice:** Tiếng Việt
**Notes:** Nhất quán với project constraint.

---

## Thao Tác Hàng Loạt

### Q1: Admin cần thao tác nhiều đơn cùng lúc không?

| Option | Description | Selected |
|--------|-------------|----------|
| Không - từng đơn | Mẹ xử lý từng đơn một. Giữ như hiện tại | ✓ |
| Xác nhận thanh toán hàng loạt | Chỉ batch confirm payment | |
| Batch đầy đủ | Batch confirm + batch update status | |

**User's choice:** Không - từng đơn
**Notes:** Đủ dùng cho quy mô gia đình.

---

## Claude's Discretion

- Dashboard query implementation (raw SQL vs Eloquent groupBy)
- Scribe group organization và thứ tự
- Query builder filter details

## Deferred Ideas

- Batch confirm payment — thêm khi mẹ có nhiều đơn hơn
- Báo cáo doanh thu (ADMN-V2-01) — v2
- Xuất Excel/CSV (ADMN-V2-02) — v2
