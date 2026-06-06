# 📋 DAILYXEDIEN.VN REBUILD — Plan Log

> **Google Sheets gốc**: [Link](https://docs.google.com/spreadsheets/d/1xi5Rv1YKgoAD1wuGH0h1k-cNrX3juF2oYC10uKxvP8k/edit?gid=2085828008#gid=2085828008)
> **Repo**: [github.com/Splinh/dailyxediennew](https://github.com/Splinh/dailyxediennew)
> **Khởi tạo**: 2026-06-06
> **Cập nhật lần cuối**: 2026-06-06

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

## 📊 Tổng Kết Tiến Độ

| Phase | Tổng tasks | ✅ Done | 🔄 Doing | ⬜ Todo | % |
|-------|-----------|--------|---------|-------|---|
| Htmlmau design | 8 | 8 | 0 | 0 | 100% |
| Pre-project setup | 5 | 5 | 0 | 0 | 100% |
| T1 — Setup & Child Theme | 17 | 0 | 0 | 17 | 0% |
| T2 — Custom Modules | 14 | 0 | 0 | 14 | 0% |
| T3 — Frontend & Perf | 10 | 0 | 0 | 10 | 0% |
| T4 — QA & Deploy | 15 | 0 | 0 | 15 | 0% |
| **TỔNG** | **69** | **13** | **0** | **56** | **19%** |

---

## 📝 Changelog

> Ghi lại mỗi lần cập nhật plan log.

### 2026-06-06
- Khởi tạo PLAN-LOG.md
- Bổ sung tasks phát sinh: Htmlmau design H1-H8 (đã hoàn thành trước đó)
- Hoàn thành Pre-project tasks P1-P5
- Clone taodolachuy → dailynew, push lên dailyxediennew
- Dọn website/, thay docs/
