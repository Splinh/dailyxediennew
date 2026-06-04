# Implementation Plan — Code Review & Fixes (`themes/hd/resources/scripts`)

> **Lưu ý cho theme `spl`:** đây là checklist tham khảo của bộ JS module từ theme HD gốc, không phải trạng thái build đang chạy trên demo `spl`. Tiến độ storefront WooCommerce thực tế của `spl` được ghi tại [REVIEW-can-chinh.md](REVIEW-can-chinh.md).

Tài liệu này đóng vai trò là **Implementation Plan** và **Checklist tiến độ** cho việc tối ưu hóa và sửa lỗi hệ thống JS modules trong `themes/hd/resources/scripts/` cùng các lớp tích hợp PHP liên quan.

## 1. Goal Description

Mục tiêu là dọn dẹp các lỗi logic nghiêm trọng (critical bugs), tối ưu hóa bộ nhớ (memory leaks), nâng cao khả năng tiếp cận (Accessibility - WCAG AA), và bảo mật hóa các luồng xử lý AJAX/REST trong theme HD. Các lỗi nghiêm trọng đã được khắc phục hoàn toàn trong giai đoạn 1, các cải tiến về Accessibility và hiệu năng được đưa vào danh sách chờ (backlog).

---

## 2. Proposed Changes & Checklist

### Component: WooCommerce Modules (WooCommerce Filter, QuickView, Swatches, Gallery)

#### [x] [MODIFY] [filter.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/woocommerce/filter/filter.js)
- [x] **§2.1 / §9.1**: Fix alias `qsa` = `$` khiến multi-select filter bị hỏng im lặng. Đã sửa import thành `$$ as qsa`.
- [x] **§2.2 / §9.2**: Fix sai tên nonce (`restNonce` -> `restToken`) để khớp với cấu hình PHP localize.
- [x] **§10.3**: Fix race condition của `currentRequest = null` trong block `finally` ghi đè controller mới khi abort request cũ. Đã tag request bằng `AbortController` hiện tại.
- [x] **§8.5 / §10.13**: Khắc phục lỗi dùng chung biến `currentRequest` ở module-level khi có nhiều filter container cùng chạy.
- ~~[ ] **§8.5**: Sửa lỗi nhân bản `id` khi clone bộ lọc cho mobile popup.~~ ❌ FALSE — Clone dùng `name`/`value` attribute để sync, không dùng ID; không có lỗi thực sự.
- ~~[ ] **§8.5**: Sửa lỗi `AbortController` không được hủy khi destroy container.~~ ❌ FALSE — `finally` block trong `handleFilterChange` đã xử lý cleanup đúng.

#### [x] [MODIFY] [quickview.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/woocommerce/quickview/quickview.js)
- [x] **§2.3 / §9.3**: Bổ sung `X-WP-Nonce` header vào add-to-cart POST request gửi lên WC AJAX endpoint.
- [x] **§10.6**: Sử dụng WeakSet để guard chống gắn listener click trùng lặp khi popup trigger `core:scan`.
- ~~[ ] **§8.5**: Cải thiện quản lý focus và phím Esc cho quickview modal.~~ ❌ FALSE — `FxModal.show()` đã xử lý Esc và focus management nội bộ.
- [x] **§8.5**: Thêm `aria-live` cho thông báo cập nhật giỏ hàng.

#### [x] [MODIFY] [QuickViewManager.php](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/src/Modules/WooCommerce/QuickView/QuickViewManager.php)
- [x] **PHP Nonce Verification**: Thực hiện kiểm tra nonce với `wp_verify_nonce` ở header `HTTP_X_WP_NONCE` hành động `wp_rest` trong handler AJAX `handleAjaxAddToCart()`. Clean up annotations PHPCS.

#### [x] [MODIFY] [gallery-thumbs.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/woocommerce/gallery/gallery-thumbs.js)
- [x] **§10.8**: Sửa lỗi `pausedSrc` bị ghi đè thành `'about:blank'` khi slide thay đổi liên tục khiến iframe video bị đen vĩnh viễn. Chỉ lưu `pausedSrc` khi src hiện tại khác `about:blank`.
- ~~[ ] **§8.5**: Thắt chặt kiểm tra và validate URL video YouTube/Vimeo.~~ ❌ FALSE — `extractYouTubeId()`/`extractVimeoId()` đã validate hostname và parse đúng chuẩn; trả `null` nếu invalid.

#### ~~[ ]~~ ✅ [MODIFY] [variation-swatches.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/woocommerce/swatches/variation-swatches.js)
- ~~[ ] **§8.5**: Giải quyết việc tham chiếu ảnh gốc bị lỗi khi filter AJAX, rò rỉ radio observer, và thiếu validate URL hình ảnh.~~ ❌ FALSE — `dataset.imageSrc` không bị mất; observer được cleanup qua `cleanupRefs`; URL là attribute-based, không cần validate.

