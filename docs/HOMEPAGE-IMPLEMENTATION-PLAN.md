# DailyXeDien Homepage Implementation Notes

Created: 2026-06-08  
Scope: review project, plan homepage rebuild, ACF flexible content, ACF options, demo data import, and long-range delivery plan.

## 0. Relationship To Google Sheet Plan

Google Sheet `DailyXeDien_Rebuild_Plan` remains the master 4-month plan. The Sheet currently defines the T6-T9/2026 roadmap with major phases: setup/import, child theme scaffold, logic migration, modules, content cleanup, frontend, performance, SEO, tracking, QA, deploy, and post-launch monitoring.

This repo document is not a replacement for that Sheet. Because the 4-month plan has been repeated/planned but not fully implemented yet, the detailed work below should be treated as implementation notes and tracking support, not as a fixed committed schedule.

Tracking is now split into:

| File | Role |
|---|---|
| `docs/PLAN-LOG.md` | High-level report/changelog for future work. |
| `docs/PLAN-TRACKING.md` | Weekly/batch tracking, blockers, decisions, KPI status. |
| `docs/HOMEPAGE-TODO.md` | Work checklist for homepage/header/footer/flexible sections. |
| `docs/HOMEPAGE-PROGRESS.md` | Current status of the homepage batch. |
| `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md` | DailyXeDien live-site content source for company/contact/menu/category data. |

## 0.1 Content Source Rule

This rebuild is based on `dailyxedien.vn`. Business data must come from the current DailyXeDien site, not Lac Huy/Thaphaco demo content.

Use this priority:

1. `dailyxedien.vn` for company info, contact, menu/category structure, service text, footer content, product/news categories.
2. Google Sheet for roadmap/tracking.
3. `htmlmau/index.html` for layout/design reference.
4. Existing SPL theme for technical patterns.
5. Old Lac Huy/Thaphaco data only as temporary placeholder while developing.

## 1. Project Review

### Current structure

| Area | Current finding | Decision |
|---|---|---|
| WordPress root | `wp/` is the WordPress install, root has `wp-cli.yml` with `path: wp` | Use WP-CLI from project root for import scripts. |
| Main theme | `wp/wp-content/themes/spl` | Continue inside `spl` theme; avoid child theme unless required later. |
| Front page template | `wp/wp-content/themes/spl/templates/template-page-home.php` | This is the correct homepage entry. It reads `home_sections` and maps layouts to `parts/home/*`. |
| Existing flexible JSON | `acf-json/group_lachuy_home.json` | Current layouts are old Lac Huy/demo layouts. Replace or migrate to DailyXeDien layouts. |
| Existing options JSON | `acf-json/group_lachuy_options.json` | Already has contact/footer/social fields. Needs header/footer expansion for DailyXeDien. |
| Header/footer | `header.php` and `footer.php` are already modified in the worktree | Treat these as current WIP. Next step is to align fields and remove hardcoded values into ACF Options. |
| HTML source | `htmlmau/index.html` | Use as the visual/content source for homepage sections, except header/footer which use theme files + ACF options. |
| Business data source | `https://dailyxedien.vn/` | Source of truth for company, contact, menu, categories, footer, services and content labels. |
| Product source | WooCommerce is installed | Product sections should be implemented now with query settings and empty/fallback states; real product data will be imported by the user later. |
| Performance docs | `docs/PERFORMANCE.md`, `docs/SCALING.md` exist | Use them after feature completion; performance/scaling is a later target, not the first milestone. |

### Current homepage flexible layouts

Existing `group_lachuy_home.json` layouts:

| Existing layout | Keep? | Notes |
|---|---:|---|
| `hero` | Replace | Static Lac Huy style, not matching DailyXeDien hero slider. |
| `features` | Replace with `usp_bar` | Keep concept only. |
| `flash_sale` | Replace/expand | Needs EV/product-card style and product query controls. |
| `categories` | Replace/expand | Needs category quick links/cards from `htmlmau/index.html`. |
| `products` | Replace/expand | Needs tabs/query presets and product empty state. |
| `about` | Replace | DailyXeDien homepage does not use old herb brand story section directly. |
| `blog` | Replace/expand | Map to latest news section from HTML sample. |

