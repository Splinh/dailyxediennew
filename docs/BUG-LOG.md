# 🐛 BUG LOG — DailyXeDien Rebuild

> Ghi lại các bug phát sinh, nguyên nhân gốc (root cause), và cách fix.
> Dùng để tránh lặp lại khi triển khai các trang/module khác.

---

## Ký hiệu

| Icon | Ý nghĩa |
|------|---------|
| 🔴 | Critical — chặn hoạt động |
| 🟡 | Medium — ảnh hưởng UX |
| 🟢 | Low — cosmetic |
| ✅ | Đã fix |
| ⬜ | Chưa fix |

---

## BUG-001: CSS không load — trang chủ vỡ giao diện 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `wp/wp-content/themes/spl/src/Core/Asset.php`

**Triệu chứng**: Trang chủ không có CSS, layout vỡ hoàn toàn.

**Root cause**: `index.css` khai báo dependency là `tailwind.css`, nhưng `tailwind.css` không tồn tại riêng trong Vite manifest (đã được merge vào `index.css` khi build). WordPress skip enqueue khi dependency không registered.

**Fix**: Kiểm tra sự tồn tại của `tailwind.css` trong manifest trước khi thêm vào dependency list.

```diff
# Asset.php
- $deps = ['tailwind-css'];
+ $deps = [];
+ if (isset($manifest['tailwind.css'])) {
+     $deps[] = 'tailwind-css';
+ }
```

---

## BUG-002: Ảnh vỡ 404 trên local 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `header.php`, `populate-media-and-fix.php`

**Triệu chứng**: Logo, banner slider, event gallery — tất cả ảnh 404.

**Root cause**: Seeding data dùng placeholder URL (`https://placehold.co/...`) thay vì sideload ảnh local vào Media Library. ACF fields reference attachment ID nhưng ID không tồn tại.

**Fix**: Tạo script `populate-media-and-fix.php` sideload ảnh từ `htmlmau/assets/images/` vào Media Library, cập nhật 43 sản phẩm WooCommerce + banner/gallery ACF fields với attachment ID thật.

---

## BUG-003: Hero Slider ảnh mờ + che logo 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `parts/home/hero-slider.php`, `dailyxedien.css`

**Triệu chứng**: Ảnh slider bị mờ so với site cũ, logo bị che khuất.

**Root cause**: Dùng `background-image` CSS + Ken Burns zoom effect → ảnh bị scale/blur. Overlay gradient quá đậm che logo.

**Fix**:
- Chuyển sang `<img>` tag với `object-fit: cover` → ảnh rõ nét pixel-perfect
- Bỏ Ken Burns zoom animation
- Dùng `cubic-bezier` crossfade cho transition mượt hơn
- Dot indicators chuyển sang `data-active` attribute

---

## BUG-004: Legacy CSS conflict Tailwind 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `inc/critical-css.php`, `inc/inline-js.php`

**Triệu chứng**: Một số element bị style sai do CSS cũ override Tailwind classes.

**Root cause**: `critical.css`, `pages.css` (legacy vanilla CSS) và `core-ui.js` từ theme cũ vẫn được enqueue, conflict với Tailwind v4 utility classes.

**Fix**: Disable enqueue các file legacy trong `critical-css.php` và `inline-js.php`.

---

## BUG-005: HDA Settings page trống — không hiện module toggles 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `hda/src/Plugin.php`, `hda/src/Modules/GlobalSetting/views/settings.php`

**Triệu chứng**: Vào `wp-admin/admin.php?page=hda-settings` → sidebar chỉ hiện "Global Setting" nhưng content area trống. Không thấy module toggles grid.

**Root cause**: `.tabs-panel` CSS default là `display: none`. Panel đầu tiên cần class `show` (được thêm bởi JS `initFilterTabs`). Nhưng JS không chạy (xem BUG-006).

**Fix**: Thêm class `show` trực tiếp vào first panel trong PHP template.

```diff
- <div id="global_setting_settings" class="group tabs-panel">
+ <div id="global_setting_settings" class="group tabs-panel show">
```

---

## BUG-006: HDA settings.js không hoạt động — tab switching + AJAX save hỏng 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `hda/src/Plugin.php`

**Triệu chứng**: Click tab sidebar không chuyển panel. Save Changes không lưu. Toàn bộ JS features của settings page hỏng.

