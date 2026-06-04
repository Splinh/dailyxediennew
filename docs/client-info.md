# Thông Tin Khách Hàng — Demo Website

> File này lưu thông tin thực tế của khách hàng để sử dụng khi lên demo.
> Cập nhật khi có thêm thông tin từ khách.

---

## Thông Tin Doanh Nghiệp

| Mục | Nội dung |
|---|---|
| **Tên thương hiệu** | Trà & Táo Đỏ Lạc Huy |
| **Mô tả ngắn (tagline)** | Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín |
| **Điện thoại / Hotline** | 098 750 33 60 |
| **Email** | Lachuyhddt@gmail.com |
| **Địa chỉ** | _(chưa có — cần hỏi khách)_ |
| **Website tham khảo** | https://thaphaco.com.vn/ |

---

## Mạng Xã Hội

| Kênh | Link |
|---|---|
| Facebook | _(chưa có)_ |
| Zalo | `https://zalo.me/0987503360` |
| Youtube | _(chưa có)_ |
| TikTok | _(chưa có)_ |

---

## Nội Dung Cần Từ Khách

- [ ] Logo (file gốc PNG/SVG, nền trong suốt)
- [ ] Hình ảnh sản phẩm (chất lượng cao)
- [ ] Danh mục sản phẩm chính xác
- [ ] Giá sản phẩm
- [ ] Địa chỉ cửa hàng / kho
- [ ] Link fanpage Facebook
- [ ] Nội dung giới thiệu công ty
- [ ] Chính sách vận chuyển / đổi trả
- [ ] Giờ làm việc

### Thông tin footer mới (ACF Options — điền sẵn field, chờ khách)
- [ ] Tên công ty đầy đủ (`company_name`)
- [ ] Tên quốc tế (`company_intl_name`)
- [ ] Mã số thuế / MST (`company_tax`)
- [ ] Đường dây khiếu nại (`complaint_phone`)
- [ ] Địa chỉ showroom/bán lẻ (`addr_showroom`)
- [ ] Vùng trồng nguyên liệu (`addr_farm`)
- [ ] Nhà máy / xưởng sản xuất (`addr_factory`)
- [ ] Số tài khoản ngân hàng (`bank_account`)
- [ ] Website (`website_url`)
- [ ] Ảnh phương thức thanh toán (`payment_image`)
- [ ] Link đăng ký Bộ Công Thương (`gov_badge_url`)
- [ ] Bộ ảnh hoạt động công ty cho gallery sitewide (`activity_gallery`)

---

## Thông Tin Kỹ Thuật (Demo)

| Mục | Giá trị |
|---|---|
| Domain local | `http://thaphaco.test` |
| WP Admin | `http://thaphaco.test/wp/wp-admin/` |
| Admin user | `admin` |
| Admin pass | `admin` |
| Theme đang chạy | `spl` (parent) |
| PHP | 8.1+ |
| DB name | `thaphaco` |

---

## Mapping Nội Dung → Theme

| Vị trí trong theme | Nội dung khách |
|---|---|
| `Site Title` (WP Settings) | Trà & Táo Đỏ Lạc Huy |
| `Tagline` (WP Settings) | Nơi cung cấp thực phẩm tự nhiên chất lượng và uy tín |
| ACF Options → `hotline` | 098 750 33 60 |
| ACF Options → `email` | Lachuyhddt@gmail.com |
| ACF Options → `address` | _(chờ khách)_ |
| Header → Top bar brand | CÔNG TY TNHH TRÀ & TÁO ĐỎ LẠC HUY _(hoặc tên đầy đủ từ khách)_ |
| Footer → Copyright | © 2026 Trà & Táo Đỏ Lạc Huy. All rights reserved. |
| Fixed buttons → Zalo | `https://zalo.me/0987503360` |
| Fixed buttons → Phone | `tel:0987503360` |

---

## Ghi Chú

- Color scheme: theo HTML mockup (xanh lá `#2d5016`, `#4a7c1f`, `#6db33f`) — có thể thay đổi theo yêu cầu khách
- WooCommerce: đã tùy biến shop, single product, filter giá, xem nhanh, mini cart off-canvas, cart/checkout và responsive
- Đa ngôn ngữ: không cần

---

## Tiến Độ WooCommerce — 2026-06-02

- [x] Tham khảo luồng giao diện từ theme `hd-thamkhao`
- [x] Bổ sung slide ảnh, điều hướng và zoom cho trang single product
- [x] Bổ sung filter giá và giao diện filter responsive cho trang cửa hàng
- [x] Bổ sung xem nhanh sản phẩm bằng REST API
- [x] Bổ sung mini cart off-canvas, cập nhật số lượng bằng AJAX và badge giỏ hàng
- [x] Bổ sung giao diện cart/checkout responsive, hỗ trợ cả WooCommerce Blocks và shortcode cổ điển
- [x] Kiểm tra PHP lint, PHPCS, render HTTP, filter, quick view và cập nhật mini cart
- [ ] Rà soát trực quan thêm trên desktop/mobile khi browser local khả dụng
- [ ] Cấu hình và kiểm thử cổng thanh toán thực tế theo tài khoản merchant của khách

---

## Tiến Độ Trang Chủ & Footer — 2026-06-03

- [x] Field ACF "Số cột" (4/5) cho section Flash Sale / Sản phẩm / Danh mục
- [x] Khối "Hình ảnh hoạt động công ty" (gallery sitewide trên footer, lightbox)
- [x] Footer redesign 4 cột theo bố cục thaphaco + field option đầy đủ (MST, kho, vùng trồng, nhà máy, STK, website, thanh toán...)
- [x] Đăng ký menu "Footer About Menu" (`about-nav`)
- [x] Giữ font Be Vietnam Pro (theo yêu cầu khách)
- [ ] Khách điền thông tin công ty + upload gallery hoạt động qua ACF Options
- [ ] Gán menu cho location "Footer About Menu" (nếu dùng menu thay link tĩnh)

Chi tiết kỹ thuật xem [REVIEW-can-chinh.md](REVIEW-can-chinh.md).
