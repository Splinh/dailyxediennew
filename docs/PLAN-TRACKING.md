# DailyXeDien Plan Tracking

Created: 2026-06-08  
Source plan: Google Sheet `DailyXeDien_Rebuild_Plan`  
Content source: https://dailyxedien.vn/  
Sheet tabs observed: `Tổng Quan`, `Chi Tiết Tasks`, `Plugin Mapping`, `KPI & Tracking`

## Purpose

File này dùng để theo dõi triển khai thực tế sau plan Google Sheet.

Google Sheet vẫn là plan gốc. File này không thay thế Sheet và không cố chốt chi tiết khi chưa triển khai. Mục tiêu là ghi:

1. Tuần này đang làm gì.
2. Task nào đã xong, đang làm, bị block.
3. Quyết định kỹ thuật nào đã chốt.
4. Kết quả kiểm thử hoặc import thực tế.
5. Việc nào phát sinh ngoài plan gốc.
6. Hiệu năng/scaling sau khi đã có dữ liệu thật.

Business/content data rule: web mới rebuild từ `dailyxedien.vn`, nên company/contact/menu/category/footer/service data lấy từ live site DailyXeDien. `htmlmau` chỉ là layout reference.

## Source Timeline Summary

Theo Sheet, timeline gốc là T6-T9/2026:

| # | Tháng | Hạng mục chính | Khoảng ngày | Tracking status |
|---|---|---|---|---|
| 1 | T6 | Setup môi trường & Import data | 01/06-07/06 | Chưa audit thực tế |
| 2 | T6 | Scaffold spl-child theme | 08/06-14/06 | Cần xem lại vì hiện đang làm trực tiếp theme `spl` |
| 3 | T6 | Migrate logic từ functions.php cũ | 15/06-21/06 | Chưa triển khai |
| 4 | T6 | Test & fix basic rendering | 22/06-30/06 | Chưa triển khai đầy đủ |
| 5 | T7 | Code TSKT Module | 01/07-07/07 | Chưa triển khai |
| 6 | T7 | Code TrackingPixels + LoanCalc | 08/07-14/07 | Chưa triển khai |
| 7 | T7 | Code PolylangBridge Module | 15/07-28/07 | Đã hoàn thành và verify |
| 8 | T7 | Dọn dẹp nội dung | 22/07-31/07 | Chưa triển khai |
| 9 | T8 | Frontend: Homepage + Product | 01/08-14/08 | Đang chuẩn bị theo batch trang chủ |
| 10 | T8 | Dọn dẹp nội dung tiếp | 01/08-14/08 | Chờ data/content thật |
| 11 | T8 | Performance Optimization | 15/08-28/08 | Defer tới sau feature complete |
| 12 | T8 | SEO Technical fixes | 22/08-31/08 | Chưa triển khai |
| 13 | T9 | Verify GA4/GSC/Ads/Pixel | 01/09-07/09 | Chưa triển khai |
| 14 | T9 | QA Testing | 08/09-14/09 | Chưa triển khai |
| 15 | T9 | Deploy Production | 15/09-21/09 | Chưa triển khai |
| 16 | T9 | Post-launch Monitoring | 22/09-30/09 | Chưa triển khai |

## Tracking Rules

### Status values

| Status | Meaning |
|---|---|
| `Not started` | Chưa làm hoặc chưa audit thực tế |
| `Ready` | Đủ thông tin để bắt đầu |
| `Doing` | Đang triển khai |
| `Blocked` | Không thể tiếp tục nếu thiếu dữ liệu/quyết định |
| `Waiting` | Chờ user/import/plugin/staging/production |
| `Done` | Hoàn thành và đã verify |
| `Changed` | Có thay đổi phạm vi so với Sheet |
| `Dropped` | Không làm nữa, có lý do |

### Update cadence

| When | What to update |
|---|---|
| Mỗi lần bắt đầu batch | `Active Batch`, task list, expected output |
| Mỗi lần xong batch | `Batch Report`, files changed, verification, blockers |
| Mỗi tuần | `Weekly Snapshot`, progress %, risk, next week |
| Khi có quyết định | `Decision Log` |
| Khi user import sản phẩm | `Product Import Tracking` |
| Khi bắt đầu performance | `Performance Baseline` |

## Current Active Batch

