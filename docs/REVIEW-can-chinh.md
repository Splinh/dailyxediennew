# Review Code — Danh Sách Cần Chỉnh

> Theme `spl` — phần demo Lạc Huy. Ngày review: 2026-06-01.
> **Trạng thái: tất cả mục đã xử lý.** Lỗi gốc "trang trắng" = mục #0.

---

## Cập nhật tiến độ WooCommerce — 2026-06-02

### [x] Storefront và single product
- Đã bổ sung filter giá, panel filter responsive, quick view sản phẩm qua REST API.
- Đã bổ sung điều hướng ảnh, thumbnail, swipe và zoom ảnh cho trang single product.

### [x] Mini cart, cart và checkout
- Đã bổ sung mini cart off-canvas, badge giỏ hàng và cập nhật số lượng bằng AJAX.
- Đã bổ sung giao diện cart/checkout responsive, hỗ trợ WooCommerce Blocks và shortcode cổ điển.
- Đã bổ sung thanh bước tiến trình cho cart/checkout và hiển thị ngưỡng miễn phí vận chuyển trong mini cart.
- Đã sửa layout WooCommerce Blocks desktop: cột nội dung và cột tổng đơn giữ cùng hàng bằng grid, sidebar sticky.
- Đã tách thứ tự responsive: cart hiển thị sản phẩm trước tổng tiền; checkout đưa summary thu gọn lên trước form.
- Đã tinh chỉnh checkout order summary: bỏ border lồng, cân lại spacing, làm nổi tổng tiền và ẩn wrapper discount/fee rỗng.

### [x] Kiểm tra kỹ thuật
- PHP lint và PHPCS cho các file PHP mới: đạt.
- Render HTTP trang chủ, cửa hàng, single product và giỏ hàng: đạt, không có warning/fatal error.
- Kiểm tra filter giá, quick view REST và cập nhật số lượng mini cart AJAX: đạt.
- Kiểm tra cart/checkout WooCommerce Blocks chỉ render một thanh tiến trình: đạt.
- Kiểm tra trực quan cart/checkout desktop và responsive bằng Chrome headless: đạt.

### [ ] Còn lại trước production
- Rà soát trực quan thêm trên desktop/mobile khi browser local khả dụng.
- Cấu hình và kiểm thử cổng thanh toán thực tế theo tài khoản merchant của khách.
- Xóa `populate-*.php` trước khi deploy production.

---

## Cập nhật trang chủ & footer — 2026-06-03

> Tham khảo bố cục từ thaphaco.com.vn (web mẫu, theme Flatsome). Brand vẫn là Lạc Huy — không đưa dữ liệu công ty thaphaco vào (trừ field demo theo yêu cầu khách ở Đợt 2).

### [x] Chọn số cột cho section trang chủ (4/5 cột)
- Thêm field ACF **"Số cột"** (select 3/4/5, mặc định 4) vào 3 layout flexible content: `flash_sale`, `products`, `categories` — `acf-json/group_lachuy_home.json`.
- PHP đọc `columns` → xuất biến CSS `--cols` lên grid: `parts/home/products.php`, `parts/home/flash-sale.php`, `parts/home/categories.php`.
- CSS dùng **class modifier** `.products-grid--cols` (chỉ áp cho home) để trang shop/search/related **giữ nguyên** auto-fill cũ. Responsive tự co 3→2→1 cột. File: `inc/critical.css`.

### [x] Tiêu đề section (icon giỏ + đường kẻ ngang)
- Đã có sẵn (`section-title__heading` + `::before/::after` + `section-title__icon` + `section-title__line`) ở products/categories/blog — giữ nguyên.
- Flash Sale giữ header riêng có đồng hồ đếm ngược (chủ đích).

### [x] Khối "Hình ảnh hoạt động công ty" (sitewide)
- Tab ACF mới **"Hoạt động công ty"** trong options: `activity_title`, `activity_subtitle`, `activity_gallery` (type gallery) — `acf-json/group_lachuy_options.json`.
- Part mới `parts/global/company-activity.php`, include trong `footer.php` ngay trên `<footer>` → hiện **mọi trang**. Click ảnh mở **lightbox PhotoSwipe** sẵn có (`data-fx-lightbox`). Gallery trống thì tự ẩn.
- CSS `.company-activity` / `.activity-grid` / `.activity-card` trong `inc/critical.css` (file load mọi trang).

