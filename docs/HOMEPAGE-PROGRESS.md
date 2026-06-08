# Homepage Progress

Created: 2026-06-08  
Last updated: 2026-06-08

## Current Status

Overall status: planning/review complete; implementation not started in this pass.

| Area | Status | Notes |
|---|---|---|
| Project review | Done | Confirmed WordPress root, theme, ACF JSON, homepage template, HTML sample. |
| Header/footer plan | Done | Header/footer should be controlled by ACF Options Page. |
| Flexible section plan | Done | New DailyXeDien flexible layouts are specified. |
| Product section plan | Done | Product sections will be implemented now but tested with real products after user import. |
| CLI import plan | Done | Two idempotent import scripts planned. |
| Tracking layer | Done | Added `docs/PLAN-TRACKING.md` to track weekly/batch progress against the Google Sheet. |
| DailyXeDien source of truth | Done | Added `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md`; live site is primary business-data source. |
| Performance/scaling plan | Deferred | Target after feature completion, using existing docs. |
| Code implementation | Not started | No PHP/ACF JSON implementation changes made in this pass. |
| CLI import execution | Not started | Waiting until ACF JSON and scripts are created. |

## Files Created

| File | Purpose |
|---|---|
| `docs/HOMEPAGE-IMPLEMENTATION-PLAN.md` | Detailed 5-month implementation and scaling plan. |
| `docs/HOMEPAGE-TODO.md` | Checklist of all work items. |
| `docs/HOMEPAGE-PROGRESS.md` | Progress tracker and next steps. |
| `docs/PLAN-TRACKING.md` | Tracking layer for Google Sheet plan: weekly snapshots, batch reports, blockers, decisions, KPI tracking. |
| `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md` | Source of truth for DailyXeDien company/contact/menu/category/service data. |

## Files Updated

| File | Purpose |
|---|---|
| `docs/PLAN-LOG.md` | Appended 2026-06-08 planning/report entry. |

## Review Findings

1. `templates/template-page-home.php` is the correct homepage template. It uses `Helper::getField('home_sections')`.
2. Existing home flexible layouts are old Lac Huy/demo layouts and should be migrated to DailyXeDien layouts.
3. `header.php` and `footer.php` are already modified in the current worktree. Next implementation should work with those edits, not reset them.
4. Existing ACF Options already cover contact/footer/social/product trust partially, but header-specific options are missing or incomplete.
5. `htmlmau/index.html` contains the homepage section sequence to follow:
   - header/nav
   - hero
   - quick benefit/category areas
   - best sellers
   - technology spotlight
   - promo/widgets/media/event sections
   - store section
   - brand partners
   - news
   - consult form
   - footer
6. Header/footer should not be flexible sections. They should be global ACF Options plus WP menus.
7. Product sections should not rely on fixed sample product IDs because the user will import real products later.
8. Root `vendor/` is not present in this checkout, so WP-CLI command availability must be verified before running import scripts.
9. Google Sheet is the master 4-month plan; repo docs should add implementation tracking, not replace the Sheet with an over-fixed schedule.
10. Because this is a rebuild of `dailyxedien.vn`, all real business data should come from the live site. `htmlmau` is layout reference only.

## Next Implementation Batch

### Batch 1: ACF Options and Header/Footer

Target:

- Add/confirm ACF Options fields.
- Connect header/footer to those fields.
- Create `populate-dxd-options-data.php`.

Completion criteria:

- Header/footer values are editable from ACF Options.
- WP menu locations still control navigation.
- CLI script can populate options without duplicating anything.

### Batch 2: Flexible JSON and Template Map

Target:

- Replace/migrate homepage flexible layouts.
- Update `template-page-home.php`.
- Create new `parts/home/*` files for new layouts.

Completion criteria:

- Homepage sections can be reordered and disabled in ACF.
- Missing product data does not break the page.
- Each section has a clean fallback.

### Batch 3: CLI Homepage Import

Target:

- Create `populate-dxd-home-data.php`.
- Import sample data based on `htmlmau/index.html`.
- Set front page and page template.

Completion criteria:

- Running the script creates/updates the homepage.
- Re-running the script does not duplicate the page.
- Section count and warnings are printed in CLI.

### Batch 4: Product Data Test

Target:

- Wait for user product import.
- Test categories, product tabs, product cards, sale badges, and empty states.

Completion criteria:

- Imported products appear in the correct homepage tabs.
- Product card layout remains stable on mobile and desktop.

### Batch 5: Performance and Scaling

Target:

- Run production build.
- Optimize CSS/JS/images.
- Apply caching and WooCommerce performance checks.

Completion criteria:

- Performance baseline measured after features are complete.
- PageSpeed target pass is realistic and documented.

## Risks

| Risk | Impact | Mitigation |
|---|---|---|
| Changing ACF field keys breaks existing DB values | High | Prefer stable field names; regenerate keys only with controlled migration. |
| Product import categories do not match planned tabs | Medium | Use flexible query presets and allow category selection in ACF. |
| Header/footer current WIP differs from final ACF plan | Medium | Audit current modified files before editing. |
| Tailwind classes not built | Medium | Run `pnpm build:theme` or theme `npm run build` after template work. |
| Performance work too early causes rework | Medium | Keep performance/scaling after feature completion. |
| Accidentally importing Lac Huy/Thaphaco demo data | High | Use `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md` for options/home populate scripts. |

## Last Report

2026-06-08:

- Reviewed project structure and homepage architecture.
- Confirmed ACF flexible field `home_sections`.
- Confirmed existing old layouts and required DailyXeDien replacements.
- Created implementation notes, todo tracker, progress tracker, and plan tracking file.
- Added tracking approach for the existing Google Sheet plan.
- Added DailyXeDien source-of-truth file so future ACF/CLI imports use live-site data.
- Added plan-log entry for future reporting.
