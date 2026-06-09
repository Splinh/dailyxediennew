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

## Thống kê

| Severity | Tổng | Đã fix | Chưa fix |
|----------|------|--------|----------|
| 🔴 Critical | 5 | 5 | 0 |
| 🟡 Medium | 3 | 3 | 0 |
| 🟢 Low | 1 | 1 | 0 |
| **Tổng** | **9** | **9** | **0** |