**Root cause**: `settings.js` là **CJS/IIFE bundle** (141KB, dùng `var`, `jQuery(...)`) nhưng bị load với `type="module"` attribute trong `Plugin.php`:

```php
Asset::enqueueJS('settings.js', [...], null, true, ['module', 'defer']);
```

- `type="module"` → browser xử lý như ES module → strict mode, scope isolation
- jQuery global access fail trong module scope
- `defer` trên footer script gây race condition với dependency `wp-color-picker`
- → JS error → `initFilterTabs()`, `initSettingsForm()` không chạy

**Trên project cũ**: Có thể dùng config khác hoặc browser cache giữ phiên bản cũ.

**Fix**: Bỏ cả `module` và `defer` khỏi enqueue:

```diff
- Asset::enqueueJS('settings.js', ['wp-color-picker', 'jquery-ui-sortable'], null, true, ['module', 'defer']);
+ Asset::enqueueJS('settings.js', ['wp-color-picker', 'jquery-ui-sortable']);
```

---

## BUG-007: HDA settings save không nhận (AJAX fail, không có PHP fallback) 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `hda/src/Modules/GlobalSetting/GlobalSetting.php`

**Triệu chứng**: Bật module toggles → Save Changes → reload → settings reset về rỗng. Module-specific settings (Optimize, Editor...) cũng không lưu.

**Root cause**: Kết hợp nhiều yếu tố:
1. JS `initSettingsForm()` không chạy (do BUG-006) → form submit AJAX không hoạt động
2. Nếu JS recover → `e.preventDefault()` chặn native POST → PHP không nhận data
3. Không có PHP fallback handler cho form POST

**Fix**: Thêm `handlePostSave()` method vào `GlobalSetting.php`:
- Hook vào `admin_init`
- Check `$_POST['_submit_settings']` + nonce + capability
- Save cả module toggles (`hda_config`) VÀ module-specific settings (delegate qua `$registry->processSettingsSave($data)`)
- Redirect với `?settings-updated=true` + success notice

---

## BUG-008: HDA stale transient cache chặn manifest resolve 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `wp_options` table (`_transient_hda_*`)

**Triệu chứng**: HDA admin CSS/JS không load đúng.

**Root cause**: 2 transient records từ project cũ (`_transient_hda_vite_manifest_*`) chứa manifest data cũ với hash filenames khác. Vite `Trait` đọc transient thay vì file thật → resolve sai path.

**Fix**: Xóa transient records:
```sql
DELETE FROM w_options WHERE option_name LIKE '_transient_hda_%';
```

---

## BUG-009: Admin list tables — class `fixed` gây sticky column issues 🟢 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `hda/resources/scripts/admin-core.js`

**Triệu chứng**: Bảng list trong admin bị layout issues do sticky column headers.

**Root cause**: WordPress thêm class `fixed` vào `.wp-list-table` cho sticky headers, gây conflict trên một số cấu hình.

**Fix**: Thêm JS remove class trong `admin-core.js` source → build:
```js
$('.wp-list-table.fixed').removeClass('fixed');
```

---

## BUG-010: Tailwind `@source` không quét `parts/` — class trang chủ bị purge 🔴 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `wp/wp-content/themes/spl/resources/styles/tailwind/index.css`

**Triệu chứng**: Trang chủ vỡ giao diện dù template đúng; nhiều class (`shadow-hover-card`, `snap-mandatory`, `aspect-[1920/750]`, `lg:grid-cols-6`…) không có trong CSS build. (Khác BUG-001: ở đây CSS có load nhưng class bị purge.)

**Root cause**: Tailwind v4 dùng `source(none)` + danh sách `@source` thủ công, chỉ quét `{src,config,template-parts,templates,woocommerce}`. Toàn bộ part trang chủ ở `parts/**` không được quét → purge.

**Fix**: Thêm `inc,parts` vào glob PHP + `inc/**/*.js`, rồi `pnpm build`.
```diff
- @source "../../../{src,config,template-parts,templates,woocommerce}/**/*.php";
+ @source "../../../{src,config,inc,parts,template-parts,templates,woocommerce}/**/*.php";
```
**Phòng ngừa**: thêm thư mục template mới → nhớ thêm vào `@source`.

---

