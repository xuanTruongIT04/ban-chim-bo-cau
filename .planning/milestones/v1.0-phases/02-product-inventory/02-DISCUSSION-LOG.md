# Phase 2: Product & Inventory - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-28
**Phase:** 02-product-inventory
**Areas discussed:** Lưu ảnh sản phẩm, Cấu trúc danh mục, Chi tiết nhật ký tồn kho, API sản phẩm công khai

---

## Lưu ảnh sản phẩm

| Option | Description | Selected |
|--------|-------------|----------|
| URL string | Admin tự upload ảnh lên nơi khác, paste URL vào. Đơn giản nhất, không cần infra. | |
| Upload lên disk nội bộ | Backend nhận file, lưu vào storage/app/public. Dễ mất ảnh khi server reset. | |
| Cloud storage (S3/R2) | Upload lên S3 hoặc Cloudflare R2. Bền vững, CDN sẵn. | ✓ |

**User's choice:** Cloud storage (S3/R2)

---

| Option | Description | Selected |
|--------|-------------|----------|
| 1 ảnh duy nhất | 1 ảnh đại diện cho mỗi sản phẩm. Đơn giản nhất. | |
| Nhiều ảnh (gallery) | Mỗi sản phẩm có 1 ảnh chính + nhiều ảnh phụ. Cần bảng product_images riêng. | ✓ |

**User's choice:** Nhiều ảnh (gallery)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Không, upload nguyên bản | Lưu file gốc lên S3. Frontend tự resize. | |
| Có, tạo thumbnail tự động | Backend tạo thêm thumbnail khi upload. Cần Intervention Image. | ✓ |

**User's choice:** Có, tạo thumbnail tự động

---

| Option | Description | Selected |
|--------|-------------|----------|
| Có, 1 ảnh chính + các ảnh phụ | Cột is_primary trong bảng product_images. Frontend dùng ảnh chính cho thumbnail listing. | ✓ |
| Không, theo thứ tự upload | Ảnh đầu tiên = ảnh chính. Lưu sort_order thay vì is_primary. | |

**User's choice:** Có, 1 ảnh chính + các ảnh phụ (is_primary)

---

## Cấu trúc danh mục

| Option | Description | Selected |
|--------|-------------|----------|
| Phẳng, không cấp bậc | Chỉ là danh sách: chim bồ câu, gia cầm, khác. | |
| Có cấp cha-con | Ví dụ: Chim bồ câu > Bồ câu sống / Bồ câu thịt. Cần parent_id. | ✓ |

**User's choice:** Có cấp cha-con

---

| Option | Description | Selected |
|--------|-------------|----------|
| 2 tầng (cha + con) | Đủ dùng, dễ quản lý. | ✓ |
| Không giới hạn (nested) | Flexible hơn nhưng phức tạp hơn đáng kể. | |

**User's choice:** 2 tầng (cha + con)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ 1 danh mục | 1 sản phẩm gắn với 1 danh mục duy nhất. Đơn giản, query dễ. | ✓ |
| Nhiều danh mục (many-to-many) | 1 sản phẩm có thể thuộc nhiều danh mục. Cần bảng pivot. | |

**User's choice:** Chỉ 1 danh mục

---

## Chi tiết nhật ký tồn kho

| Option | Description | Selected |
|--------|-------------|----------|
| Có, chọn từ danh sách | Admin chọn loại: nhập hàng / kiểm kê / hư hỏng / khác. Có thêm ghi chú tự do. | ✓ |
| Không, chỉ ghi số lượng | Log chỉ lưu: ai đổi, đổi bao nhiêu, khi nào. | |
| Ghi chú tự do (free text) | Admin tự nhập lý do thoải mái. Linh hoạt nhưng khó lọc. | |

**User's choice:** Có, chọn từ danh sách + ghi chú tự do

---

| Option | Description | Selected |
|--------|-------------|----------|
| Ghi delta (đổi bao nhiêu) | Lưu số thay đổi: +50, -3. Dễ hiểu lịch sử. | ✓ |
| Ghi giá trị tuyệt đối | Admin nhập "tồn kho mới là 25". Hệ thống tự tính delta để ghi log. | |

**User's choice:** Ghi delta

---

## API sản phẩm công khai

| Option | Description | Selected |
|--------|-------------|----------|
| Chỉ trạng thái còn/hết hàng | Trả về in_stock: true/false. Khách không biết chính xác số lượng. | ✓ |
| Hiển thị số lượng cụ thể | Trả về stock_quantity. Dễ bị lợi dụng để biết số liệu kinh doanh. | |

**User's choice:** Chỉ trạng thái còn/hết hàng (in_stock: true/false)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Filter theo danh mục + sort mặc định | ?category_id=X để lọc. Mặc định sort theo tên. Pagination chuẩn. | ✓ |
| Filter + sort nhiều tiêu chí | ?sort=price_asc, ?sort=newest, filter theo unit_type. Dùng spatie/query-builder. | |

**User's choice:** Filter theo category_id + sort mặc định theo tên

---

## Claude's Discretion

- Cách implement thumbnail (queue job hay synchronous)
- Cách validate max depth 2 cho category (middleware, service, hay domain rule)
- Thứ tự field trong API resource response

## Deferred Ideas

None
