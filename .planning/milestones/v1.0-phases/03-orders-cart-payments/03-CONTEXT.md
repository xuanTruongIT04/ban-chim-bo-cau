# Phase 3: Orders, Cart & Payments - Context

**Gathered:** 2026-03-29
**Status:** Ready for planning

<domain>
## Phase Boundary

Khách hàng (anonymous, không cần tài khoản) có thể thêm sản phẩm vào giỏ hàng, đặt đơn hàng (tồn kho trừ atomic), và chọn hình thức thanh toán. Admin nhập đơn thủ công cho khách Zalo/điện thoại. Đơn hàng theo state machine 5 trạng thái. Payment tracking riêng biệt. Email thông báo đơn mới cho admin.

Không bao gồm: dashboard admin (Phase 4), cổng thanh toán online (v2), stock reservation (v2).

</domain>

<decisions>
## Implementation Decisions

### Giỏ hàng (Cart)

- **D-01:** Cart dùng UUID token — client gọi `POST /cart` → server trả về `cart_token` (UUID). Client gửi kèm token trong header `X-Cart-Token` cho mọi request giỏ hàng. Không dùng session hay Sanctum guest token.
- **D-02:** Giỏ hàng hết hạn sau 7 ngày không hoạt động. Dùng scheduled job hoặc lazy cleanup.
- **D-03:** Không giới hạn số lượng tối đa mỗi sản phẩm trong giỏ. Chỉ check tồn kho lúc đặt hàng.
- **D-04:** Khi thêm sản phẩm đã có trong giỏ → cộng thêm số lượng (ví dụ: có 3, thêm 2 → tổng 5).
- **D-05:** Giỏ hàng KHÔNG lock giá — luôn lấy giá hiện tại của sản phẩm. Mẹ đổi giá là có hiệu lực ngay.
- **D-06:** Khi sản phẩm bị ẩn (is_active=false) mà đang có trong giỏ → vẫn hiển thị nhưng đánh dấu "hết hàng". Khi checkout sẽ reject sản phẩm inactive.
- **D-07:** Giỏ hàng KHÔNG trừ tồn kho — chỉ trừ khi đặt hàng thành công (per CART-04).

### Order State Machine

- **D-08:** 5 trạng thái: `cho_xac_nhan` → `xac_nhan` → `dang_giao` → `hoan_thanh` | `huy`. Enum values dùng snake_case không dấu.
- **D-09:** Chỉ admin (mẹ) được phép hủy đơn. Khách muốn hủy phải liên hệ mẹ qua Zalo/điện thoại.
- **D-10:** Hủy đơn được phép ở bất kỳ trạng thái nào trừ `hoan_thanh`. Khi hủy, tồn kho được hoàn lại trong cùng transaction (per ORDR-05).
- **D-11:** Admin được phép quay ngược (lùi) 1 bước trạng thái (ví dụ: đang_giao → xac_nhan nếu nhầm). Không được lùi quá 1 bước.
- **D-12:** Chuyển trạng thái không hợp lệ (ví dụ: cho_xac_nhan → hoan_thanh) bị reject với error tiếng Việt.

### Thông tin Checkout

- **D-13:** Khách cần nhập khi checkout: họ tên (bắt buộc), số điện thoại (bắt buộc, validate format VN 10 số bắt đầu 0), địa chỉ giao hàng (bắt buộc, free-text).
- **D-14:** Không có trường ghi chú đơn hàng trong v1.
- **D-15:** Admin quyết định hình thức giao hàng (nội_tỉnh / ngoại_tỉnh) sau khi nhận đơn — khách không chọn lúc checkout. Trường `delivery_method` nullable, admin cập nhật sau.
- **D-16:** v1 không tính phí giao hàng. Tổng đơn = tổng tiền sản phẩm. Mẹ tự thỏa thuận ship với khách.

### Thanh toán (Payment)

- **D-17:** Khách chọn phương thức thanh toán lúc checkout: `cod` hoặc `chuyen_khoan`.
- **D-18:** Payment flow giống nhau cho cả 2 phương thức: payment_status bắt đầu = `chua_thanh_toan`. Admin xác nhận khi nhận được tiền → `da_thanh_toan`.
- **D-19:** Khi khách chọn `chuyen_khoan`, API response trả về thông tin tài khoản ngân hàng của mẹ (tên, STK, ngân hàng) để FE hiển thị cho khách.
- **D-20:** Thông tin ngân hàng lưu trong config/env (chỉ 1 tài khoản của mẹ). Không cần lưu DB.

### Idempotency & Atomic Order

