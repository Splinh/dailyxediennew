# Homepage Work Todo

Created: 2026-06-08  
Owner: SPL/DailyXeDien implementation

Status legend:

| Status | Meaning |
|---|---|
| `[ ]` | Not started |
| `[~]` | In progress |
| `[x]` | Done |
| `[hold]` | Waiting for product import or external input |

## 1. Review and Planning

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [x] | Review project structure | root, `wp/`, `docs/` | WordPress root is `wp/`; theme is `spl`. |
| [x] | Confirm homepage render path | `templates/template-page-home.php` | Uses `home_sections` flexible content. |
| [x] | Confirm current ACF JSON | `acf-json/` | Existing Lac Huy fields need DailyXeDien migration. |
| [x] | Confirm HTML source sections | `htmlmau/index.html` | Header/footer excluded from flexible sections. |
| [x] | Create detailed implementation plan | `docs/HOMEPAGE-IMPLEMENTATION-PLAN.md` | 5-month plan created. |
| [x] | Create todo tracker | `docs/HOMEPAGE-TODO.md` | This file. |
| [x] | Create progress tracker | `docs/HOMEPAGE-PROGRESS.md` | Current progress and next actions. |
| [x] | Create plan tracking layer | `docs/PLAN-TRACKING.md` | Weekly/batch tracking against the existing Google Sheet plan. |
| [x] | Add DailyXeDien source of truth | `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md` | Live site is the source for company/contact/menu/category data. |
| [x] | Add plan-log report entry | `docs/PLAN-LOG.md` | Report entry appended for 2026-06-08. |

## 2. ACF Options: Header and Footer

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Audit current hardcoded header/footer values | `header.php`, `footer.php` | List every value that should move to ACF Options. |
| [ ] | Add Header tab in options JSON | `acf-json/group_lachuy_options.json` | Logo, tagline, topbar links, hotline label, search placeholder, cart toggle. |
| [ ] | Add Floating Actions options | `acf-json/group_lachuy_options.json` | Phone, Zalo, back-to-top. |
| [ ] | Confirm Footer fields | `acf-json/group_lachuy_options.json` | Company, desc, social, website, legal/contact fields. |
| [ ] | Update header to read only ACF/WP menu values | `header.php` | Keep fallback values safe. |
| [ ] | Update footer to read only ACF/WP menu values | `footer.php` | Keep menu locations `about-nav`, `policy-nav`. |
| [ ] | Build options CLI import script | `populate-dxd-options-data.php` | Idempotent import/update. |
| [ ] | Populate options from DailyXeDien source | `populate-dxd-options-data.php` | Use `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md`; no Lac Huy/Thaphaco values. |
| [ ] | Run options CLI import locally | WP-CLI | Requires active ACF Pro. |
| [ ] | Verify options in WP admin | ACF Options Page | Fields editable and values render. |

## 3. ACF Flexible: Homepage

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Decide key migration approach | `acf-json/group_lachuy_home.json` | Either rename to DailyXeDien or preserve stable field keys. |
| [ ] | Create `hero_slider` layout | ACF JSON | Slides, desktop/mobile images, CTAs, autoplay. |
| [ ] | Create `usp_bar` layout | ACF JSON | Benefit cards. |
| [ ] | Create `category_showcase` layout | ACF JSON | Product category cards. |
| [ ] | Create `best_sellers` layout | ACF JSON | Product tabs/query presets. |
| [ ] | Create `tech_spotlight` layout | ACF JSON | Technology highlight. |
| [ ] | Create `promo_banners` layout | ACF JSON | Promo/event banners. |
| [ ] | Create `media_reviews` layout | ACF JSON | Video/testimonials. |
| [ ] | Create `event_gallery` layout | ACF JSON | Gallery section. |
| [ ] | Create `store_locator` layout | ACF JSON | Store/dealer list. |
| [ ] | Create `brand_partners` layout | ACF JSON | Brand logos. |
| [ ] | Create `latest_news` layout | ACF JSON | Post query section. |
| [ ] | Create `consult_form` layout | ACF JSON | Shortcode/contact CTA. |
| [ ] | Add common fields to each layout | ACF JSON | `disable`, `anchor_id`, `extra_class`. |
| [ ] | Update homepage template switch map | `templates/template-page-home.php` | Map new layouts to new parts. |
| [ ] | Keep temporary fallback behavior | `templates/template-page-home.php` | Useful until ACF import is done. |

