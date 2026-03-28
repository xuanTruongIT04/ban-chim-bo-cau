# Requirements: Ban Chim Bồ Câu

**Defined:** 2026-03-28
**Core Value:** Mẹ luôn biết còn bao nhiêu hàng và không bao giờ bán quá số lượng thực tế.

## v1 Requirements

### Authentication (AUTH)

- [ ] **AUTH-01**: Admin (mẹ) có thể đăng nhập bằng email/password qua Sanctum
- [ ] **AUTH-02**: Token admin có thời hạn hết hạn (không vĩnh viễn)
- [ ] **AUTH-03**: Khách hàng mua hàng không cần tài khoản (anonymous cart với token)
- [ ] **AUTH-04**: Admin có thể đăng xuất và hủy token

### Sản phẩm & Danh mục (PROD)

- [ ] **PROD-01**: Admin có thể tạo, sửa, xóa sản phẩm với tên tiếng Việt, giá, mô tả, ảnh
- [ ] **PROD-02**: Mỗi sản phẩm có unit_type: `con` (bán lẻ theo con) hoặc `kg` (bán theo cân/lô)
- [ ] **PROD-03**: Admin có thể tạo và quản lý danh mục (chim bồ câu / gia cầm / khác)
- [ ] **PROD-04**: Admin có thể bật/tắt hiển thị sản phẩm (is_active) — ẩn hàng hết mùa hoặc hết hàng
- [ ] **PROD-05**: Khách có thể xem danh sách và chi tiết sản phẩm đang bán

### Tồn kho (INVT)

- [ ] **INVT-01**: Tồn kho lưu dạng DECIMAL(10,3) — hỗ trợ cả con (integer) và kg (thập phân)
- [ ] **INVT-02**: Admin có thể điều chỉnh tồn kho thủ công (nhập thêm hàng, kiểm kê)
- [ ] **INVT-03**: Hệ thống không bao giờ cho phép tồn kho xuống dưới 0
- [ ] **INVT-04**: Admin có thể xem lịch sử điều chỉnh tồn kho (ai, bao nhiêu, khi nào)

### Giỏ hàng (CART)

- [ ] **CART-01**: Khách có thể thêm sản phẩm vào giỏ hàng (dùng session token, không cần đăng ký)
- [ ] **CART-02**: Giỏ hàng hiển thị số lượng, giá, tổng tiền
- [ ] **CART-03**: Khách có thể cập nhật số lượng hoặc xóa sản phẩm khỏi giỏ
- [ ] **CART-04**: Giỏ hàng KHÔNG trừ tồn kho — chỉ trừ khi đặt hàng thành công

### Đặt hàng (ORDR)

- [ ] **ORDR-01**: Khách có thể đặt hàng từ giỏ; hệ thống kiểm tra và trừ tồn kho trong cùng DB transaction (lockForUpdate)
- [ ] **ORDR-02**: API đặt hàng có idempotency key — không tạo 2 đơn nếu client gửi 2 lần
- [ ] **ORDR-03**: Admin có thể nhập đơn thủ công (cho khách Zalo/điện thoại) — cùng cơ chế lock tồn kho
- [ ] **ORDR-04**: Đơn hàng có 5 trạng thái: `chờ xác nhận → xác nhận → đang giao → hoàn thành` hoặc `hủy`
- [ ] **ORDR-05**: Khi hủy đơn, tồn kho được hoàn lại trong cùng transaction với việc đổi trạng thái
- [ ] **ORDR-06**: Admin có thể xem chi tiết đơn hàng (sản phẩm, số lượng, địa chỉ, trạng thái)
- [ ] **ORDR-07**: Admin có thể cập nhật trạng thái đơn hàng; chuyển trạng thái sai bị từ chối

### Thanh toán (PAYM)

- [ ] **PAYM-01**: Đơn hàng có payment_status riêng biệt: `chưa thanh toán / chờ xác nhận / đã thanh toán`
- [ ] **PAYM-02**: Hỗ trợ COD: payment_status là `chưa thanh toán` cho đến khi mẹ xác nhận sau giao hàng
- [ ] **PAYM-03**: Hỗ trợ chuyển khoản ngân hàng: admin xác nhận thủ công khi nhận tiền
- [ ] **PAYM-04**: Admin có thể xác nhận thanh toán đã nhận

### Giao hàng (DELV)

- [ ] **DELV-01**: Khách nhập địa chỉ giao hàng khi đặt đơn (tên, số điện thoại, địa chỉ)
- [ ] **DELV-02**: Đơn hàng có hình thức giao: `nội tỉnh` (tự giao) hoặc `ngoại tỉnh` (xe khách)

### Thông báo (NOTI)

- [ ] **NOTI-01**: Admin (mẹ) nhận email khi có đơn hàng mới (gửi qua queued job, sau khi transaction commit)
- [ ] **NOTI-02**: Email thông báo bằng tiếng Việt, ghi rõ sản phẩm, số lượng, địa chỉ giao hàng

### Admin Operations (ADMN)

- [ ] **ADMN-01**: Admin có dashboard hiển thị số đơn đang chờ xử lý
- [ ] **ADMN-02**: Admin có thể lọc danh sách đơn hàng theo trạng thái và ngày
- [ ] **ADMN-03**: Admin có thể tìm đơn hàng theo tên/SĐT khách
- [ ] **ADMN-04**: Admin có thể xác nhận thanh toán và cập nhật trạng thái giao hàng từ danh sách đơn