- **D-21:** API đặt hàng có header `Idempotency-Key` (UUID) — client gửi lại cùng key sẽ nhận response cũ, không tạo đơn mới (per ORDR-02).
- **D-22:** Khi đặt hàng, toàn bộ flow (check tồn kho → trừ tồn kho → tạo đơn) chạy trong 1 `DB::transaction` với `lockForUpdate` trên từng product row (per ORDR-01). Dùng `bcadd`/`bccomp` cho DECIMAL precision (đã proven ở Phase 2).
- **D-23:** Admin nhập đơn thủ công (cho khách Zalo/phone) dùng cùng cơ chế lock — không có code path nào bypass inventory lock (per ORDR-03).

### Email Notification

- **D-24:** Khi đơn hàng mới được tạo, admin (mẹ) nhận email tiếng Việt liệt kê sản phẩm, số lượng, địa chỉ giao hàng (per NOTI-01, NOTI-02).
- **D-25:** Email được gửi qua queued job, fire sau khi transaction commit. Dùng Laravel database queue driver (đã quyết định ở tech stack).

### Claude's Discretion

- Cấu trúc bảng orders, order_items, carts, cart_items — Claude quyết định schema chi tiết
- Cách implement state machine (enum + guard method, hay dùng package) — tùy Claude
- Cách implement idempotency (middleware hay inline check) — tùy Claude, có thể dùng `infinitypaul/idempotency-laravel` đã mention trong CLAUDE.md
- Email template layout — tùy Claude, miễn tiếng Việt và đủ thông tin
- Cart cleanup mechanism (scheduled command hay lazy delete) — tùy Claude

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Foundation
- `.planning/REQUIREMENTS.md` — Requirements CART-01..04, ORDR-01..07, PAYM-01..04, DELV-01..02, NOTI-01..02, TECH-05 là scope chính xác
- `.planning/ROADMAP.md` §Phase 3 — Success criteria (7 tiêu chí) là định nghĩa "done"
- `CLAUDE.md` — Tech stack, Clean Architecture constraints, packages (infinitypaul/idempotency-laravel cho idempotency)

### Prior Phase Patterns
- `.planning/phases/02-product-inventory/02-CONTEXT.md` — Decision patterns từ Phase 2 (đặc biệt D-08 delta stock, D-11 stock_adjustments schema)
- `app/Application/Product/Actions/AdjustStockAction.php` — Pattern DB::transaction + lockForUpdate + bcadd/bccomp (reuse cho PlaceOrderAction)
- `app/Domain/Product/Repositories/ProductRepositoryInterface.php` — `findByIdForUpdate()` interface đã có sẵn

### Existing Infrastructure
- `routes/api.php` — Route structure hiện tại (admin group + public group)
- `bootstrap/app.php` — Exception handler pattern cho domain exceptions
- `app/Infrastructure/Providers/RepositoryServiceProvider.php` — Repository binding pattern

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AdjustStockAction`: DB::transaction + lockForUpdate + bcadd/bccomp pattern — reuse trực tiếp cho PlaceOrderAction
- `ProductRepositoryInterface::findByIdForUpdate()`: Đã có sẵn, dùng cho lock product rows khi đặt hàng
- `ProductRepositoryInterface::updateStock()`: Đã có sẵn, dùng để trừ tồn kho
- Exception handler pattern trong `bootstrap/app.php`: Thêm domain exceptions mới (OrderNotFoundException, InvalidOrderTransitionException, etc.)
- `RepositoryServiceProvider`: Pattern binding interface → implementation, thêm OrderRepository, CartRepository

### Established Patterns
- Clean Architecture: Domain entities thuần PHP (readonly constructor), repos interface, Eloquent ở Infrastructure
- Action pattern: 1 action = 1 use case, inject repos qua constructor
- Form Request validation, API Resource transformation
- JSON envelope: `{ success, data, meta }` / `{ success: false, code, message, errors }`
- Test pattern: Pest feature tests với `actingAs()` + Sanctum token

### Integration Points
- `routes/api.php`: Thêm cart routes (public, dùng X-Cart-Token) + order routes (admin + public checkout)
- Product domain: Cart/Order cần read ProductModel, lock + update stock
- Auth: Admin order management dùng Sanctum middleware hiện có

</code_context>

<specifics>
## Specific Ideas

- State machine cho order nên validate transitions ở Domain layer (guard method trên entity hoặc enum), không phải controller
- PlaceOrderAction phải handle multiple products trong 1 transaction — lock tất cả product rows trước khi check + trừ
- Idempotency key nên dùng package `infinitypaul/idempotency-laravel` đã mention trong CLAUDE.md
- Email notification dùng Laravel Notification system với database queue — fire after commit

</specifics>

<deferred>
## Deferred Ideas

- Phí giao hàng tính theo loại (nội tỉnh/ngoại tỉnh) — có thể thêm v2
- Ghi chú đơn hàng từ khách — có thể thêm khi FE request
- Stock reservation (giữ hàng trong giỏ có thời hạn) — INVT-V2-02
- QR code động cho chuyển khoản — PAYM-V2-02

</deferred>

---

*Phase: 03-orders-cart-payments*
*Context gathered: 2026-03-29*