### [x] Footer redesign + Option Page fields
- Bố cục 4 cột kiểu thaphaco (Công ty / Về chúng tôi / Chính sách / Liên hệ + thanh toán), `footer.php`.
- Thêm field option: `company_name`, `company_intl_name`, `company_tax` (MST), `complaint_phone`, `addr_showroom`, `addr_farm`, `addr_factory`, `bank_account`, `website_url`, `payment_image`, `gov_badge_url`. Default **để trống** cho khách tự điền.
- Đăng ký thêm menu location `about-nav` ("Về chúng tôi") trong `inc/setup.php`; có fallback link tĩnh khi chưa gán menu.
- Font: **giữ Be Vietnam Pro** (khách đã chốt, không đổi sang system font dù thaphaco dùng system).

### [x] Kiểm tra kỹ thuật
- `php -l` toàn bộ file PHP sửa/mới: đạt. JSON ACF `json_decode`: hợp lệ. CSS cân bằng dấu ngoặc.
- Bump `modified` trong 2 file ACF JSON để admin nhận "Sync available".

### [ ] Cần làm thủ công sau khi pull
- WP Admin → Custom Fields → **Sync** field group `Lạc Huy - Trang chủ` và `Lạc Huy - Tùy chọn Theme` (nếu đã từng vào DB) để field mới hiện ra.
- Vào **Tùy Chọn**: điền thông tin công ty + upload gallery hoạt động.
- Mỗi section trang chủ (Flash Sale/Sản phẩm/Danh mục): chọn "Số cột".
- Gán menu cho location **"Footer About Menu"** (về chúng tôi) nếu muốn dùng menu thay link tĩnh.

---

## Đợt 2 — Điền nội dung, fix logo / header / menu — 2026-06-03

> Danh sách việc + tiến độ (cập nhật khi xong từng mục).

### [x] A. Điền nội dung field ACF Options
- Field đã có dữ liệu trong `client-info.md` (Lạc Huy): giữ/điền đúng — hotline, email, address, company_name, zalo.
- Field chưa có dữ liệu thật: **lấy của thaphaco làm demo** (theo yêu cầu khách) — `company_intl_name`, `company_tax` (MST), `complaint_phone`, `addr_showroom`, `addr_farm`, `addr_factory`, `bank_account`, `facebook`, `youtube`, `tiktok`.
- `website_url` = URL site hiện tại (home_url); `activity_title`/`activity_subtitle` = mặc định.
- Đã chạy: script `populate-options-data.php` (14 field) + set option `social_link__options` (fb/yt/tiktok/zalo/messenger) — vì footer/header đọc social từ option này, KHÔNG phải ACF `*_url`.
- ⚠ Data thaphaco chỉ là demo — thay bằng data thật của Lạc Huy trước production. Xóa `populate-options-data.php` khi deploy.

### [x] B. Fix logo bị to khi thay ảnh
- Thêm CSS `.logo img` / `.logo .custom-logo`: cao 48px, `width:auto`, `max-width:200px`, `object-fit:contain`, `padding:6px 0`. File `inc/critical.css`.

### [x] C. Header sticky — treo nav-bar khi cuộn
- `.nav-bar` đổi `position:relative` → `position:sticky; top:var(--header-height); z-index:98` → header (logo/search) + nav-bar (danh mục) dính cùng khi cuộn; top-bar vẫn cuộn đi. File `inc/critical.css`.

### [x] D. Fix lỗi tạo menu không hiện
- Chẩn đoán: **không có fatal/JS chặn** (debug.log sạch, `admin_hide_menu` rỗng). Menu "Main menu" (ID 27) tạo ra nhưng **0 item + chưa gán location** nên header trống (`main-nav` để `fallback_cb => false`).
- Đã sửa:
  - `header.php`: `main-nav` đổi `fallback_cb` → hàm `spl_main_nav_fallback()` (định nghĩa ở `inc/setup.php`) → nav hiện link Trang chủ/Cửa hàng/Giới thiệu/Tin tức/Liên hệ khi chưa gán menu.
  - Đã tạo 5 item cho "Main menu" (Trang Chủ, Cửa Hàng, Giới Thiệu, Tin Tức, Liên Hệ) + gán location `main-nav` qua WP-CLI → menu hiện ngay trên header.