## BUG-011: ACF JSON còn brand "Lạc Huy / lachuy" 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `acf-json/group_lachuy_*.json` (4 file)

**Triệu chứng**: Tên file, group `key`/`title`, prefix field và data còn "Lạc Huy".

**Fix**: Đổi tên 4 file → `group_daily_*.json`; `group_lachuy_/field_lachuy_/layout_lachuy_` → `daily`; "Lạc Huy" → "DailyXeDien". **Giữ nguyên field `name`** (`home_sections`…) để template không vỡ.

---

## BUG-012: `spl_icon` thiếu icon → render SVG rỗng 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `header.php` (hàm `spl_icon`)

**Triệu chứng**: Nút slider, danh mục, tab hiện icon rỗng.

**Root cause**: Map icon tĩnh thiếu `chevron-left`, `bicycle`, `motorcycle`, `truck`.

**Fix**: Bổ sung 4 path Lucide. **Thêm icon mới phải khai báo trong map này.**

---

## BUG-013: Class Tailwind không tồn tại trong config 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `resources/styles/tailwind/themes.css`, `parts/home/consult-form.php`

**Triệu chứng**: Badge tech & tiêu đề consult form sai style.

**Root cause**: `bg-primary-950/50` (scale chỉ tới 900) và `md:text-3.5xl` (size không chuẩn) không sinh được.

**Fix**: Thêm token `--color-primary-950: #001a33`; đổi `md:text-3.5xl` → `md:text-4xl`.
**Kiểm chứng**: 526 class trong DOM → **0 class Tailwind thiếu** (22 còn lại là class WP/WooCommerce/JS-marker).

---

## BUG-014: `SwatchesAdmin` fatal khi WooCommerce chưa active 🔴 ⬜

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `src/Modules/WooCommerce/Swatches/Admin/SwatchesAdmin.php:39`

**Triệu chứng**: `PHP Fatal: Call to undefined function wc_get_attribute_taxonomy_names()` (hook `admin_init`); WP-CLI prompt "--skip-themes=spl".

**Root cause**: Gọi hàm WC trong `hookAttributeTaxonomies()` mà không kiểm tra WC đã load.

**Hiện trạng**: Không trigger vì WooCommerce đang active (né tạm). **Chưa sửa code.**

**Khuyến nghị**: Bọc đầu hàm:
```php
if ( ! function_exists( 'wc_get_attribute_taxonomy_names' ) ) { return; }
```

---

## BUG-015: `sed -i` (Windows) làm rỗng file 🟡 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `.env`, `parts/home/consult-form.php`

**Triệu chứng**: Sau khi chạy `sed -i`, file bị **rỗng 0 byte** (cả hai gitignore/chưa commit → không khôi phục từ git).

**Root cause**: `sed -i` không ổn định trên môi trường Windows/Git-bash của máy dev.

**Fix**: Dựng lại `.env` (DB/URL/9 salts mới, `FORCE_SSL_ADMIN=false`) và `consult-form.php` (theo `htmlmau/index.html` + contract JS).
**Phòng ngừa**: **KHÔNG dùng `sed -i`** trên dự án này — dùng Edit/Write hoặc PHP.

---

## BUG-016: WP-CLI + WooCommerce — cảnh báo "Undefined array key routes" 🟢 ✅

**Ngày phát hiện**: 2026-06-08
**File liên quan**: `woocommerce/includes/cli/class-wc-cli-runner.php`

**Triệu chứng**: Mọi lệnh `wp` in cảnh báo, làm bẩn output `--porcelain` (từng set nhầm ID khi capture biến shell).

**Root cause**: Tương thích WC + WP-CLI khi REST routes chưa sẵn. Vô hại với frontend.

**Workaround**: `--skip-plugins=woocommerce` cho lệnh không cần WC; `grep -v` lọc cảnh báo.

---

## BUG-017: "Không thấy settings SPL Toolkit" (lần kiểm tra 2026-06-09) 🟡 ✅

**Ngày phát hiện**: 2026-06-09
**File liên quan**: `hda/src/Plugin.php`, `hda/src/Modules/GlobalSetting/GlobalSetting.php`

**Triệu chứng**: User báo vào admin không thấy settings của SPL Toolkit.

