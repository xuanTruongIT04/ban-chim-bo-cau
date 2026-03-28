# Ban Chim Bồ Câu — Laravel Backend

## What This Is

Backend API Laravel cho hệ thống bán gia cầm & thú nuôi của mẹ: chim bồ câu (sống & thịt), gia cầm các loại. Hệ thống hỗ trợ khách tự đặt hàng online và mẹ nhập đơn thủ công qua Zalo/điện thoại. Tất cả giao tiếp qua RESTful API (frontend riêng sẽ tích hợp sau).

## Core Value

Mẹ luôn biết còn bao nhiêu hàng và không bao giờ bán quá số lượng thực tế.

## Requirements

### Validated

(Chưa có — ship để validate)

### Active

**Quản lý sản phẩm**
- [ ] CRUD sản phẩm: tên, loại (chim sống / thịt / gia cầm), giá, ảnh, mô tả tiếng Việt
- [ ] Tồn kho hỗn hợp: theo con (đơn vị) hoặc theo lô/kg tùy sản phẩm
- [ ] Quản lý danh mục (category)

**Giỏ hàng & Đặt hàng**
- [ ] Giỏ hàng cho khách online (session/token-based)
- [ ] Mẹ nhập đơn thủ công (admin order entry)
- [ ] Kiểm tra tồn kho realtime khi đặt — không oversell (dùng DB transaction + lock)
- [ ] Ngăn duplicate order (idempotency key hoặc debounce ở API)
- [ ] Trạng thái đơn hàng: chờ xác nhận → xác nhận → đang giao → hoàn thành / hủy

**Thanh toán & Giao hàng**
- [ ] Hỗ trợ COD và chuyển khoản ngân hàng (QR tĩnh)
- [ ] Ghi nhận hình thức giao hàng: nội tỉnh (tự giao) / ngoại tỉnh (xe khách)
- [ ] Địa chỉ giao hàng khách hàng

**Quản lý & Báo cáo**
- [ ] Dashboard đơn hàng cho mẹ (tiếng Việt, đơn giản)
- [ ] Lịch sử đơn hàng, lọc theo trạng thái / ngày
- [ ] Thông báo đơn mới (ít nhất email hoặc webhook)

**Kỹ thuật**
- [ ] Clean Architecture: Domain / Application / Infrastructure / Presentation tách rõ
- [ ] Authentication: Sanctum (admin) + public API (khách)
- [ ] API documentation (Scribe hoặc Swagger)
- [ ] Unit + Feature tests cho business logic quan trọng (oversell, duplicate)

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
| RESTful API (không Blade full-stack) | Frontend riêng sẽ được build sau, API linh hoạt hơn | — Pending |
| Laravel Sanctum cho auth | Đơn giản, đủ dùng cho SPA/mobile, không cần OAuth phức tạp | — Pending |
| DB transaction + pessimistic lock cho inventory | Cách duy nhất đảm bảo không oversell ở concurrency cao | — Pending |
| Tồn kho hỗn hợp (unit vs batch) | Thực tế kinh doanh: bồ câu bán theo con, thịt bán theo kg | — Pending |

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
*Last updated: 2026-03-28 after initialization*