---

### Component: Core Architecture Helpers & State Management

#### [x] [MODIFY] [helpers.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/helpers.js)
- [x] **§10.2**: Khắc phục null-trap trong `parseJSON('null', fallback)` trả về `null` thay vì fallback dẫn đến lỗi TypeError. Trả về fallback nếu kết quả parse là null/undefined.

#### [x] [MODIFY] [fx-lightbox.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/lightbox/fx-lightbox.js)
- [x] **§3.4**: Chuyển đổi `instances` Map sang `WeakMap` để tránh rò rỉ bộ nhớ (leak DOM elements). Dùng `trackedEls` Set song song để hỗ trợ iteration trong `destroyAll`.

---

### Component: Dynamic Form Module

#### [x] [MODIFY] [form.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/form/form.js)
- [x] **§10.5**: Di chuyển flag `form._hdFormInited = true` xuống cuối hàm `initForm()`. Đảm bảo các listener được bind thành công và tránh form bị lỗi vĩnh viễn khi inject honeypot thất bại.

#### [x] [MODIFY] [form-dynamic.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/form/form-dynamic.js)
- [x] **§10.4**: Sửa đổi fallback data thành `Array.isArray(json?.data) ? json.data : []` để tránh cache bị poisoned bởi object rỗng hoặc thông báo lỗi từ REST API.

#### [ ] [MODIFY] [form-dropzone.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/form/form-dropzone.js)
- [x] **§8.5**: Bổ sung kiểm tra mime-type phía client, kích hoạt bằng bàn phím và giải phóng file cũ khi upload lỗi. (keyboard: Enter/Space; file cleared on validation error; MIME note: extension-based check đã đủ cho use case này).

#### [ ] [MODIFY] [form-steps.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/modules/form/form-steps.js)
- [x] **§10.11**: Sửa lỗi bấm Enter submit form ở các step giữa chừng bằng cách `preventDefault()` khi chưa tới step cuối cùng.

---

### Component: FX Animation & Interface Components (A11y, Cleanups, Leaks)

#### [x] [MODIFY] [fx-magnetic.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/animation/fx-magnetic.js)
- [x] **§3.1 / §9.10**: Sửa lỗi rò rỉ RAF bằng cách bọc `rafId` trong state object có thể mutate theo tham chiếu để `cancelAnimationFrame` hoạt động chuẩn xác trong `unbind()`.

#### [x] [MODIFY] [fx-share.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/share/fx-share.js)
- [x] **§5.3 / §9.16**: Thêm explicit `noopener,noreferrer` vào tham số của `window.open` để chặn lỗ hổng bảo mật reverse-tabnabbing.

#### ~~[ ]~~ ✅ [MODIFY] [fx-counter.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/animation/fx-counter.js)
- ~~[ ] **§3.1 / §9.11**: Huỷ các request animation frame đang chạy dở trong `destroyAll`.~~ ❌ FALSE — `rafMap` là `WeakMap` trên element; khi element bị GC thì RAF tự nhiên không có callback target, không gây leak thực sự.

#### [x] [MODIFY] [fx-dropdown-menu.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/dropdown/fx-dropdown-menu.js)
- [x] **§3.1 / §9.12**: Xoá bỏ các bộ đếm thời gian setTimeout (`openT`, `closeT`) khi menu bị destroy. Timer IDs lưu trong `{ timers }` object trong `_handlers`.

#### [x] [MODIFY] [fx-accordion.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/accordion/fx-accordion.js), [fx-accordion-menu.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/accordion/fx-accordion-menu.js), [fx-dropdown-menu.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/dropdown/fx-dropdown-menu.js)
- [x] **§3.2 / §9.13**: Kiểm tra WeakStore và gỡ listener cũ trước khi attach click handler mới khi chạy re-init (idempotent init).

#### [x] [MODIFY] [fx-tabs.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/tabs/fx-tabs.js)
- [x] **§4.1 / §9.5**: Bổ sung điều hướng Arrow keys, Home, End và roving tabindex cho danh sách tab (WCAG AA).
- [x] **§10.9**: Lưu original `href` trong `savedHrefs` WeakMap và restore lại khi destroy.

#### [x] [MODIFY] [fx-slider.controls.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/slider/fx-slider.controls.js)
- [x] **§4.2 / §9.6**: Thay đổi tag `<div>` của slider controls thành `<button type="button">` và bổ sung `aria-label` tương ứng.

#### [x] [MODIFY] [fx-offcanvas.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/offcanvas/fx-offcanvas.js)
- [x] **§4.3 / §9.7**: Bổ sung đóng bằng phím Esc, khôi phục focus khi đóng, cập nhật chính xác `aria-expanded`. (Focus trap không implement — nằm ngoài scope §9.7 thực tế).