**Kết luận**: Không phải bug mới (liên quan BUG-005/006/007 đã fix). Đã verify: plugin active, cap `hda_manage_options` đã cấp cho `administrator`+`quantri`, menu **`SPL`** (icon ⚙️) đăng ký đúng, `settings.js` build+enqueue đúng screen, truy cập thật `admin.php?page=hda-settings` trả về đủ UI (257KB).

**Nguyên nhân cảm nhận**: menu tên "SPL" (không phải "SPL Toolkit"); cap được sync ở lần `admin_init` đầu → cần **đăng xuất/đăng nhập lại** hoặc hard-refresh. URL trực tiếp: `/wp/wp-admin/admin.php?page=hda-settings`.

---

## BUG-018: Vite dynamic imports trả về 404 — không load được CSS/JS của module 🔴 ✅

**Ngày phát hiện**: 2026-06-26
**File liên quan**: `wp/wp-content/themes/spl/vite.config.ts`

**Triệu chứng**: Các module lazy-loaded (tabs, slider, lightbox) bị lỗi khởi tạo hoàn toàn. Bảng điều khiển debug báo lỗi `Unable to preload CSS for /css/fx-tabs.css`.

**Root cause**: Vite biên dịch các link preloading sử dụng base path mặc định là `/`, dẫn đến việc tải tài nguyên từ root của domain (ví dụ `https://dailynew.test/css/fx-tabs.css`) thay vì thư mục theme (`/wp-content/themes/spl/assets/`).

**Fix**: Thêm thuộc tính `base: '/wp-content/themes/spl/assets/'` vào cấu hình `vite.config.ts` để các tệp chunk được định tuyến chính xác.

---

## BUG-019: Vòng lặp tính toán chiều cao vô hạn (6-million-pixel whitespace gap) 🔴 ✅

**Ngày phát hiện**: 2026-06-26
**File liên quan**: `wp/wp-content/themes/spl/resources/styles/partials/_base.scss`

**Triệu chứng**: Khoảng trắng khổng lồ hàng triệu pixel xuất hiện ở dưới chân trang, đẩy các phần tử khác xuống dưới.

**Root cause**: Các tab panel ẩn (`not(.is-active)`) được cấu hình `position: absolute; visibility: hidden;` nhưng không có giới hạn chiều cao. Khi kết hợp với Swiper slides có `height: auto` và card links có `height: 100%` trong môi trường flexbox, trình duyệt rơi vào vòng lặp layout đệ quy vô hạn, phình to chiều cao panel ẩn lên mức kịch khung của trình duyệt (`6,291,497px`).

**Fix**: Thêm `height: 0;` cho khối panel ẩn `&:not(.is-active)` trong `_base.scss` để ngắt vòng lặp.

---

## BUG-020: Page Cache tĩnh không tự động xóa khi bấm Clear Cache 🔴 ✅

**Ngày phát hiện**: 2026-06-26
**File liên quan**: `wp/wp-content/themes/spl/src/Features/Optimizer/PageCache.php`

**Triệu chứng**: Khách vãng lai (không đăng nhập) vẫn nhìn thấy giao diện/dữ liệu cũ kể cả sau khi quản trị viên đã bấm Clear Cache trong admin.

**Root cause**:
1. Hàm dọn dẹp file tĩnh `PageCache::purgeAll()` chưa được móc vào hành động xóa cache tập trung `hd_clear_all_cache` của theme.
2. Phương thức `PageCache::register()` trả về sớm khi `is_admin()` là true, ngăn chặn việc đăng ký các hàm dọn dẹp (`save_post`, `woocommerce_update_product`, v.v.) trong môi trường quản trị.

**Fix**: Tách biệt đăng ký các hàm dọn dẹp ra khỏi khối điều kiện `is_admin()` trong `PageCache.php` và liên kết `PageCache::purgeAll` vào action `hd_clear_all_cache`.

---

## BUG-021: Đổi slug trang hợp tác sang `hop-tac` — theme vẫn hardcode `/co-hoi-hop-tac/` → 404 🔴 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/populate-cooperation-bluera.php:37`, `header.php:82,344`, `parts/global/mobile-drawer.php:92`, `inc/setup.php:52`

