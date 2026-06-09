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

## Thống kê

| Severity | Tổng | Đã fix | Chưa fix |
|----------|------|--------|----------|
| 🔴 Critical | 7 | 6 | 1 (BUG-014) |
| 🟡 Medium | 7 | 7 | 0 |
| 🟢 Low | 2 | 2 | 0 |
| **Tổng** | **17** | **16** | **1** |