## 4. Homepage Template Parts

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Build hero slider part | `parts/home/hero-slider.php` | Use responsive images and first image eager. |
| [ ] | Build USP bar part | `parts/home/usp-bar.php` | Clean mobile horizontal/stacked behavior. |
| [ ] | Build category showcase part | `parts/home/category-showcase.php` | Link to product categories. |
| [ ] | Build best sellers part | `parts/home/best-sellers.php` | Product tabs with query settings. |
| [ ] | Build tech spotlight part | `parts/home/tech-spotlight.php` | Match HTML sample section. |
| [ ] | Build promo banners part | `parts/home/promo-banners.php` | Responsive banner grid. |
| [ ] | Build media reviews part | `parts/home/media-reviews.php` | Video popup or safe link fallback. |
| [ ] | Build event gallery part | `parts/home/event-gallery.php` | Lazy-loaded gallery. |
| [ ] | Build store locator part | `parts/home/store-locator.php` | ACF repeater first, CPT later if needed. |
| [ ] | Build brand partners part | `parts/home/brand-partners.php` | Logo list. |
| [ ] | Build latest news part | `parts/home/latest-news.php` | WP_Query posts. |
| [ ] | Build consult form part | `parts/home/consult-form.php` | Shortcode fallback if form plugin inactive. |
| [ ] | Update product card style | `parts/product-card.php` | Product import will be tested later. |

## 5. CLI Demo Import

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Create options populate script | `populate-dxd-options-data.php` | Header/footer/social/floating values. |
| [ ] | Create homepage populate script | `populate-dxd-home-data.php` | Page creation, template, front page, `home_sections`. |
| [ ] | Use DailyXeDien content defaults | populate scripts | Company/contact/category/service text from live site. |
| [ ] | Add product-section query presets | populate script | Use query type/category, not product IDs. |
| [ ] | Add idempotent page lookup | populate script | Find page by `trang-chu` before creating. |
| [ ] | Add clear CLI output | populate script | Print updated fields/sections. |
| [ ] | Run CLI import | WP-CLI | After ACF JSON is complete. |
| [ ] | Verify re-run does not duplicate | WP-CLI + WP admin | Required before commit/deploy. |

## 6. Product Data Readiness

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Product cards support no-product state | `parts/product-card.php`, product sections | Avoid broken homepage before import. |
| [ ] | Product sections query by category | `best_sellers` | Real products should appear after import. |
| [ ] | Product sections query sale/featured/newest | `best_sellers` | Use WooCommerce metadata/API safely. |
| [hold] | Test with imported products | WooCommerce | Waiting for user product import. |
| [hold] | Verify category tabs after import | Homepage | Waiting for user product import. |
| [hold] | Verify sale price/badges after import | Product card | Waiting for user product import. |

## 7. Build and QA

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Run theme build | `pnpm build:theme` or theme `npm run build` | Regenerate Vite/Tailwind assets. |
| [ ] | Smoke test homepage | Browser/local site | No PHP warnings. |
| [ ] | Test desktop layout | Browser | Header, nav, hero, sections, footer. |
| [ ] | Test mobile layout | Browser | Drawer, sticky header, product grids, long text. |
| [ ] | Test ACF changes reflect frontend | WP admin + frontend | Edit fields, refresh frontend. |
| [ ] | Test CLI import after clean data | WP-CLI | Re-run safe. |

## 8. Later Performance and Scaling

| Status | Task | File/Area | Notes |
|---|---|---|---|
| [ ] | Remove unused legacy CSS | theme CSS includes | Only after all pages migrated. |
| [ ] | Audit homepage DB queries | Query Monitor/local debug | Especially product sections. |
| [ ] | Cache product query IDs | `inc/product-cache.php` | Existing helper can be reused. |
| [ ] | Image optimization | Vite/images/CDN | Convert/compress hero and product images. |
| [ ] | Page cache baseline | `docs/PERFORMANCE.md` | After feature-complete. |
| [ ] | Redis/object cache baseline | `docs/SCALING.md` | After staging. |
| [ ] | PageSpeed target pass | Lighthouse/PageSpeed | Mobile >= 75, desktop >= 92 target. |