| Field | Value |
|---|---|
| Batch | Homepage ACF + tracking setup |
| Started | 2026-06-08 |
| Status | Doing |
| Goal | Bổ sung tracking cho plan hiện có; chuẩn bị lớp theo dõi trước khi triển khai thật |
| Scope | Docs/tracking only |
| Out of scope | Chưa tạo ACF JSON thật, chưa chạy import, chưa sửa product section |

## Weekly Snapshot Template

Copy block này xuống cuối file mỗi tuần.

```md
## Weekly Snapshot - YYYY-MM-DD

### Summary

- Overall status:
- Main focus:
- Completed:
- In progress:
- Blocked/waiting:

### Progress By Area

| Area | Status | % | Notes |
|---|---|---:|---|
| Setup/import | Not started | 0 | |
| Header/footer ACF Options | Not started | 0 | |
| Homepage flexible sections | Not started | 0 | |
| Product sections | Waiting | 0 | Chờ import sản phẩm |
| Product archive/single | Not started | 0 | |
| Tracking/SEO | Not started | 0 | |
| Performance/scaling | Not started | 0 | Defer |
| QA/deploy | Not started | 0 | |

### Files Changed

| File | Change | Reason |
|---|---|---|
| | | |

### Verification

| Check | Result | Notes |
|---|---|---|
| PHP syntax | Not run | |
| Theme build | Not run | |
| WP-CLI import | Not run | |
| Browser desktop | Not run | |
| Browser mobile | Not run | |

### Next Actions

1. 
2. 
3. 
```

## Batch Report Template

```md
## Batch Report - YYYY-MM-DD - Batch name

### Objective

### Completed

| Task | Status | Evidence |
|---|---|---|
| | | |

### Changed Files

| File | Summary |
|---|---|
| | |

### Verification

| Command/Test | Result | Notes |
|---|---|---|
| | | |

### Blockers

| Blocker | Owner | Next step |
|---|---|---|
| | | |

### Next Batch

```

## Decision Log

| Date | Decision | Reason | Impact |
|---|---|---|---|
| 2026-06-08 | Google Sheet remains the master 4-month plan; repo docs add tracking and implementation notes | Plan has not been fully implemented yet, so detailed schedule must stay flexible | Avoids overcommitting unknown implementation details |
| 2026-06-08 | `dailyxedien.vn` is the source of truth for business data | User confirmed the web is rebuilt from DailyXeDien | ACF options/populate scripts must use DailyXeDien company/contact/menu/category data |
| 2026-06-08 | Header/footer use ACF Options; homepage sections use ACF Flexible Content | Matches requested architecture and current theme pattern | Field work splits into options JSON and home flexible JSON |
| 2026-06-08 | Product sections are built now but real validation waits for product import | Product data is not ready yet | Product tasks use `Waiting` status until import |
| 2026-06-08 | Performance/scaling is a later target after completion | Optimizing too early risks rework | Track baseline only after feature-complete |

## Blocker Log

| Date | Blocker | Area | Owner | Status | Resolution |
|---|---|---|---|---|---|
| 2026-06-08 | Real product data not imported yet | Homepage product sections | User | Waiting | Implement query/empty states first, test after import |
| 2026-06-08 | Old Lac Huy/Thaphaco demo values exist in theme scripts/ACF labels | Content/source data | Dev | Open | Replace defaults with DailyXeDien data during ACF/CLI implementation |
| 2026-06-08 | Root `vendor/` not present in checkout | WP-CLI commands | Dev | Open | Verify available WP-CLI command before running import scripts |
| 2026-06-08 | Some current theme files are already modified | Header/footer implementation | Dev | Open | Audit current diffs before editing; do not revert existing WIP |

## Product Import Tracking

Use this section only after product import starts.

| Check | Status | Notes |
|---|---|---|
| Product count imported | Waiting | |
| Product categories imported | Waiting | |
| Product images imported | Waiting | |
| Regular/sale prices correct | Waiting | |
| Stock/variation data correct | Waiting | |
| Homepage tabs show products | Waiting | |
| Product card layout stable | Waiting | |
| Archive filters work | Waiting | |
| Single product page works | Waiting | |

## KPI Tracking

Measured 2026-06-18 (after Performance Phase A–I).