**Triệu chứng**: Install mới chạy populate → trang "Cơ Hội Hợp Tác" được tạo tại `/hop-tac/`, nhưng mọi link "Hợp Tác" (topbar fallback, main menu header, mobile drawer) đều trỏ `/co-hoi-hop-tac/` → khách bấm vào 404. `inc/setup.php` fallback nav lookup `get_page_by_path('co-hoi-hop-tac')` không thấy → mất luôn item "Hợp Tác" trong menu.

**Root cause**: Script đổi slug tạo trang thành `hop-tac` nhưng 4 vị trí trong theme vẫn hardcode URL `/co-hoi-hop-tac/`.

**Khuyến nghị**: Thống nhất một slug, hoặc thay hardcode bằng lookup page theo path/template rồi dùng `get_permalink()`.

---

## BUG-022: Thêm section ACF bất kỳ ở trang liên hệ → mất hẳn block "Hệ thống cơ sở" 🔴 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/templates/template-page-contact.php:49`, `acf-json/group_daily_contact.json`, `parts/contact/locations.php`

**Triệu chứng**: Block 3 cơ sở (showroom + 2 nhà máy Đồng Nai, 228 dòng + bản đồ) chỉ được render bởi nhánh fallback khi ACF rỗng. Admin thêm 1 section bất kỳ (`contact_info`/`contact_form`/`contact_faq`) → `Helper::getField` trả non-empty → nhánh else không chạy → block biến mất khỏi trang, không có cách khôi phục qua editor.

**Root cause**: Group ACF `group_daily_contact.json` không đăng ký layout `contact_locations`; switch trong template cũng không có case tương ứng.

**Khuyến nghị**: Đăng ký layout `contact_locations` trong ACF group + thêm case trong switch của template.

---

## BUG-023: Card Zalo mặc định trỏ về `https://zalo.me/` trần (không số) 🟡 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/parts/contact/info.php:57`

**Triệu chứng**: Trang liên hệ khi ACF chưa cấu hình → click card "Chat Zalo" mở trang chủ `zalo.me` thay vì cửa sổ chat của shop.

**Root cause**: Fallback label `'Nhắn tin ngay'` là truthy nên `?: '0933505222'` không chạy; `preg_replace('/[^0-9+]/', '')` xoá sạch mọi ký tự của label → chuỗi rỗng → link `https://zalo.me/`.

**Khuyến nghị**: Đổi fallback thành số điện thoại (`0933505222`), hoặc chỉ build link khi giá trị là số.

---

## BUG-024: Populate in "✓ Flushed all caches" dù purge có thể no-op 🟡 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/populate-cooperation-bluera.php:239-243`

**Triệu chứng**: Chạy script qua WP-CLI trên môi trường thiếu class PageCache / không có action LiteSpeed / không có Redis → vẫn in dòng "✓ Flushed all caches (LiteSpeed, PageCache, Redis)". Operator tin cache đã sạch; production LiteSpeed tiếp tục phục vụ trang cũ tới 12h.

**Root cause**: Dòng success in vô điều kiện, tách khỏi kết quả thật của từng bước purge (`class_exists` gate; `header('X-LiteSpeed-Purge: *')` là no-op trong CLI).

**Khuyến nghị**: In success theo kết quả thật từng bước (PageCache loaded? LiteSpeed purged? object cache active?).

---

## BUG-025: Stage-3 lookup bypass Polylang → có thể ghi đè page sai ngôn ngữ 🟡 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/populate-cooperation-bluera.php:23-46`

**Triệu chứng**: Site đa ngôn ngữ có page hợp tác vi + en (không page nào ở slug mục tiêu): `get_posts()` lấy page mới nhất theo ngày (en) → `update_field` ghi đè data Bluera tiếng Việt lên page tiếng Anh, phá nội dung page đó; page tiếng Việt bị bỏ sót.

**Root cause**: `get_posts()` mặc định `suppress_filters=true` bypass language scoping của Polylang; `numberposts=1` + `orderby date desc` chọn page theo ngày thay vì đúng ngôn ngữ/slug.

**Khuyến nghị**: Truyền `'suppress_filters' => false` và lọc theo ngôn ngữ, hoặc lookup chính xác theo slug + lang.

---

## BUG-026: Bỏ fallback `the_content()` — nội dung editor không bao giờ hiện 🟡 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/templates/template-page-contact.php:45`, `templates/template-page-cooperation.php:49`