### Current render map

`templates/template-page-home.php` currently maps:

```php
hero        => parts/home/hero.php
features    => parts/home/features.php
flash_sale  => parts/home/flash-sale.php
categories  => parts/home/categories.php
products    => parts/home/products.php
about       => parts/home/about.php
blog        => parts/home/blog.php
```

New map should become:

```php
hero_slider      => parts/home/hero-slider.php
usp_bar          => parts/home/usp-bar.php
category_showcase=> parts/home/category-showcase.php
best_sellers     => parts/home/best-sellers.php
tech_spotlight   => parts/home/tech-spotlight.php
promo_banners    => parts/home/promo-banners.php
media_reviews    => parts/home/media-reviews.php
event_gallery    => parts/home/event-gallery.php
store_locator    => parts/home/store-locator.php
brand_partners   => parts/home/brand-partners.php
latest_news      => parts/home/latest-news.php
consult_form     => parts/home/consult-form.php
```

## 2. Architecture Decisions

### Header/footer

Header and footer must use ACF Options Page, not page flexible content.

Fields to add or confirm in `group_lachuy_options.json`:

| Field | Type | Purpose |
|---|---|---|
| `logo` | image | Header logo override. |
| `logo_tagline` | text | Small tagline under/near logo. |
| `hotline_label` | text | Label above hotline. |
| `hotline` | text | Primary hotline. |
| `secondary_hotline` | text | Optional secondary hotline. |
| `topbar_links` | repeater/link | Top utility links. |
| `header_search_placeholder` | text | Product search placeholder. |
| `header_cart_enabled` | true_false | Show/hide cart action. |
| `floating_actions` | repeater | Phone/Zalo/back-to-top floating buttons. |
| `footer_desc` | textarea | Main footer intro text. |
| `company_name` | text | Legal/company name. |
| `address` | text | Main address. |
| `website_url` | url/text | Website URL. |
| `facebook_url`, `youtube_url`, `zalo_url`, `tiktok_url` | url | Social URLs. |
| `product_trust` | repeater | Trust badges used by product pages. |

Default values for these fields must come from `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md`, including:

- Company: `Công ty TNHH Xe Điện BLUERA Việt Nhật`
- Tax code: `0312473259`
- Hotline: `0933 505 222`
- Landline: `028 2253 0524`
- Email: `Dailyxedien.vn@gmail.com`
- Address: `466 Nguyễn Duy Trinh, P. Bình Trưng, TP. Hồ Chí Minh`
- Working hours: `Thứ 2 - Chủ nhật (8:00AM - 08:00PM)`

Footer menus should stay as WP menu locations:

| Menu location | Use |
|---|---|
| `about-nav` | Footer information/support links. |
| `policy-nav` | Policy links. |
| `main-nav` | Desktop main navigation. |
| `mobile-nav` | Mobile drawer navigation. |

### Homepage sections

Homepage must use one ACF flexible field:

| Field | Type | Location |
|---|---|---|
| `home_sections` | flexible_content | Page template `templates/template-page-home.php` |

Each layout needs:

| Common field | Type | Purpose |
|---|---|---|
| `disable` | true_false | Hide section without deleting data. |
| `anchor_id` | text | Optional section anchor for nav links. |
| `extra_class` | text | Controlled custom class for small adjustments. |

### Product sections

Build product sections now, but do not depend on imported products being available.

Rules:

1. Product layouts query WooCommerce by category/tag/featured/sale/newest.
2. If no products exist, render a clean empty state visible only to editors/admins or a static placeholder in local/demo mode.
3. Product card should be data-driven and use WooCommerce APIs only.
4. Avoid storing product IDs in sample data unless required; prefer query presets so imported products show automatically.
5. After user imports products, test with real categories, prices, sale badges, stock status, variations, and images.

## 3. Flexible Layout Specification

### 3.1 `hero_slider`

Purpose: full-width homepage hero from `htmlmau/index.html`.

Fields:

| Field | Type | Notes |
|---|---|---|
| `slides` | repeater | 1-5 slides. |
| `slides.image_desktop` | image | Required for production. |
| `slides.image_mobile` | image | Optional mobile crop. |
| `slides.badge` | text | Small label. |
| `slides.title` | textarea | Allows line breaks, not arbitrary HTML unless needed. |
| `slides.description` | textarea | Short supporting text. |
| `slides.primary_link` | link | CTA. |
| `slides.secondary_link` | link | Optional CTA. |
| `autoplay` | true_false | Default on. |
| `delay_ms` | number | Default 5500. |

Template: `parts/home/hero-slider.php`  
JS: hero slider module in `inc/dxd-ui.js` or a dedicated home JS file.

### 3.2 `usp_bar`

Purpose: quick benefit cards under hero.

Fields:

| Field | Type |
|---|---|
| `items` | repeater |
| `items.icon` | select |
| `items.title` | text |
| `items.description` | text |
| `items.link` | link |

Template: `parts/home/usp-bar.php`

### 3.3 `category_showcase`

Purpose: homepage category cards/quick links.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `subtitle` | text |
| `items` | repeater |
| `items.product_cat` | taxonomy: product_cat |
| `items.title_override` | text |
| `items.image` | image |
| `items.badge` | text |
| `columns_desktop` | select |

Template: `parts/home/category-showcase.php`

### 3.4 `best_sellers`

Purpose: product tabs/grid from `#best-sellers`.

Fields:

| Field | Type | Notes |
|---|---|---|
| `heading` | text | Example: "Xe bán chạy". |
| `subtitle` | text | Optional. |
| `tabs` | repeater | Each tab has query settings. |
| `tabs.label` | text | Tab label. |
| `tabs.query_type` | select | newest, sale, featured, category, manual. |
| `tabs.category` | taxonomy | Used when query type is category. |
| `tabs.manual_products` | relationship | Optional, can stay empty until product import. |
| `tabs.count` | number | Default 8. |
| `columns_desktop` | select | 3, 4, 5. |
| `show_view_all` | true_false | Default true. |
| `view_all_link` | link | Shop/archive URL. |

Template: `parts/home/best-sellers.php`  
Product card: `parts/product-card.php`

### 3.5 `tech_spotlight`

Purpose: technology/feature highlight section from `#ai-tech-spotlight`.

Fields:

| Field | Type |
|---|---|
| `eyebrow` | text |
| `heading` | text |
| `description` | textarea |
| `image` | image |
| `features` | repeater |
| `features.icon` | select |
| `features.title` | text |
| `features.description` | textarea |
| `link` | link |

Template: `parts/home/tech-spotlight.php`

### 3.6 `promo_banners`

Purpose: promotional/event/banner cards.

Fields:

| Field | Type |
|---|---|
| `items` | repeater |
| `items.image` | image |
| `items.title` | text |
| `items.description` | textarea |
| `items.link` | link |
| `layout_style` | select |

Template: `parts/home/promo-banners.php`

### 3.7 `media_reviews`

Purpose: video/testimonial widget section.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `video_url` | oembed/url |
| `video_thumbnail` | image |
| `testimonials` | repeater |
| `testimonials.name` | text |
| `testimonials.role` | text |
| `testimonials.content` | textarea |
| `testimonials.rating` | number |

Template: `parts/home/media-reviews.php`

### 3.8 `event_gallery`

Purpose: standalone event images section.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `images` | gallery |
| `link` | link |

Template: `parts/home/event-gallery.php`

### 3.9 `store_locator`

Purpose: store/dealer area from `#store-section`.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `description` | textarea |
| `stores` | repeater |
| `stores.name` | text |
| `stores.address` | text |
| `stores.phone` | text |
| `stores.map_url` | url |
| `stores.region` | select/text |
| `cta_link` | link |

Template: `parts/home/store-locator.php`

Later: if dealer data becomes a CPT, replace repeater with CPT query.

### 3.10 `brand_partners`

Purpose: partner/brand logo section.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `brands` | repeater |
| `brands.logo` | image |
| `brands.name` | text |
| `brands.link` | url |

Template: `parts/home/brand-partners.php`

### 3.11 `latest_news`

Purpose: latest news section from `#news-section`.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `subtitle` | text |
| `category` | taxonomy: category |
| `count` | number |
| `view_all_link` | link |

Template: `parts/home/latest-news.php`

### 3.12 `consult_form`