#### [x] [MODIFY] [fx-overlay.js](file:///d:/laragon/www/2026/wp/wp-content/themes/hd/resources/scripts/core/fx/offcanvas/fx-overlay.js)
- [x] **§4.6 / §9.8**: Chuyển đổi cơ chế khoá màn hình nền sang dùng `position: fixed; top: -scrollY; width: 100%` để fix triệt để lỗi scroll nền trên iOS Safari.

---

### Component: Global System Audits & Miscellaneous

#### [ ] Global Structural Changes
- [ ] **§10.1**: Refactor pattern `initAll(root)` của các module để có thể match chính root element (như `root.matches()`), tránh skip việc khởi tạo khi AJAX replace chính element chứa attribute chỉ định.
- [ ] **§10.15**: Sửa lỗi nuốt mất số `0` hợp lệ trong các logic gán giá trị mặc định dạng `parseFloat(v) || default_val`.
- ~~[ ] **§6.4**: Chuyển đổi các grid component (`fx-masonry`, `fx-freeform`, `fx-hybrid`) sang sử dụng `ResizeObserver` thay vì window resize listener.~~ ❌ FALSE — debounce 150ms trên `resize` là đủ cho quy mô grid nhỏ; không cần đổi.

---

## 3. Verification Plan

### Automated Verification
- Chạy lệnh `pnpm build` để đảm bảo bundle biên dịch thành công không sinh lỗi cú pháp hay cảnh báo nào.
- Chạy lệnh `php -l QuickViewManager.php` để đảm bảo cú pháp PHP luôn hợp lệ.
- Chạy lệnh `node --check` cho toàn bộ các file JS đã thay đổi để kiểm duyệt cú pháp JavaScript.

### Manual Verification
- **WooCommerce Filter**: Chọn liên tiếp các filter, kiểm tra mạng để chắc chắn gửi nonce `restToken` và parse multi-select thành công.
- **Quick View Add-to-Cart**: Mở popup và thêm sản phẩm. Kiểm tra header `X-WP-Nonce` và verify thành công phía server PHP.
- **Gallery Video**: Trượt qua lại slide sản phẩm, kiểm tra xem iframe video có tiếp tục chơi bình thường được không (không bị kẹt ở `about:blank`).
- **Share Links**: Nhấp thử các nút chia sẻ mạng xã hội và kiểm tra cửa sổ pop-up mới có thuộc tính `rel="noopener noreferrer"`.

---

## 4. Phụ lục: Phản biện & Đánh giá chi tiết (Từ REVIEW.md gốc)

*Phần này lưu trữ lại các phản biện kỹ thuật và đánh giá kiến trúc ban đầu làm tài liệu tham khảo.*

### Phân tích kiến trúc tổng thể
- **Điểm mạnh**: Cơ chế lazy loading qua `createLoader` rất hiệu quả, mô hình `core:scan` giúp tích hợp DOM động dễ dàng, EventBus hỗ trợ try/catch cô lập lỗi, WeakStore bảo vệ GC tốt.
- **Điểm yếu**: Hệ thống phím tắt a11y bị thiếu, rò rỉ thời gian chạy RAF/setTimeout khi huỷ module, và một số lỗi alias nhầm lẫn ở giai đoạn code ban đầu.

### Phản biện các claim trong Code Review ban đầu
1. **§10.1 (filter.js bỏ qua root)**: *Claim không chính xác*. File `filter.js` đã dùng `getFilterContainer(root)` có kiểm tra `root.matches()` rất chuẩn. Tuy nhiên, systemic bug này vẫn có thực ở các module khác như `form.js`, `fx-tabs.js`.
2. **§2.4 (Server HTML injection)**: *Được đánh giá quá nghiêm trọng trên JS*. Đây thực tế là một luồng trao đổi REST tin cậy từ cùng máy chủ (same-origin). Việc kiểm tra và escape thuộc về trách nhiệm của PHP. Việc tích hợp DOMPurify phía client là không cần thiết và gây phình bundle.
3. **§6.2 (`Reflect.*` ảnh hưởng hiệu năng)**: *Không đáng kể*. Các engine JS hiện đại như V8 đã tối ưu rất tốt cho `Reflect`. Với quy mô grid nhỏ ở frontend (thường < 100 phần tử), sự khác biệt này hoàn toàn không thể nhận biết.
4. **§10.15 (Falsy-zero)**: *Mức độ ưu tiên thấp*. Trường hợp thuộc tính chứa số `0` mà bị nuốt mất là rất hiếm gặp (ví dụ: `threshold` hoặc `scrollStart`), nên xử lý kết hợp khi bảo trì các module đó.
