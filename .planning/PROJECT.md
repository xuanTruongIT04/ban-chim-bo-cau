# Ban Chim Bồ Câu — Laravel Backend

## What This Is

Backend API Laravel cho hệ thống bán gia cầm & thú nuôi của mẹ: chim bồ câu (sống & thịt), gia cầm các loại. Hệ thống hỗ trợ khách tự đặt hàng online và mẹ nhập đơn thủ công qua Zalo/điện thoại. Tất cả giao tiếp qua RESTful API (frontend riêng sẽ tích hợp sau).

## Current State

**Version:** v1.0 MVP — shipped 2026-03-29
**Codebase:** ~7,800 LOC PHP (app + tests), 34 API endpoints, 93 commits
**Tech stack:** Laravel 12, PHP 8.3, MySQL 8.0, Sanctum, Pest 4, PHPStan level 6
**Architecture:** Clean Architecture (Domain / Application / Infrastructure / Presentation)

## Core Value

Mẹ luôn biết còn bao nhiêu hàng và không bao giờ bán quá số lượng thực tế.

## Requirements

### Validated

**Quản lý sản phẩm** — Validated in Phase 2: Product & Inventory
- [x] CRUD sản phẩm: tên, loại (chim sống / thịt / gia cầm), giá, ảnh, mô tả tiếng Việt
- [x] Tồn kho hỗn hợp: theo con (đơn vị) hoặc theo lô/kg tùy sản phẩm
- [x] Quản lý danh mục (category)

**Giỏ hàng & Đặt hàng** — Validated in Phase 3: Orders, Cart & Payments
- [x] Giỏ hàng cho khách online (session/token-based)
- [x] Mẹ nhập đơn thủ công (admin order entry)
- [x] Kiểm tra tồn kho realtime khi đặt — không oversell (dùng DB transaction + lock)
- [x] Ngăn duplicate order (idempotency key hoặc debounce ở API)
- [x] Trạng thái đơn hàng: chờ xác nhận → xác nhận → đang giao → hoàn thành / hủy

**Thanh toán & Giao hàng** — Validated in Phase 3: Orders, Cart & Payments
- [x] Hỗ trợ COD và chuyển khoản ngân hàng (QR tĩnh)
- [x] Ghi nhận hình thức giao hàng: nội tỉnh (tự giao) / ngoại tỉnh (xe khách)
- [x] Địa chỉ giao hàng khách hàng

**Thông báo** — Validated in Phase 3: Orders, Cart & Payments
- [x] Thông báo đơn mới (email, queued, Vietnamese)

**Quản lý & Báo cáo** — Validated in Phase 4: Admin Operations & Docs
- [x] Dashboard đơn hàng cho mẹ (tiếng Việt, đơn giản)
- [x] Lịch sử đơn hàng, lọc theo trạng thái / ngày

**Kỹ thuật** — Validated in Phase 4: Admin Operations & Docs
- [x] API documentation (Scribe — 34 endpoints, browsable HTML docs)

### Active

(Không còn requirement active — tất cả 43 requirements đã validated trong v1.0. Xem v2 requirements trong REQUIREMENTS archive.)

### Out of Scope

- Thanh toán online tích hợp cổng (MoMo, VNPay) — v1 chỉ COD + chuyển khoản thủ công, thêm sau nếu cần
- App mobile native — backend API đủ, frontend/app là dự án riêng
- Multi-vendor / marketplace — chỉ 1 người bán (mẹ)
- Hệ thống loyalty / điểm thưởng — không cần cho quy mô này
- Thú nuôi (chó, mèo, cá) — v1 tập trung gia cầm, mở rộng sau nếu mẹ cần

## Context

- Dự án cá nhân làm cho mẹ — người dùng admin là mẹ, cần UI/UX đơn giản, tiếng Việt hoàn toàn
- Quy mô nhỏ: hộ kinh doanh gia đình, không cần high-availability hay multi-region
- Vấn đề cốt lõi: oversell và duplicate order chưa xảy ra nhưng cần ngăn từ đầu khi hệ thống online
- Hàng tồn kho hỗn hợp: một số sản phẩm bán theo con (bồ câu sống), một số theo lô/kg (thịt)
- Giao hàng 2 loại: nội tỉnh tự giao, ngoại tỉnh gửi xe khách
- Cần API docs rõ ràng để frontend dev (có thể là người khác) tích hợp được

## Constraints

- **Tech Stack**: Laravel (PHP) — bắt buộc, mẹ đã quen hệ sinh thái này
- **Architecture**: Clean Architecture — tách Domain/Application/Infrastructure/Presentation
- **Language**: Tiếng Việt trong messages, validation errors, dashboard labels
- **Scale**: Hộ kinh doanh gia đình — không cần over-engineer cho traffic lớn
- **Database**: MySQL/PostgreSQL — cần hỗ trợ SELECT FOR UPDATE để tránh oversell

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| RESTful API (không Blade full-stack) | Frontend riêng sẽ được build sau, API linh hoạt hơn | ✅ Implemented — 34 endpoints documented |
| Laravel Sanctum cho auth | Đơn giản, đủ dùng cho SPA/mobile, không cần OAuth phức tạp | ✅ Implemented Phase 1 |
| DB transaction + pessimistic lock cho inventory | Cách duy nhất đảm bảo không oversell ở concurrency cao | ✅ Implemented Phase 2 — `DB::transaction` + `lockForUpdate` + `bcadd`/`bccomp` |
| Tồn kho hỗn hợp (unit vs batch) | Thực tế kinh doanh: bồ câu bán theo con, thịt bán theo kg | ✅ Implemented Phase 2 — `DECIMAL(10,3)` + `UnitType` enum (con/kg) |
| Idempotency cho checkout | Ngăn duplicate order khi mạng chậm/retry | ✅ Implemented Phase 3 — `infinitypaul/idempotency-laravel` middleware |
| OrderStatus state machine | Đảm bảo chuyển trạng thái đúng, có back-step 1 bước | ✅ Implemented Phase 3 — PHP enum với `allowedNextStates()` |
| Payment status tách biệt | COD vs chuyển khoản có flow khác nhau | ✅ Implemented Phase 3 — `PaymentStatus` enum riêng biệt |

---

## Evolution

Tài liệu này được cập nhật tại mỗi phase transition và milestone.

**Sau mỗi phase transition:**
1. Requirement không hợp lệ? → Chuyển sang Out of Scope kèm lý do
2. Requirement đã validate? → Chuyển sang Validated kèm phase reference
3. Requirement mới nổi lên? → Thêm vào Active
4. Có quyết định cần ghi lại? → Thêm vào Key Decisions

**Sau mỗi milestone:**
1. Review toàn bộ các section
2. Kiểm tra Core Value — còn đúng không?
3. Audit Out of Scope — lý do còn hợp lệ không?

---
*Last updated: 2026-03-29 after v1.0 milestone completion — 4 phases, 13 plans, 43/43 requirements validated*