**Triệu chứng**: Admin viết nội dung vào editor của trang liên hệ/hợp tác (vd thông báo "Showroom tạm đóng để nâng cấp"), ACF chưa có section → nội dung đó không được xuất ra bất kỳ đâu trên trang.

**Root cause**: Commit `2403c4ed` bỏ vòng fallback `the_content()` trong nhánh ACF-rỗng, thay bằng các part cứng — `$post->post_content` không còn được render.

**Khuyến nghị**: Giữ `the_content()` trong nhánh fallback (trước hoặc sau các part).

---

## BUG-027: `wp_insert_post()` không check return → success giả khi tạo trang fail 🟡 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/populate-cooperation-bluera.php:35-42`

**Triệu chứng**: `wp_insert_post()` fail (trả 0 — slug conflict, hoặc filter `save_post` throw) → script vẫn in "✓ Created page ... (ID: 0)", mọi `update_field(..., 0)` no-op, script kết thúc "COMPLETED" → operator tưởng trang đã tạo, thực tế `/hop-tac/` 404.

**Root cause**: Return value của `wp_insert_post()` không được kiểm tra.

**Khuyến nghị**: Ngay sau khi tạo: `if (empty($page_id)) { WP_CLI::error(...); }` (hoặc die kèm lý do).

---

## BUG-028: Case `contact_hero` là dead code — không có layout ACF tương ứng 🟢 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/templates/template-page-contact.php:31-33`, `acf-json/group_daily_contact.json`

**Triệu chứng**: Không tồn tại layout `contact_hero` nào trong group ACF → case không bao giờ chạy. Developer nhìn vào sẽ tưởng hero là ACF-managed rồi chỉnh `parts/contact/hero.php` hoặc populate hero layout (sẽ không render) — thực tế hero chỉ render qua fallback không-args.

**Root cause**: Case giữ trong switch dù layout không được đăng ký — che việc thiếu đăng ký thay vì làm nó lộ ra.

**Khuyến nghị**: Hoặc xoá case, hoặc đăng ký layout `contact_hero` trong ACF — chọn một.

---

## BUG-029: `purgeAll()` chạy 2 lần trên create path của populate 🟢 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/populate-cooperation-bluera.php:35,240`, `src/Features/Optimizer/PageCache.php`

**Triệu chứng**: Tạo trang mới: `wp_insert_post` fires `save_post` → `PageCache::purgeAll` lần 1 (flush toàn bộ object cache + xoá recursive toàn bộ page cache), rồi script gọi `purgeAll()` lần 2 lặp lại — toàn site cold cache 2 lần chỉ vì 1 trang, site lớn sẽ spike origin load.

**Root cause**: `PageCache::register()` hook `save_post → purgeAll` chạy trước CLI early-return; script gọi tay purge thêm lần nữa.

**Khuyến nghị**: Bỏ lệnh purge tay khi trang vừa được tạo, hoặc `remove_action('save_post', ...)` trước `wp_insert_post`.

---

## BUG-030: Fallback part list trùng lặp switch — dễ drift giữa 2 nhánh 🟢 ⬜

**Ngày phát hiện**: 2026-09-07 (code review commit `2403c4ed`)
**File liên quan**: `wp/wp-content/themes/spl/templates/template-page-cooperation.php:44-55`, `templates/template-page-contact.php:30-52`

**Triệu chứng**: Nhánh ACF-rỗng liệt kê lại đúng các parts mà switch phía trên đã map (lặp ở cả 2 template) → danh sách part tồn tại 2 nơi mỗi template. Thêm layout mới mà quên 1 trong 2 → install có ACF và install ACF-trống render khác nhau, không ai phát hiện cho tới khi so sánh 2 bản render.

**Root cause**: Danh sách part duplicate giữa switch và else, lệch convention `the_content()`-based của `template-page-home.php`.

**Khuyến nghị**: Dùng chung một map slug→part, loop map đó ở cả hai nhánh.

---

## Thống kê

| Severity | Tổng | Đã fix | Chưa fix |
|----------|------|--------|----------|
| 🔴 Critical | 12 | 9 | 3 (BUG-014, BUG-021, BUG-022) |
| 🟡 Medium | 12 | 7 | 5 (BUG-023 → BUG-027) |
| 🟢 Low | 5 | 2 | 3 (BUG-028 → BUG-030) |
| **Tổng** | **29** | **18** | **11** |