Purpose: consultation/contact form area from `#consult-form`.

Fields:

| Field | Type |
|---|---|
| `heading` | text |
| `description` | textarea |
| `form_shortcode` | text |
| `hotline_override` | text |
| `background_image` | image |

Template: `parts/home/consult-form.php`

## 4. CLI Demo Data Plan

Create two scripts:

| Script | Purpose |
|---|---|
| `wp/wp-content/themes/spl/populate-dxd-options-data.php` | Populate ACF options for header/footer/social/footer/floating buttons. |
| `wp/wp-content/themes/spl/populate-dxd-home-data.php` | Create/update homepage, set template/front page, populate `home_sections`. |

Expected command:

```bash
vendor/bin/wp eval-file wp/wp-content/themes/spl/populate-dxd-options-data.php
vendor/bin/wp eval-file wp/wp-content/themes/spl/populate-dxd-home-data.php
```

Fallback command if root Composer vendor is not installed but WP-CLI bundle is installed elsewhere:

```bash
php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-dxd-options-data.php
php vendor/wp-cli/wp-cli/php/boot-fs.php --path=wp eval-file wp/wp-content/themes/spl/populate-dxd-home-data.php
```

Import rules:

1. Scripts must be idempotent: re-run should update existing page/options, not duplicate pages.
2. Scripts must check `function_exists('update_field')`.
3. Scripts should use field names for normal fields, but field keys for fields where ACF references are fragile.
4. Product sections should use query presets, not fixed product IDs.
5. Script should print a clear report: page ID, updated option count, section count, warnings.
6. Script must not populate Lac Huy/Thaphaco demo company data.
7. Script should use DailyXeDien live-site defaults from `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md`.

## 5. Roadmap Interpretation

The original Sheet already has the 4-month timeline. The table below is only a tracking interpretation for repo execution. Dates should be updated when implementation actually starts.

### Phase 1: Homepage Foundation and ACF Migration

Goal: homepage can be controlled by ACF and matches the HTML sample structurally.

| Week | Milestone | Tasks | Done when |
|---|---|---|---|
| W1 | Field architecture | Review existing ACF JSON; define DailyXeDien options fields; define new flexible layouts; decide whether to rename `group_lachuy_*` to `group_daily_*` or preserve keys during migration. | ACF field spec approved and local JSON committed. |
| W2 | Header/footer options | Expand options JSON; map header/footer to ACF values; keep WP menus for nav; remove hardcoded DailyXeDien contact values where possible. | Header/footer render correctly with ACF values and fallbacks. |
| W3 | Homepage flexible MVP | Create/replace flexible JSON; update `template-page-home.php`; build `hero_slider`, `usp_bar`, `category_showcase`, `best_sellers`. | Front page loads with first 4 sections from ACF. |
| W4 | Demo import | Build CLI populate scripts; import sample homepage/options data; verify re-run behavior. | CLI creates/updates homepage and options without duplication. |

Month 1 verification:

- `home_sections` visible in WP admin.
- Header/footer values can be changed from ACF Options.
- Homepage template renders without PHP warnings.
- Product sections render cleanly even before product import.

### Phase 2: Complete Homepage and Product-Ready UX

Goal: full homepage matches `htmlmau/index.html` section coverage and is ready for real products.

| Week | Milestone | Tasks | Done when |
|---|---|---|---|
| W5 | Remaining content sections | Implement `tech_spotlight`, `promo_banners`, `media_reviews`, `event_gallery`. | Middle homepage sections render from ACF. |
| W6 | Store/brand/news/form | Implement `store_locator`, `brand_partners`, `latest_news`, `consult_form`. | All sections from `htmlmau/index.html` have a flexible equivalent. |
| W7 | Product card and product tabs | Update product card style; support sale/new/featured/category/manual queries; add empty states. | Product sections are ready for imported WooCommerce data. |
| W8 | Responsive and JS polish | Hero slider, tab switching, drawers, dropdowns, modal/video handling, mobile overflow checks. | Desktop/mobile screenshots match expected layout. |

Month 2 verification:

- All homepage sections can be reordered/disabled.
- No section depends on static HTML.
- Product sections do not break with zero products.
- Product sections update automatically after product import.