- Kiểm tra render `http://thaphaco.test/`: HTTP 200, không warning/fatal, 5 menu item hiển thị đúng.

### [x] E. Cập nhật file tiến độ
- Đã ghi danh sách + tiến độ vào file này.

### [x] F. Style menu giống thaphaco
- `.nav-bar` đổi nền xanh đậm `#198839` → xanh brand `var(--color-primary)` (`#60b301`) đúng màu thaphaco.
- Nút "Danh mục sản phẩm" → `--color-primary-dark`; link menu bỏ `text-transform:uppercase` (Title Case như thaphaco), căn trái thay vì giữa.
- Thêm trạng thái **trang hiện tại** (`current-menu-item`/`aria-current`): nền nhạt + gạch chân. File `inc/critical.css`.

> **Cần khách làm:** thay data demo thaphaco bằng data thật Lạc Huy (MST, STK, địa chỉ, social); upload ảnh vào gallery "Hoạt động công ty"; (tùy chọn) sửa item menu trong Giao diện → Menu.

---

## 🔴 Lỗi thật sự (ưu tiên cao)

### [x] 0. ⭐ NGUYÊN NHÂN "TRANG TRẮNG" — sai tên class reveal (ĐÃ SỬA)
- **File:** `inc/inline-js.php:102` ↔ `inc/critical.css:1352-1360`
- **Vấn đề:** CSS ẩn `.reveal { opacity:0 }` và chỉ hiện khi có class `.reveal.visible`. Nhưng JS lại thêm class `revealed` (sai tên) → mọi phần tử `.reveal` vĩnh viễn `opacity:0` → trang chủ + single-product + archive-product trắng nội dung (HTML vẫn render đầy đủ).
- **Đã sửa:** đổi JS `classList.add('revealed')` → `'visible'`; thêm fallback hiện toàn bộ khi trình duyệt không hỗ trợ `IntersectionObserver`.

### [x] 1. Query "Sản phẩm nổi bật" sai cơ chế → luôn rỗng
- **File:** `parts/home/products.php:27-34`
- **Vấn đề:** Query featured bằng postmeta `'meta_key' => '_featured', 'meta_value' => 'yes'`. WooCommerce hiện đại lưu featured ở taxonomy `product_visibility` (term `featured`), **không** phải meta `_featured`. → Query luôn rỗng, luôn rơi vào fallback "sản phẩm mới nhất".
- **Bằng chứng:** chính `debug-products.php:22` đã ghi chú "Modern WooCommerce uses 'product_visibility' taxonomy".
- **Hướng sửa:** dùng `wc_get_featured_product_ids()` + `'post__in'`, hoặc `tax_query` với `product_visibility = featured`.

### [x] 2. Trang blog / Tin Tức bị trống
- **File:** `home.php` (root theme)
- **Vấn đề:** Giữa `get_header('blog')` và `get_footer('blog')` chỉ có `/**/`, không render gì. `home.php` được WP ưu tiên hơn `index.php` cho trang danh sách bài viết → trang "Tin Tức" (`page_for_posts`) hiển thị trắng.
- **Liên quan:** nút "Xem tất cả bài viết" ở `parts/home/blog.php:62` trỏ về trang này.
- **Hướng sửa:** thêm vòng lặp render danh sách post (hoặc include lại layout blog).

### [x] 3. Gọi template part không tồn tại
- **File:** `functions.php:41`
- **Vấn đề:** gọi `get_template_part( 'parts/blocks/php-error', ... )` nhưng file `parts/blocks/php-error.php` **không tồn tại**. Khi guard lỗi ở frontend → không hiện thông báo, chỉ `wp_die()` trắng.
- **Hướng sửa:** tạo file part đó, hoặc đổi sang `wp_die( esc_html( $error_message ) )`.