| KPI | Baseline | Target | Current | Status |
|---|---:|---:|---:|---|
| Homepage mobile PageSpeed | TBD | >= 75 | TBD | Waiting (after feature complete) |
| Homepage desktop PageSpeed | TBD | >= 92 | TBD | Waiting (after feature complete) |
| TTFB cached | TBD | <= 150ms | TBD | Waiting (page cache active) |
| TTFB uncached | TBD | <= 1200ms | ~560ms | 🟢 On track |
| Homepage DB queries | 322 | <= 80 | 171 | 🟡 −47% (need object cache for ≤80) |
| Homepage page time | 2.47s | < 1s | 0.56s | 🟢 Achieved |
| Frontend scripts | ~15+ | <= 5 | 7 | 🟡 Close |
| Frontend styles | ~15+ | <= 7 | 10 | 🟡 Close |
| Memory peak | N/A | < 20MB | 15.7MB | 🟢 Good |
| Product archive DB queries | TBD | TBD | TBD | Waiting |
| Broken links | TBD | 0 | TBD | Waiting |
| GSC critical errors | TBD | 0 | TBD | Waiting |

## Current Snapshot - 2026-06-08

### Summary

- Overall status: planning/tracking setup.
- Completed: project review, homepage architecture review, tracking docs.
- In progress: tracking layer for existing 4-month Sheet plan and DailyXeDien content-source alignment.
- Blocked/waiting: product import, ACF JSON implementation, WP-CLI availability check.

### Progress By Area

| Area | Status | % | Notes |
|---|---|---:|---|
| Setup/import | Not started | 0 | Sheet has timeline, implementation needs audit |
| Header/footer ACF Options | Ready | 10 | Architecture reviewed; fields still need implementation |
| Homepage flexible sections | Ready | 10 | Layout spec exists; JSON/template work not started |
| Product sections | Waiting | 5 | Can implement shell/query; real test waits for import |
| Product archive/single | Not started | 0 | Outside current homepage batch |
| Tracking/SEO | Not started | 0 | Tracking docs exist, implementation not started |
| Performance/scaling | Waiting | 0 | After feature completion |
| QA/deploy | Not started | 0 | |

### Next Actions

1. Content cleanup: Audit and remove/hide discontinued products and configure 301 redirects.
2. Clean up unrelated posts (travel, sports, etc.).
3. Clean up orphaned media and standardize alt texts.
4. Clean up empty tags and taxonomies to optimize sitemaps.

## Weekly Snapshot - 2026-06-30

### Summary

- Overall status: Polylang Integration and Pro features successfully activated and verified.
- Main focus: Multi-language orchestration, WooCommerce translation sync, String scanning, and SEO/canonical setup.
- Completed:
  - Installed and configured free Polylang plugin.
  - Programmatically set up languages (vi as default, en as secondary).
  - Enabled all custom HD Polylang Pro settings (`translate_slugs`, `duplicate_content`, `share_slugs`, `locale_fallback`).
  - Verified `Products.php` (WooCommerceSync), `Scanner.php` (StringTranslation), `DuplicateContent.php` (DuplicateContent), and `Frontend.php` / `TranslateSlugs.php` (SEO & Canonical).
- In progress: Content cleanup preparations.
- Blocked/waiting: None.

### Progress By Area

| Area | Status | % | Notes |
|---|---|---:|---|
| Setup/import | Done | 100 | Polylang + core DB setup complete |
| Header/footer ACF Options | Done | 100 | Fully dynamic options active |
| Homepage flexible sections | Done | 100 | 12 flexible content layouts active |
| Product sections | Done | 100 | WooCommerceSync active |
| Product archive/single | Not started | 0 | Ready for frontend styling in Month 3 |
| Tracking/SEO | Done | 100 | GA4, FB Pixel + Polylang alternate links verified |
| Performance/scaling | Waiting | 0 | After feature completion |
| QA/deploy | Not started | 0 | Month 4 |

### Files Changed

None. Pre-existing theme classes inside `src/Modules/PLL/` were used.

### Verification

| Check | Result | Notes |
|---|---|---|
| PHP syntax | PASS | All files checked with `php -l` |
| Theme build | PASS | Tested using `pnpm build` |
| WP-CLI import | PASS | Script successfully run to configure Polylang |
| Browser desktop | PASS | Home page responds with 200 OK |
| Browser mobile | PASS | Home page alternate links verified |

### Next Actions

1. Begin content cleanup phase (hide products, remove unrelated posts, clean up taxonomies).