### Phase 3: Content Import, SEO, Tracking, and QA

Goal: real data starts replacing sample data; tracking and SEO are ready for staging.

| Week | Milestone | Tasks | Done when |
|---|---|---|---|
| W9 | Product import support | User imports products; verify categories, images, prices, sale flags, stock, product card output. | Product sections render real imported products. |
| W10 | Page content expansion | Map other `htmlmau` pages into templates/specs: product archive, single product, dealer pages, collaboration page, about page. | Non-homepage priority pages have implementation backlog. |
| W11 | SEO basics | Homepage meta, Organization/LocalBusiness schema, breadcrumb consistency, canonical, sitemap check. | Homepage and product sections pass basic SEO checks. |
| W12 | Tracking basics | GA4, Ads, Meta Pixel event plan; WooCommerce events plan; contact form conversion events. | Tracking checklist ready before staging deploy. |

Month 3 verification:

- Homepage works with real products.
- SEO fields and schema do not duplicate plugin output.
- Analytics events are documented before production.

### Phase 4: Performance, Scaling Baseline, and Staging Deploy

Goal: after features are complete, optimize for speed and scaling.

| Week | Milestone | Tasks | Done when |
|---|---|---|---|
| W13 | Frontend performance | Remove unused legacy CSS, build Tailwind production CSS, optimize JS, preload hero image, lazy-load below-fold images. | Homepage assets are production-built and lean. |
| W14 | WooCommerce performance | Product query caching, product card cache priming, avoid N+1 queries, audit transients. | Homepage product sections do not create excessive DB queries. |
| W15 | Cache and infra baseline | Apply items from `docs/PERFORMANCE.md` and `docs/SCALING.md`: page cache, object cache, CDN headers, image formats. | Staging has measurable cache HIT behavior. |
| W16 | Staging QA | Full responsive QA, form QA, menu QA, product flow QA, PageSpeed/Lighthouse baseline. | Staging approved for production prep. |

Month 4 verification:

- PageSpeed target baseline: mobile >= 75, desktop >= 92 after caching.
- No PHP warnings with WP_DEBUG.
- No layout shift from product cards or missing images.

### Phase 5: Production Stabilization and Growth

Goal: deploy safely, monitor, then improve scale and conversion.

| Week | Milestone | Tasks | Done when |
|---|---|---|---|
| W17 | Production deployment | Backup, deploy theme/ACF JSON/scripts, run import, clear caches, smoke test. | Production homepage works and uses ACF data. |
| W18 | Post-deploy monitoring | Monitor GSC, GA4, server logs, cache logs, WooCommerce errors, conversion paths. | No critical production issues for 7 days. |
| W19 | Scaling improvements | Tune cache purge, Redis, query indexes if needed, image CDN rules, product archive performance. | Site stable under expected traffic. |
| W20 | Conversion iteration | Improve homepage sections based on data: product tabs, CTAs, consultation form, store locator. | First post-launch optimization batch shipped. |

Month 5 verification:

- Stable production operation.
- Scaling/performance target has real measurements.
- Plan log has weekly reports and decisions.

## 6. Definition of Done

Homepage is done when:

1. Header/footer content comes from ACF Options with safe fallbacks.
2. Homepage sections come from ACF Flexible Content.
3. All major sections in `htmlmau/index.html` have matching flexible layouts.
4. CLI scripts can create/update sample homepage data.
5. Product sections work before and after product import.
6. Mobile, tablet, and desktop layouts are verified.
7. No PHP warnings/errors in normal rendering.
8. Tailwind/Vite production build is regenerated.
9. Performance pass is completed after feature completion.

## 7. Open Decisions

| Decision | Recommendation |
|---|---|
| Rename `group_lachuy_*` keys? | Rename titles/filenames for clarity, but preserve field names where existing code depends on them. Field keys can be regenerated only if DB migration is controlled. |
| Header/footer flexible or options? | Options only, as requested. |
| Product IDs in sample data? | Avoid fixed IDs; use category/query presets so imported products appear automatically. |
| Store locator data source | Start with ACF repeater; later migrate to dealer CPT if dealer pages become data-heavy. |
| Performance timing | After homepage and core WooCommerce surfaces are feature-complete. |
