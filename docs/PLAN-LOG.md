# 📋 DAILYXEDIEN.VN REBUILD — Plan Log

> **Google Sheets gốc**: [Link](https://docs.google.com/spreadsheets/d/1xi5Rv1YKgoAD1wuGH0h1k-cNrX3juF2oYC10uKxvP8k/edit?gid=2085828008#gid=2085828008)
> **Repo**: [github.com/Splinh/dailyxediennew](https://github.com/Splinh/dailyxediennew)
> **Khởi tạo**: 2026-06-06
> **Cập nhật lần cuối**: 2026-06-08

---

## Ký Hiệu Trạng Thái

| Icon | Trạng thái |
|------|-----------|
| ⬜ | Chưa bắt đầu |
| 🔄 | Đang làm |
| ✅ | Hoàn thành |
| ⏸️ | Tạm dừng |
| ❌ | Huỷ / Không cần |
| 🆕 | Task phát sinh (không có trong plan gốc) |

---

## THÁNG 1 — Setup & Child Theme

### Tuần 1: Project Setup

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 1 | Clone woo2026 project, cấu hình .env | 🔴 Cao | ⬜ | — | |
| 2 | Import DB production → local Laragon | 🔴 Cao | ⬜ | — | mysqldump |
| 3 | Search-replace URLs (dailyxedien.vn → .test) | 🔴 Cao | ⬜ | — | wp-cli |
| 4 | Rename SPL (namespace, constants) | 🔴 Cao | ⬜ | — | |
| 5 | Copy DevVN Store Pro + BaoKim plugins | 🔴 Cao | ⬜ | — | |
| 6 | Set PHP >=8.3 trong composer.json | 🟡 TB | ⬜ | — | |
| 7 | Verify WooCommerce data intact | 🔴 Cao | ⬜ | — | SP, đơn hàng, biến thể |

### Tuần 2: Child Theme Scaffold

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 8 | Tạo spl-child scaffold (composer, vite) | 🔴 Cao | ⬜ | — | PSR-4 autoload |
| 9 | Tạo Bootstrap.php + functions.php child | 🔴 Cao | ⬜ | — | ~30 dòng |
| 10 | Setup Vite config cho child theme | 🟡 TB | ⬜ | — | |

### Tuần 3: WooCommerce Migration

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 11 | Migrate CheckoutFields.php | 🔴 Cao | ⬜ | — | Remove fields, Buy Now |
| 12 | Migrate PriceDisplay.php | 🔴 Cao | ⬜ | — | Liên hệ, first variant |
| 13 | Migrate srsltid redirect fix | 🟢 Thấp | ⬜ | — | |
| 14 | Migrate translation filters | 🟢 Thấp | ⬜ | — | Quick View, Select options |

### Tuần 4: Minor Migrations & Test

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 15 | Migrate currency symbol ₫ | 🟢 Thấp | ⬜ | — | |
| 16 | Migrate archive title cleanup | 🟢 Thấp | ⬜ | — | |
| 17 | Test toàn bộ frontend rendering | 🔴 Cao | ⬜ | — | |

---

## THÁNG 2 — Custom Modules

### Tuần 1: TSKT & Tracking

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 18 | Code TSKTModule.php | 🔴 Cao | ⬜ | — | ACF repeater display |
| 19 | Code TSKTImport.php | 🟡 TB | ⬜ | — | Admin bulk import |
| 20 | Code TSKTExport.php | 🟡 TB | ⬜ | — | Export tool |

### Tuần 2: Tracking & Shortcodes

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 21 | Code TrackingPixels.php | 🔴 Cao | ⬜ | — | GA4 + FB Pixel events |
| 22 | Code LoanShortcode.php | 🟡 TB | ⬜ | — | [loan_calculator] |
| 23 | Code SeasonalModule.php | 🟢 Thấp | ⬜ | — | Tet, holiday banners |

### Tuần 3: Polylang Bridge

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 24 | Code PolylangBridge — WooCommerceSync | 🔴 Cao | ⬜ | — | Stock/price sync |
| 25 | Code PolylangBridge — StringTranslation | 🟡 TB | ⬜ | — | Admin UI strings |

### Tuần 3-4: Polylang + Content Cleanup

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 26 | Code PolylangBridge — DuplicateContent | 🟡 TB | ⬜ | — | Duplicate to EN |
| 27 | Code PolylangBridge — SEOIntegration | 🔴 Cao | ⬜ | — | Hreflang, canonical |
| 28 | Dọn SP: xóa/ẩn ngừng kinh doanh | 🔴 Cao | ⬜ | — | 301 redirect |
| 29 | Dọn bài viết không liên quan | 🟡 TB | ⬜ | — | Du lịch, thể thao |
| 30 | Dọn hình ảnh orphaned | 🟡 TB | ⬜ | — | Alt text chuẩn hóa |
| 31 | Dọn tags/danh mục rỗng | 🟡 TB | ⬜ | — | Giảm tag sitemaps |

---

## THÁNG 3 — Frontend & Performance

### Tuần 1: Frontend Templates

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 32 | Frontend: Homepage template | 🔴 Cao | ⬜ | — | Hero, badges, products |
| 33 | Frontend: Product page enhancements | 🔴 Cao | ⬜ | — | TSKT tab, loan calc |

### Tuần 2: Category & Mobile

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 34 | Frontend: Category page + filter | 🟡 TB | ⬜ | — | AJAX filter |
| 35 | Frontend: Mobile responsive | 🔴 Cao | ⬜ | — | Sticky header, bottom nav |

### Tuần 3: Performance

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 36 | Performance: LiteSpeed config | 🔴 Cao | ⬜ | — | Production cache |
| 37 | Performance: DB optimization | 🟡 TB | ⬜ | — | Revisions, transients |
| 38 | Performance: Preload hero + fonts | 🟡 TB | ⬜ | — | fetchpriority, preconnect |

### Tuần 4: SEO

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 39 | Gỡ Yoast, thống nhất Rank Math | 🔴 Cao | ⬜ | — | robots.txt, sitemap |
| 40 | SEO: Schema markup | 🟡 TB | ⬜ | — | Product, Org, Local |
| 41 | SEO: Redirect 301 map | 🔴 Cao | ⬜ | — | Rank Math Redirections |

---

## THÁNG 4 — QA & Deploy

### Tuần 1: Verification

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 42 | Verify GSC: ownership, sitemap, coverage | 🔴 Cao | ⬜ | — | 0 errors |
| 43 | Verify GA4: tracking, e-commerce events | 🔴 Cao | ⬜ | — | purchase, add_to_cart |
| 44 | Verify Google Ads: conversion, remarketing | 🔴 Cao | ⬜ | — | Merchant Center |
| 45 | Verify FB Pixel events | 🟡 TB | ⬜ | — | TrackingPixels.php |

### Tuần 2: Payment & QA

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 46 | Verify BaoKim payment E2E | 🔴 Cao | ⬜ | — | Test checkout flow |
| 47 | Verify DevVN Stores display | 🟡 TB | ⬜ | — | |
| 48 | Verify Fluent SMTP email | 🟡 TB | ⬜ | — | |
| 49 | QA: Full site crawl (0 broken links) | 🔴 Cao | ⬜ | — | Screaming Frog |

### Tuần 3: Final QA & Deploy

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 50 | QA: Mobile test (3+ devices) | 🔴 Cao | ⬜ | — | |
| 51 | QA: Schema validation | 🟡 TB | ⬜ | — | Rich Results Test |
| 52 | QA: PageSpeed ≥75 mobile, ≥92 desktop | 🔴 Cao | ⬜ | — | |
| 53 | Deploy: Upload theme + plugins | 🔴 Cao | ⬜ | — | Staged rollout |

### Tuần 4: Post-Deploy

| # | Công việc | Ưu tiên | Trạng thái | Ngày | Ghi chú |
|---|----------|---------|-----------|------|---------|
| 54 | Deploy: Deactivate old plugins (5/batch) | 🔴 Cao | ⬜ | — | Verify each batch |
| 55 | Monitor: GSC daily 14 ngày | 🔴 Cao | ⬜ | — | Fix 404 ngay |
| 56 | Monitor: GA4 traffic comparison | 🟡 TB | ⬜ | — | vs tháng trước |

---

## 🆕 TASKS PHÁT SINH

> Ghi lại các công việc phát sinh ngoài plan gốc. Mỗi task ghi rõ ngày, lý do, ảnh hưởng đến plan.

### Thiết kế htmlmau — HTML Tailwind tham khảo (trước T1)

> Lên giao diện mẫu bằng HTML + Tailwind CSS CDN trước khi code theme thật.
> Dùng làm tham khảo cho T3 Frontend (#32-35).

| # | Công việc | Ngày | Trạng thái | Ghi chú |
|---|----------|------|-----------|---------|
| H1 | Thiết kế `index.html` — Homepage | — | ✅ | Hero, badges, product grid, flash sale, blog |
| H2 | Thiết kế `san-pham.html` — Archive sản phẩm | — | ✅ | Filter sidebar, product grid, pagination |
| H3 | Thiết kế `chi-tiet-san-pham.html` — Single product | — | ✅ | Gallery, TSKT tab, reviews, related products |
| H4 | Thiết kế `daily.html` — Archive đại lý | — | ✅ | Danh sách đại lý, bản đồ, filter theo vùng |
| H5 | Thiết kế `chi-tiet-daily.html` — Single đại lý | — | ✅ | Thông tin đại lý, sản phẩm, liên hệ |
| H6 | Thiết kế `hop-tac.html` — Cơ hội hợp tác | — | ✅ | Form đăng ký, quyền lợi, quy trình |
| H7 | Thiết kế `about.html` — Giới thiệu | — | ✅ | Câu chuyện, đội ngũ, giá trị cốt lõi |
| H8 | Viết spec markdown cho các trang | — | ✅ | 6 files trong `htmlmau/md page/` |

### Pre-project Setup (trước T1)

| # | Công việc | Ngày | Trạng thái | Ghi chú |
|---|----------|------|-----------|---------|
| P1 | Clone repo taodolachuy → dailynew | 2026-06-06 | ✅ | Nền tảng project, giữ lại htmlmau |
| P2 | Push lên repo dailyxediennew | 2026-06-06 | ✅ | github.com/Splinh/dailyxediennew |
| P3 | Xoá folder website/ (51MB cloudflared.exe) | 2026-06-06 | ✅ | Giảm repo size, thêm vào .gitignore |
| P4 | Thay docs/ root bằng docs từ theme spl | 2026-06-06 | ✅ | BLUEPRINT + PERFORMANCE + SCALING |
| P5 | Tạo PLAN-LOG.md | 2026-06-06 | ✅ | File này — tracking progress |

---

### 🔨 ĐỢT THỰC HIỆN: Trang Chủ + Header/Footer (T3 #32) — bắt đầu 2026-06-07

> Mục tiêu: dựng trang chủ dailyxedien theo `htmlmau/index.html`, ưu tiên header/footer.
> Brand lấy từ `docs/brand-guide.md` (primary `#1e73be`, accent `#ffa500`, navy `#002647`, font **Be Vietnam Pro**).
> Trang chủ = ACF Flexible Content; header/footer nhập qua ACF Options page (mở rộng).
> Stack: **Tailwind v4 + Vite** (pipeline đã có sẵn ở `resources/styles/tailwind/`).

**Quyết định đã chốt:** (1) Tailwind+Vite; (2) thay TOÀN BỘ section trang chủ theo htmlmau; (3) mở rộng ACF Options đầy đủ; (4) brand theo `docs/brand-guide.md`, font Be Vietnam Pro (giữ, không đổi Inter).

**Build:** máy dev chạy `npm run watch` / `npm run build` trong thư mục theme (`wp/wp-content/themes/spl`) là được (user xác nhận). Tailwind v4 chỉ sinh class đang dùng → **phải build lại** sau khi sửa template. JS thì enqueue thẳng, không cần build.

**Icon = SVG inline** (user chốt, KHÔNG FontAwesome). Helper `spl_icon($name,$class)` trong `header.php`.

#### A. Nền build (Tailwind + brand)
| # | Việc | Trạng thái | Ghi chú |
|---|------|-----------|---------|
| A1 | `@theme` trong `themes.css`: primary `#1e73be`, accent `#ffa500`, accent-dark, sale, navy `#002647`, scale primary-50…900, shadow-premium/hover-card, animate float/fade-in; font Be Vietnam Pro | ✅ | thay teal |
| A2 | Port custom utility/animation htmlmau → `components/dailyxedien.css` (no-scrollbar, hero-slide, tab-btn.active, skip-link, back-to-top, ring-pulse, shimmer, .dxd-mainmenu/mobilemenu/footermenu). Import vào `components/index.css` | ✅ | + FontAwesome đã GỠ khỏi `inc/critical-css.php` |
| A3 | `npm run build`/`watch` → regenerate `assets/.vite/manifest.json` + `tw.*.css` | ✅ | `pnpm build` thành công |
| A4 | Bỏ enqueue CSS vanilla cũ (`inc/critical.css`, `inc/pages.css`) | ✅ | Disable trong critical-css.php, inline-js.php |

#### B. Header (ưu tiên) — `header.php` ✅ XONG
| # | Việc | Trạng thái |
|---|------|-----------|
| B1 | Top utility bar (navy): topbar_links ACF + login + giỏ hàng | ✅ |
| B2 | Main header sticky: logo (`custom_logo` + fallback DXD), search WC (`post_type=product`), hotline | ✅ |
| B3 | Mobile drawer + accordion danh mục (`mobile-nav`/`main-nav` + `product_cat`) | ✅ |
| B4 | Nav bar xanh: nút "Danh mục SP" + dropdown (`get_terms('product_cat')`) + `wp_nav_menu('main-nav')` | ✅ |
| B5 | JS `inc/dxd-ui.js` (drawer, dropdown touch, back-to-top, no-scroll, ESC) — enqueue ở `inc/inline-js.php` | ✅ |

#### C. Footer (ưu tiên) — `footer.php` ✅ XONG
| # | Việc | Trạng thái |
|---|------|-----------|
| C1 | Footer navy 4 cột: Cty+social / Chính sách (`policy-nav`) / Hỗ trợ (`about-nav`) / Liên hệ | ✅ |
| C2 | Copyright bar + nút nổi (Zalo/Phone/back-to-top) + `parts/global/company-activity` | ✅ |

#### E. ACF Options mở rộng — `acf-json/group_lachuy_options.json`
| # | Việc | Trạng thái |
|---|------|-----------|
| E1 | Tab Header: `topbar_links` (repeater), `logo` (image), `logo_tagline`, `hotline_label`, hotline phụ | ✅ |
| E2 | Footer: cột chính sách/hỗ trợ qua WP menu (`policy-nav`/`about-nav`) + giữ `footer_desc`, social, `website_url` | ✅ |

#### D. Trang chủ flexible — `acf-json/group_lachuy_home.json` + `templates/template-page-home.php` + `parts/home/*`
| # | Section (theo htmlmau) | Trạng thái |
|---|------|-----------|
| D1 | Viết lại layouts flexible: hero_slider, usp_bar, categories, best_sellers (tabs), tech_spotlight, promo_banners, media_reviews, event_gallery, store_locator, brands, news, consult_form | ✅ |
| D2 | Sửa `template-page-home.php` switch map layout mới | ✅ |
| D3 | Viết lại `parts/home/*` (Tailwind) nhận `$args` flexible | ✅ | 12 file |
| D4 | Sửa `parts/product-card.php` sang style EV (ảnh, tên, giá, sao, "đã bán", badge) | ✅ |
| D5 | Port JS htmlmau → `resources/scripts/components/page-home.js` (hero slider, switchTab, drawer, cart, testimonials, scroll-top, toast) | ✅ |

#### Verify
- `pnpm build:theme` ok → `assets/.vite/manifest.json` cập nhật.
- Set 1 Page template "Trang Chủ" làm front page; nhập vài section ACF.
- `http://dailynew.test/` khớp `htmlmau/index.html`; đổi ACF → frontend đổi; mobile drawer ok; không lỗi PHP (WP_DEBUG).

---

### 🆕 HDA Plugin (SPL Toolkit) Fix — 2026-06-08

> Fix settings page không hoạt động sau khi clone project.

| # | Công việc | Trạng thái | Ghi chú |
|---|----------|-----------|--------|
| I1 | Diagnose: ACF active, HDA active, 17 modules, capability OK | ✅ | Stale transients blocking manifest |
| I2 | Fix stale transient cache | ✅ | Xóa `_transient_hda_*` |
| I3 | Fix settings panel visibility (first panel `show` class) | ✅ | |
| I4 | Fix tab switching (standalone vanilla JS tab switcher) | ✅ | |
| I5 | Fix script loading: bỏ `type="module"` + `defer` | ✅ | CJS bundle ≠ ESM |
| I6 | Fix settings save: PHP POST fallback + module settings delegation | ✅ | |
| I7 | Evaluate tools-thamkhao → kết luận: không cần, xóa được | ✅ | |

### 🆕 Admin UI Tweaks — 2026-06-08

| # | Công việc | Trạng thái | Ghi chú |
|---|----------|-----------|--------|
| J1 | Remove `fixed` class từ admin list tables | ✅ | `admin-core.js` source + build |

---

## 📊 Tổng Kết Tiến Độ

| Phase | Tổng tasks | ✅ Done | 🔄 Doing | ⬜ Todo | % |
|-------|-----------|--------|---------|-------|---|
| Htmlmau design | 8 | 8 | 0 | 0 | 100% |
| Pre-project setup | 5 | 5 | 0 | 0 | 100% |
| T3 #32 — Header/Footer | 7 (A-C) | 7 | 0 | 0 | 100% |
| T3 #32 — ACF Options | 2 (E) | 2 | 0 | 0 | 100% |
| T3 #32 — Trang chủ flexible | 5 (D) | 5 | 0 | 0 | 100% |
| HDA Plugin Fix | 7 (I) | 7 | 0 | 0 | 100% |
| Admin UI Tweaks | 1 (J) | 1 | 0 | 0 | 100% |
| T1 — Setup & Child Theme | 17 | 0 | 0 | 17 | 0% |
| T2 — Custom Modules | 14 | 0 | 0 | 14 | 0% |
| T3 — Frontend & Perf (còn lại) | 8 | 0 | 0 | 8 | 0% |
| T4 — QA & Deploy | 15 | 0 | 0 | 15 | 0% |
| **TỔNG** | **89** | **35** | **0** | **54** | **39%** |

---

## 📝 Changelog

> Ghi lại mỗi lần cập nhật plan log.

### 2026-06-08 (chiều)
- **HDA Plugin Fix** — Settings page trống sau khi clone:
  - Root cause: `settings.js` (CJS bundle) bị load `type="module"` → JS crash → tab switching + AJAX save hỏng
  - Fix: bỏ `type="module"` + `defer` khỏi `Plugin.php` enqueue
  - Thêm PHP POST fallback handler trong `GlobalSetting.php` (cả module toggles + module settings)
  - Thêm standalone inline tab switcher + first panel `show` class trong `settings.php` view
  - Xóa stale transients (`_transient_hda_*`), grant `hda_manage_options` capability
  - Kết luận `tools-thamkhao/`: không cần, legacy tools
- **Admin UI** — thêm remove `.fixed` class từ list tables vào `admin-core.js` source → build
- **Hero Slider Fix** — dùng `<img>` thay `background-image`, bỏ Ken Burns zoom, smoother crossfade
- **Legacy CSS Cleanup** — disable `critical.css`, `pages.css`, `core-ui.js` (conflict Tailwind)
- Cập nhật task D1-D5, E1-E2, A3-A4 → ✅ tất cả

### 2026-06-08 (sáng)
- Review lại project theo yêu cầu homepage: xác nhận WordPress root là `wp/`, theme chính là `wp/wp-content/themes/spl`, trang chủ dùng `templates/template-page-home.php` và ACF flexible field `home_sections`.
- **Hoàn thành D1-D5**: 12 ACF flexible layouts, 12 template parts, product card EV style, page-home.js
- **Hoàn thành E1-E2**: ACF Options mở rộng (Header tab + Footer integration)
- Seeding data: `populate-home-dailyxedien.php` + `populate-media-and-fix.php`
- Fix CSS load order (`Asset.php` — tailwind dep check), slider aspect-ratio, logo fallback
- `pnpm build` theme + HDA plugin thành công
- Tạo bộ tài liệu mới: HOMEPAGE-IMPLEMENTATION-PLAN, HOMEPAGE-TODO, HOMEPAGE-PROGRESS, PLAN-TRACKING, DAILYXEDIEN-SOURCE-OF-TRUTH

### 2026-06-07
- Bắt đầu đợt T3 #32: Trang chủ + Header/Footer dailyxedien (theo htmlmau)
- Tạo `docs/brand-guide.md` (brand thật từ site: #1e73be / #ffa500 / #002647, Be Vietnam Pro, logo, nav, social)
- Khảo sát kiến trúc theme: xác nhận pipeline Tailwind v4 + Vite có sẵn (`resources/styles/tailwind/`, enqueue qua `Asset`/manifest); phát hiện `tools/` (vite shared config) bị thiếu → user build bằng `npm run watch`/`build` trong theme
- Ghi chi tiết việc cần làm A–E vào PLAN-LOG
- **Hoàn thành A (nền brand/Tailwind), B (header.php), C (footer.php)** — toàn bộ icon SVG inline (helper `spl_icon`), bỏ FontAwesome
- Set brand `themes.css` + utility `components/dailyxedien.css`; viết `inc/dxd-ui.js` (drawer/dropdown/back-to-top)
- Còn lại: E (ACF Options mở rộng) + D (trang chủ flexible) — làm tiếp buổi chiều

### 2026-06-06
- Khởi tạo PLAN-LOG.md
- Bổ sung tasks phát sinh: Htmlmau design H1-H8 (đã hoàn thành trước đó)
- Hoàn thành Pre-project tasks P1-P5
- Clone taodolachuy → dailynew, push lên dailyxediennew
- Dọn website/, thay docs/