### [x] 4. Countdown Flash Sale lỗi trên Safari
- **File:** `parts/home/flash-sale.php:17` + `inc/inline-js.php:79`
- **Vấn đề:** `end_time` xuất dạng `Y-m-d H:i:s` ("2026-06-02 12:00:00"); JS parse bằng `new Date(...)`. Chuỗi có dấu cách không phải ISO 8601 → Safari trả `NaN` → đồng hồ đứng `00:00:00`.
- **Hướng sửa:** xuất ISO (`date('c')` hoặc thay dấu cách bằng `T`).

---

## 🟡 Không nhất quán / dễ ra trang trắng

### [x] 5. About & Contact không có fallback khi ACF trống
- **File:** `templates/template-page-about.php`, `templates/template-page-contact.php`
- **Vấn đề:** nếu `about_sections`/`contact_sections` rỗng (hoặc ACF tắt → `getField` trả `false`) thì trang chỉ có breadcrumb, nội dung trống. Trong khi `template-page-home.php:55-63` có nhánh fallback.
- **Hướng sửa:** thống nhất — thêm fallback hoặc thông báo "chưa có nội dung".

### [x] 6. Giá trị fallback hotline/email khác nhau giữa các file
- **File:** `header.php:17-18`, `footer.php:16-17` (default `0901 806 930` / `splworks.info@gmail.com` — Thaphaco cũ)
  vs `parts/contact/form.php:23`, `woocommerce/archive-product.php:17` (default `098 750 33 60` — Lạc Huy đúng theo [client-info.md](client-info.md))
- **Vấn đề:** khi ACF chưa cấu hình, các trang hiện số điện thoại khác nhau.
- **Đã sửa:** gom default về `098 750 33 60` / `Lachuyhddt@gmail.com` ở header/footer (kể cả nhánh `is_array`), và Zalo `https://zalo.me/0987503360` theo [client-info.md](client-info.md).

### [x] 7. `date()` không theo timezone WordPress
- **File:** `footer.php:145` (`date('Y')`), `parts/home/flash-sale.php:17`
- **Vấn đề:** dùng giờ server, không theo timezone WP; phpcs ruleset WordPress cũng cảnh báo.
- **Hướng sửa:** dùng `wp_date()` / `current_time()`.

---

## 🟢 Nhỏ / cần lưu ý

### [x] 8. File debug/populate còn trong theme
- **Đã xử lý:** đã xóa `debug-*.php` và `verify-data.php`. Còn lại `populate-*.php` (script tạo dữ liệu demo, có guard `ABSPATH`, chỉ chạy qua WP-CLI).
- **Lưu ý:** xóa nốt `populate-*.php` trước khi deploy production.

### [x] 9. Phụ thuộc JS theo trạng thái build — ĐÃ LÀM RÕ (an toàn)
- **File:** `inc/inline-js.php:17`, `inc/critical-css.php:20`
- **Kết luận:** `resources/styles/` **không** định nghĩa `.reveal { opacity:0 }` (chỉ `inc/critical.css` có, mà file này chỉ inline khi CHƯA build). → Sau khi build Vite: critical.css + inline-js đều tắt, nhưng CSS biên dịch không ẩn `.reveal` → **nội dung vẫn hiện, không trắng trang**. Chỉ mất hiệu ứng animation reveal (không ảnh hưởng nội dung).
- **Tùy chọn (không bắt buộc):** nếu muốn giữ animation sau build, thêm module xử lý `.reveal` vào `resources/scripts/` rồi build lại.

### [x] 10. Countdown reset mỗi lần load — chấp nhận cho demo
- **File:** `parts/home/flash-sale.php:16-18`
- **Vấn đề:** mặc định `+24h` tính lại mỗi request. Khi cần sale thật, set `end_time` cố định qua ACF.

---

## Ghi chú chung
- Cấu trúc code tốt: escaping đầy đủ, có guard `ABSPATH`, có fallback cho phần lớn section.
- **Tất cả mục #0–#10 đã xử lý.** Việc còn lại trước production: xóa `populate-*.php`.