### Kỹ thuật (TECH)

- [ ] **TECH-01**: Clean Architecture: Domain / Application / Infrastructure / Presentation tách rõ ràng; Domain không phụ thuộc Laravel
- [ ] **TECH-02**: API versioned tại `/api/v1/` từ ngày đầu
- [ ] **TECH-03**: Tất cả API error trả về JSON envelope nhất quán (code, message, errors)
- [ ] **TECH-04**: Validation messages, status labels, email notifications hoàn toàn bằng tiếng Việt
- [ ] **TECH-05**: API documentation tự động qua Scribe
- [ ] **TECH-06**: Test coverage cho PlaceOrderAction (concurrent oversell) và idempotency (duplicate order)
- [ ] **TECH-07**: PHPStan level 6+ để enforce layer boundaries

---

## v2 Requirements

### Thông báo nâng cao

- **NOTI-V2-01**: Thông báo Zalo OA cho mẹ khi có đơn mới (cần Zalo Business account)
- **NOTI-V2-02**: SMS cho khách khi trạng thái đơn thay đổi (ESMS.vn hoặc tương tự)
- **NOTI-V2-03**: Khách nhận email khi trạng thái đơn thay đổi (nếu cung cấp email lúc đặt)

### Thanh toán nâng cao

- **PAYM-V2-01**: Tích hợp cổng thanh toán (MoMo, VNPay, ZaloPay)
- **PAYM-V2-02**: QR code động sinh tự động cho từng đơn hàng

### Hàng tồn kho nâng cao

- **INVT-V2-01**: Cảnh báo hàng sắp hết (low stock alert)
- **INVT-V2-02**: Dự phòng tồn kho (stock reservation) — giữ hàng trong giỏ có thời hạn

### Báo cáo

- **ADMN-V2-01**: Báo cáo doanh thu theo ngày/tháng
- **ADMN-V2-02**: Xuất danh sách đơn hàng ra Excel/CSV

### Sản phẩm nâng cao

- **PROD-V2-01**: Biến thể sản phẩm (ví dụ: bồ câu trắng / bồ câu nâu)
- **PROD-V2-02**: Bảng giá theo số lượng (giá sỉ khi mua nhiều)

---

## Out of Scope

| Feature | Reason |
|---------|--------|
| Cổng thanh toán online (MoMo, VNPay) | v1 chỉ COD + chuyển khoản thủ công; thêm vào v2 khi cần |
| Đăng ký tài khoản khách hàng | Quy mô gia đình, khách quen; thêm friction không cần thiết |
| Multi-vendor / marketplace | Chỉ 1 người bán (mẹ) — không áp dụng |
| Loyalty points / voucher | Phức tạp không cần thiết cho quy mô này |
| Thú nuôi (chó, mèo, cá) | v1 tập trung gia cầm; mở rộng sau nếu mẹ cần |
| App mobile native | Backend API đủ; frontend/app là dự án riêng |
| Real-time stock updates (WebSocket) | Polling đủ dùng; không có traffic đủ lớn để cần |

---

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| PROD-01 | Phase 2 | Pending |
| PROD-02 | Phase 2 | Pending |
| PROD-03 | Phase 2 | Pending |
| PROD-04 | Phase 2 | Pending |
| PROD-05 | Phase 2 | Pending |
| INVT-01 | Phase 2 | Pending |
| INVT-02 | Phase 2 | Pending |
| INVT-03 | Phase 2 | Pending |
| INVT-04 | Phase 2 | Pending |
| CART-01 | Phase 3 | Pending |
| CART-02 | Phase 3 | Pending |
| CART-03 | Phase 3 | Pending |
| CART-04 | Phase 3 | Pending |
| ORDR-01 | Phase 3 | Pending |
| ORDR-02 | Phase 3 | Pending |
| ORDR-03 | Phase 3 | Pending |
| ORDR-04 | Phase 3 | Pending |
| ORDR-05 | Phase 3 | Pending |
| ORDR-06 | Phase 3 | Pending |
| ORDR-07 | Phase 3 | Pending |
| PAYM-01 | Phase 3 | Pending |
| PAYM-02 | Phase 3 | Pending |
| PAYM-03 | Phase 3 | Pending |
| PAYM-04 | Phase 3 | Pending |
| DELV-01 | Phase 3 | Pending |
| DELV-02 | Phase 3 | Pending |
| NOTI-01 | Phase 3 | Pending |
| NOTI-02 | Phase 3 | Pending |
| TECH-05 | Phase 3 | Pending |
| ADMN-01 | Phase 4 | Pending |
| ADMN-02 | Phase 4 | Pending |
| ADMN-03 | Phase 4 | Pending |
| ADMN-04 | Phase 4 | Pending |
| TECH-01 | Phase 1 | Pending |
| TECH-02 | Phase 1 | Pending |
| TECH-03 | Phase 1 | Pending |
| TECH-04 | Phase 1 | Pending |
| TECH-06 | Phase 1 | Pending |
| TECH-07 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 43 total
- Mapped to phases: 43/43
- Unmapped: 0

---
*Requirements defined: 2026-03-28*
*Last updated: 2026-03-28 after roadmap creation — all 43 requirements mapped*
