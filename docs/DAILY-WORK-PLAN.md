# DailyXeDien — Daily Work Plan

> **Source**: [Google Sheet](https://docs.google.com/spreadsheets/d/1xi5Rv1YKgoAD1wuGH0h1k-cNrX3juF2oYC10uKxvP8k/edit?gid=2085828008#gid=2085828008) + [PLAN-LOG.md](file:///d:/laragon/www/dailynew/docs/PLAN-LOG.md)
> **HTML Mockups Vercel**: [thietkedaily.vercel.app](https://thietkedaily.vercel.app/)
> **Updated**: 2026-07-08
> **Progress**: 94/115 tasks done (~81%)
> **Timeline**: T6–T9/2026 (4 tháng)

---

## ✅ ĐÃ HOÀN THÀNH (tính đến 25/06/2026)

| Phase | Done |
|-------|------|
| Htmlmau design (H1-H8) | ✅ 8/8 |
| Pre-project setup (P1-P5) | ✅ 5/5 |
| Header/Footer/ACF/Trang chủ (A-E) | ✅ 14/14 |
| HDA Plugin Fix + Admin UI (I+J) | ✅ 8/8 |
| Performance Phase A–I | ✅ 9/9 |
| WC Migration (T1-11→17) | ✅ 7/7 |
| ACF JSON Flexible (Sessions 11-17) | ✅ already done |

---

## 📅 PHẦN CÒN LẠI — "tiếp tục plan" = làm task [ ] đầu tiên

---

### ═══════ T6: TUẦN 3 (18–22/06) — Performance Wrap + WC Migration ═══════

#### Session 1 — Performance verification ✅ 2026-06-18
- [x] Đo lại QM query count homepage sau Phase G → **171 queries** (was 322, −47%)
- [x] Cập nhật KPI vào `docs/PLAN-TRACKING.md`

#### Session 2 — WC Migration: CheckoutFields ✅ 2026-06-18
- [x] T1-11. Migrate `CheckoutFields.php` — Remove billing/shipping company/address_2/postcode/country/state, reorder fields, Vietnamese labels

#### Session 3 — WC Migration: PriceDisplay ✅ 2026-06-19
- [x] T1-12. Migrate `PriceDisplay.php` — "Liên hệ" price, first variant display

#### Session 4 — WC Migration: Minor filters ✅ 2026-06-19
- [x] T1-13. Migrate srsltid redirect fix
- [x] T1-14. Migrate translation filters (Quick View, Select options)

#### Session 5 — WC Migration: Currency + archive ✅ 2026-06-19
- [x] T1-15. Migrate currency symbol ₫
- [x] T1-16. Migrate archive title cleanup (already in Optimizer.php)

---

### ═══════ T6: TUẦN 4 (23–29/06) — Test + ACF Options ═══════

#### Session 6 — Full rendering test ✅ 2026-06-19
- [x] T1-17. Test toàn bộ frontend rendering sau migration (Playlist slider refactored to theme Swiper, manual math logic eliminated)

#### Session 7 — ACF Options: audit + field spec ✅ 2026-06-19
- [x] Audit current hardcoded header/footer values → danh sách cần chuyển ACF (verified that topbar links, logo, tagline, address are already dynamic)
- [x] Add Header tab fields in options JSON (already fully covered in the schema)

#### Session 8 — ACF Options: footer + floating actions ✅ 2026-06-19
- [x] Add Floating Actions options (Phone, Zalo, back-to-top) in group_daily_options.json
- [x] Confirm Footer fields đầy đủ and integrated conditionally in footer.php

#### Session 9 — ACF Options: CLI import script ✅ 2026-06-19
- [x] Build `populate-dxd-options-data.php` (idempotent import)
- [x] Populate options from DailyXeDien source data

#### Session 10 — ACF Options: verify ✅ 2026-06-19
- [x] Run options CLI import locally
- [x] Verify options trong WP admin + frontend render đúng

---

### ═══════ T7: TUẦN 1 (01–06/07) — ACF Flexible JSON ═══════

#### Session 11 — ACF JSON: key migration decision ✅ (already done)
- [x] All JSON files already use `group_daily_*` keys — zero `group_lachuy` references remain

#### Sessions 12–15 — ACF JSON: all 12 layouts ✅ (already done)
- [x] All 12 layouts exist in `group_daily_home.json` with full sub_fields, disable toggles
- [x] hero_slider, usp_bar, categories, best_sellers, tech_spotlight, promo_banners
- [x] media_reviews, event_gallery, store_locator, brands, news, consult_form

#### Session 16 — ACF JSON: common fields + template map ✅ (already done)
- [x] Every layout has `disable` toggle
- [x] Template switch map in `template-page-home.php` covers all 12 layouts

---

### ═══════ T7: TUẦN 2 (07–13/07) — CLI Import + TSKT ═══════

#### Session 17 — CLI homepage import script ✅ (already done)
- [x] `populate-home-dailyxedien.php` exists with all 12 sections + options seeding

#### Session 18 — CLI import: run + verify ✅ 2026-06-20
- [x] Verify CLI import was run (check DB for home_sections data)
- [x] Verify idempotent re-run (không duplicate)

#### Session 19 — Full homepage smoke test ✅ 2026-06-20
- [x] Smoke test homepage desktop
- [x] Test mobile layout (drawer, sticky header, grids)

#### Session 20 — TSKT Module: core display ✅ 2026-06-20
- [x] T2-18. Code TSKTDisplay.php — ACF repeater display on product page

#### Session 21 — TSKT Module: import/export ✅ 2026-06-20
- [x] T2-19. Code `TSKTImport.php` — Admin bulk import
- [x] T2-20. Code `TSKTExport.php` — Export tool

---

### ═══════ T7: TUẦN 3 (14–20/07) — Tracking + Shortcodes ═══════

#### Session 22+23 — TrackingPixels module ✅ 2026-06-25
- [x] T2-21. Code `TrackingPixels.php` — GA4 events (gtag.js + WC e-commerce events)
- [x] T2-21b. TrackingPixels — FB Pixel events (ViewContent, InitiateCheckout, Purchase)
- [x] ACF Options: Tracking tab with 5 fields (GA4 ID, Ads Conversion, FB Pixel ID, on/off toggle)

#### Session 24 — LoanShortcode ⏸️ DEFERRED
- [x] T2-22. Code `LoanShortcode.php` — file sẵn sàng, **tắt đăng ký**. Trả góp dùng Bảo Kim, cần domain chính mới tích hợp.

#### Session 25 — SeasonalModule ✅ 2026-06-25
- [x] T2-23. Code `SeasonalModule.php` — Tet/holiday body class + announcement bar, ACF Options tab

#### Session 26A — Portfolio Gallery: CPT + Export script ✅ 2026-06-26
- [x] T2-24a. Tạo CPT `dxd_gallery` + taxonomy `dxd_gallery_cat` trong theme (tương đương `featured_item` Flatsome)
- [x] T2-24b. Viết script `export-flatsome-portfolio.php` chạy trên **web cũ** (Flatsome) — export featured_item + featured_item_category ra JSON (kèm ảnh URL)

#### Session 26B — Portfolio Gallery: Import script ✅ 2026-06-26
- [x] T2-24c. Viết script `import-portfolio-gallery.php` chạy trên **web mới** — đọc JSON → tạo `dxd_gallery` posts + `dxd_gallery_cat` terms + sideload ảnh vào Media Library

#### Session 26C — Portfolio Gallery: Homepage tab section ✅ 2026-06-26
- [x] T2-24d. Thay thế section `event_gallery` → `portfolio_gallery` trên homepage
- [x] T2-24e. ACF: field chọn danh mục portfolio cho mỗi tab (repeater: tab_label + taxonomy term)
- [x] T2-24f. Template `parts/home/portfolio-gallery.php` — UI tabs (data-fx-tabs), 4 items/row, slide nếu >4 (data-fx-slider)
- [x] T2-24g. Lightbox click ảnh (data-fx-lightbox)

#### Session 26D — Homepage News: category tabs ✅ 2026-06-27
- [x] T2-24h. Thêm tab danh mục cho phần Tin tức trang chủ (ACF repeater tabs + template rewrite, giống pattern portfolio gallery)

#### Session 27 — PolylangBridge: WooCommerceSync ✅ 2026-06-30
- [x] T2-25. Code PolylangBridge — WooCommerceSync (stock/price sync)

---

### ═══════ T7: TUẦN 4 (21–27/07) — Portfolio Gallery + Polylang ═══════

#### Session 28 — PolylangBridge: StringTranslation ✅ 2026-06-30
- [x] T2-26. Code PolylangBridge — StringTranslation (Admin UI strings)

#### Session 29 — PolylangBridge: DuplicateContent ✅ 2026-06-30
- [x] T2-27. Code PolylangBridge — DuplicateContent (Duplicate to EN)

#### Session 30 — PolylangBridge: SEO ✅ 2026-06-30
- [x] T2-28. Code PolylangBridge — SEOIntegration (Hreflang, canonical)

#### Session 31 — Content cleanup: products ✅ 2026-07-01
- [x] T2-29. Dọn SP: xóa/ẩn ngừng kinh doanh + 301 redirect (Created ContentCleanup.php & registered in Optimizer)

#### Session 32 — Content cleanup: posts + media ✅ 2026-07-01
- [x] T2-30. Dọn bài viết không liên quan (du lịch, thể thao - Disassociated post 505 from 8 categories)
- [x] T2-31. Dọn hình ảnh orphaned + chuẩn hóa alt text (Updated alt text for 404 images from filenames)

#### Session 33 — Content cleanup: taxonomy ✅ 2026-07-01
- [x] T2-32. Dọn tags/danh mục rỗng (Deleted 9 empty categories and 2 empty product categories)

---

### ═══════ T8: TUẦN 1 (01–06/08) — Frontend: Homepage + Product ═══════

#### Session 34 — Homepage template polish ✅ 2026-07-01
- [x] T3-32a. Review + refine homepage sections (spacing, alignment, colors)

#### Session 35 — Homepage template: product integration ✅ 2026-07-01
- [x] T3-32b. Test homepage với real products (sau import)

#### Session 36 — Product page: TSKT tab ✅ 2026-07-01
- [x] T3-33a. Frontend product page — TSKT specifications tab

#### Session 37 — Content Pages: CSS Styling (ALL pages)
- [x] T3-33b. Shared components CSS — breadcrumb, sidebar, buttons, icons, reveal ✅ 2026-07-03
- [x] T3-33c. Single product page CSS — sp-* classes (gallery, tabs, reviews, related) ✅ 2026-07-03
- [x] T3-33d. Archive product page CSS — archive-*, filter-* classes ✅ 2026-07-03
- [x] T3-33e. Blog listing + single post CSS — news-*, post-* classes ✅ 2026-07-03
- [x] T3-33f. About + Contact pages — CSS styling ✅ 2026-07-03
- [x] T3-33g. Cooperation page — new template + CSS ✅ 2026-07-04

---

### ═══════ T8: TUẦN 2 (07–13/08) — Interactions + Mobile ═══════

#### Session 38 — Product page: JS interactions ✅ 2026-07-04
- [x] T3-34a. Gallery click/zoom/lightbox, review stars, related slider (data-fx-slider)

#### Session 39 — Category page: AJAX filter
- [ ] T3-34b. Frontend category page — AJAX filter implementation

#### Session 40 — Mobile responsive: header + nav
- [ ] T3-35a. Mobile responsive — sticky header, bottom nav bar

#### Session 41 — Mobile responsive: grids + overflow
- [ ] T3-35b. Mobile responsive — product grids, tab overflow, long text

#### Session 42 — WooCommerce Cart & Checkout polish
- [ ] T3-35c. Verify and polish cart/checkout layouts to match mockups

---

### ═══════ T8: TUẦN 3 (14–20/08) — Performance Production ═══════

#### Session 46 — LiteSpeed/OLS config
- [ ] T3-36. Performance: LiteSpeed config cho production (cache rules, purge)

#### Session 47 — DB optimization
- [ ] T3-37. Performance: DB optimization — Revisions cleanup, transient purge

#### Session 48 — Asset preloading
- [ ] T3-38. Performance: Preload hero + fonts — fetchpriority, preconnect hints

#### Session 49 — Image optimization
- [ ] T3-39. Image optimization: WebP conversion, CDN headers, lazy-load audit

#### Session 50 — Object cache baseline
- [ ] T3-40. Redis/Memcached object cache setup + baseline measurement

---

### ═══════ T8: TUẦN 4 (21–28/08) — SEO ═══════

#### Session 51 — SEO: Rank Math migration
- [ ] T3-41. Gỡ Yoast, thống nhất Rank Math — robots.txt, sitemap

#### Session 52 — SEO: Schema markup
- [ ] T3-42a. Schema markup — Product structured data

#### Session 53 — SEO: Schema Organization + Local
- [ ] T3-42b. Schema markup — Organization, LocalBusiness

#### Session 54 — SEO: Redirect map
- [ ] T3-43. Redirect 301 map — Rank Math Redirections (old URLs → new)

---

### ═══════ T9: TUẦN 1 (01–07/09) — Verification ═══════

#### Session 55 — GSC verification
- [ ] T4-44. Verify GSC: ownership, sitemap, coverage (0 errors)

#### Session 56 — GA4 verification
- [ ] T4-45. Verify GA4: tracking, e-commerce events (purchase, add_to_cart)

#### Session 57 — Ads + Pixel verification
- [ ] T4-46. Verify Google Ads: conversion, remarketing, Merchant Center
- [ ] T4-47. Verify FB Pixel events

---

### ═══════ T9: TUẦN 2 (08–14/09) — Payment + QA ═══════

#### Session 58 — Payment E2E
- [ ] T4-48. Verify BaoKim payment E2E (test checkout flow)

#### Session 59 — Plugin verification
- [ ] T4-49. Verify DevVN Stores display
- [ ] T4-50. Verify Fluent SMTP email

#### Session 60 — Full site crawl
- [ ] T4-51. QA: Full site crawl (0 broken links) — Screaming Frog

#### Session 61 — Mobile QA
- [ ] T4-52. QA: Mobile test (3+ devices)

---

### ═══════ T9: TUẦN 3 (15–21/09) — Final QA + Deploy ═══════

#### Session 62 — Schema + PageSpeed QA
- [ ] T4-53. QA: Schema validation — Rich Results Test
- [ ] T4-54. QA: PageSpeed ≥75 mobile, ≥92 desktop

#### Session 63 — Deploy
- [ ] T4-55. Deploy: Upload theme + plugins (staged rollout)

#### Session 64 — Deactivate old plugins
- [ ] T4-56. Deploy: Deactivate old plugins (5/batch, verify each)

---

### ═══════ T9: TUẦN 4 (22–30/09) — Post-Deploy ═══════

#### Session 65 — Monitor GSC
- [ ] T4-57. Monitor: GSC daily 14 ngày (fix 404 ngay)

#### Session 66 — Monitor GA4
- [ ] T4-58. Monitor: GA4 traffic comparison vs tháng trước

---

## 🔑 CÁCH DÙNG

Khi vào conversation mới, nói **"tiếp tục plan"** → agent sẽ:

1. Đọc file `docs/DAILY-WORK-PLAN.md`
2. Tìm session có task `[ ]` đầu tiên chưa hoàn thành
3. Bắt đầu thực hiện session đó (1-2 tasks)
4. Sau khi xong → đánh `[x]` + ghi thời gian
5. Cập nhật `PLAN-LOG.md` khi xong mỗi tuần

> [!TIP]
> Mỗi session thiết kế để hoàn thành trong **1 buổi làm việc** (~2-4 giờ).
> Nếu xong sớm, agent có thể hỏi có muốn tiếp session kế tiếp không.

## 📌 BLOCKERS HIỆN TẠI

| Blocker | Area | Status |
|---------|------|--------|
| Real product data chưa import | Homepage product sections | Waiting |
| WP-CLI availability | CLI import scripts | Cần verify |
| Production server access | Deploy (T4) | Chưa đến phase |

## 📊 KPI TARGETS

| KPI | Baseline | Current | Target |
|-----|----------|---------|--------|
| Homepage DB queries | 322 | **171** (−47%) | ≤ 80 |
| Homepage page time | 2.47s | **0.56s** (−77%) | < 1s ✅ |
| Memory peak | N/A | 15.7MB | < 20MB ✅ |
| Scripts (frontend) | ~15+ | 7 | ≤ 5 |
| Styles (frontend) | ~15+ | 10 | ≤ 7 |
| Mobile PageSpeed | TBD | TBD | ≥ 75 |
| Desktop PageSpeed | TBD | TBD | ≥ 92 |
