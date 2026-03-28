# Phase 3: Orders, Cart & Payments - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-29
**Phase:** 03-orders-cart-payments
**Areas discussed:** Giỏ hàng anonymous, Order state machine, Thông tin checkout, Thanh toán & xác nhận

---

## Giỏ hàng anonymous

| Option | Description | Selected |
|--------|-------------|----------|
| UUID tự sinh | Client gọi POST /cart → server trả về cart_token (UUID). Đơn giản, không cần session. | ✓ |
| Sanctum token (guest) | Tạo Sanctum token cho guest user ẩn danh. Phức tạp hơn nhưng thống nhất với auth flow. | |
| Session-based | Dùng Laravel session ID. Khó scale, không phù hợp API-first. | |

**User's choice:** UUID tự sinh
**Notes:** Phù hợp API-first architecture, không phụ thuộc session

---

| Option | Description | Selected |
|--------|-------------|----------|
| 7 ngày | Giỏ hàng tự xóa sau 7 ngày không hoạt động | ✓ |
| 24 giờ | Xóa nhanh, giữ DB gọn | |
| 30 ngày | Giữ lâu, thuận tiện nhưng tốn DB | |

**User's choice:** 7 ngày

---

| Option | Description | Selected |
|--------|-------------|----------|
| Không giới hạn | Chỉ check tồn kho lúc đặt hàng | ✓ |
| Giới hạn theo tồn kho | Check realtime khi thêm | |
| Giới hạn cố định (99) | Giới hạn cứng tránh abuse | |

**User's choice:** Không giới hạn

---

| Option | Description | Selected |
|--------|-------------|----------|
| Cộng thêm số lượng | Đã có 3, thêm 2 → tổng 5. UX tự nhiên. | ✓ |
| Ghi đè số lượng | Đã có 3, thêm 2 → thay bằng 2. | |

**User's choice:** Cộng thêm số lượng

---

| Option | Description | Selected |
|--------|-------------|----------|
| Không lock giá | Luôn lấy giá hiện tại. Mẹ đổi giá có hiệu lực ngay. | ✓ |
| Lock giá lúc thêm | Lưu giá vào cart item. | |

**User's choice:** Không lock giá

---

| Option | Description | Selected |
|--------|-------------|----------|
| Vẫn hiển thị, cảnh báo | Giỏ show sản phẩm nhưng đánh dấu "hết hàng". Checkout reject. | ✓ |
| Tự động xóa khỏi giỏ | Sản phẩm biến mất. Khách có thể bối rối. | |

**User's choice:** Vẫn hiển thị, cảnh báo

---

## Order State Machine

| Option | Description | Selected |
|--------|-------------|----------|
| 5 trạng thái đủ | cho_xac_nhan → xac_nhan → dang_giao → hoan_thanh / huy | ✓ |
| Thêm 'đang chuẩn bị' | Thêm 1 bước cho mẹ biết hàng đang đóng gói | |

**User's choice:** 5 trạng thái đủ

---

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ admin | Mẹ quyết định hủy. Khách liên hệ qua Zalo/ĐT. | ✓ |
| Cả khách và admin | Khách tự hủy được khi đơn còn đang chờ. | |

**User's choice:** Chỉ admin

---

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ khi chưa giao | Hủy được ở: cho_xac_nhan, xac_nhan. Không hủy khi đang_giao. | |
| Bất kỳ lúc nào trừ hoàn thành | Admin hủy được cả khi đang giao. Linh hoạt hơn. | ✓ |

**User's choice:** Bất kỳ lúc nào trừ hoàn thành

---

| Option | Description | Selected |
|--------|-------------|----------|
| Không cho phép | Chỉ đi tiến, không lùi. Hủy rồi tạo lại nếu cần. | |
| Cho phép lùi 1 bước | Admin quay lại 1 bước nếu nhầm. | ✓ |

**User's choice:** Cho phép lùi 1 bước

---

## Thông tin Checkout

**User's choice (multiselect):** Họ tên (bắt buộc), Số điện thoại (bắt buộc), Địa chỉ giao hàng (bắt buộc)
**Not selected:** Ghi chú đơn

---

| Option | Description | Selected |
|--------|-------------|----------|
| Khách tự chọn | Dropdown nội_tỉnh / ngoại_tỉnh | |
| Admin quyết định sau | Khách chỉ nhập địa chỉ, mẹ chọn hình thức giao | ✓ |

**User's choice:** Admin quyết định sau

---

| Option | Description | Selected |
|--------|-------------|----------|
| Chưa có phí ship | Tổng đơn = tổng tiền SP. Mẹ tự thỏa thuận. | ✓ |
| Phí cố định theo loại giao | Nội tỉnh X đồng, ngoại tỉnh Y đồng | |

**User's choice:** Chưa có phí ship

---

## Thanh toán & xác nhận

| Option | Description | Selected |
|--------|-------------|----------|
| Khác nhau | COD: chưa_thanh_toán. CK: chờ_xác_nhận → đã_thanh_toán. | |
| Giống nhau | Cả hai bắt đầu chưa_thanh_toán. Admin xác nhận khi nhận tiền. | ✓ |

**User's choice:** Giống nhau

---

| Option | Description | Selected |
|--------|-------------|----------|
| Lúc checkout | Khách chọn COD hoặc chuyển khoản khi đặt đơn | ✓ |
| Không chọn, mẹ quyết định | Tất cả đơn mặc định, mẹ xác định sau | |

**User's choice:** Lúc checkout

---

| Option | Description | Selected |
|--------|-------------|----------|
| Có, trả về trong response | Response trả về tên + STK + ngân hàng | ✓ |
| Không, mẹ gửi qua Zalo | Khách đặt đơn, mẹ liên hệ gửi STK sau | |

**User's choice:** Có, trả về trong response

---

| Option | Description | Selected |
|--------|-------------|----------|
| Config/env | Lưu trong .env, chỉ 1 tài khoản | ✓ |
| Database (admin settings) | Lưu DB, admin sửa qua API | |

**User's choice:** Config/env

---

## Claude's Discretion

- Schema chi tiết bảng orders, order_items, carts, cart_items
- State machine implementation (enum + guard method vs package)
- Idempotency implementation (middleware vs inline, có thể dùng infinitypaul/idempotency-laravel)
- Email template layout
- Cart cleanup mechanism

## Deferred Ideas

- Phí giao hàng tính theo loại — v2
- Ghi chú đơn hàng từ khách — khi FE request
- Stock reservation — INVT-V2-02
- QR code động cho chuyển khoản — PAYM-V2-02
