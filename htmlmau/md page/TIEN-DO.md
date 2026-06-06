# dailyxedien.vn — Yêu cầu & Tiến độ

> Dự án: làm lại website **dailyxedien.vn** dựa trên file landing page tĩnh.
> Cập nhật: 04/06/2026

---

## 1. Yêu cầu (Requirements)

### Mục tiêu chung
- Review file HTML gốc (`daily.html`) và **đại tu toàn diện UX/UI cho chuyên nghiệp**.
- File này sẽ được dùng làm nền để **dựng lại web dailyxedien.vn**.

### Quyết định đã chốt với khách
| Hạng mục | Lựa chọn |
|---|---|
| Mức độ cải thiện | **Đại tu toàn diện** (fix bug + a11y/SEO + tính năng UX + cấu trúc) |
| Cấu trúc file | **Tách CSS/JS ra file riêng** |

### Bối cảnh kỹ thuật
- Môi trường: Laragon (Windows), thư mục `D:\laragon\www\dailynew`.
- Stack hiện tại: HTML tĩnh + **Tailwind CSS (CDN)** + FontAwesome + **Vanilla JS** (không framework).
- Ngôn ngữ nội dung: Tiếng Việt.

---

## 2. Cấu trúc file hiện tại

```
dailynew/
├── index.html          ← file chính (đã dọn sạch, link asset ngoài)
├── daily.html          ← BẢN GỐC giữ lại để backup/đối chiếu
├── TIEN-DO.md          ← file này
└── assets/
    ├── css/style.css   ← style + animation tuỳ biến
    └── js/main.js      ← toàn bộ logic JS (đã viết lại, fix bug)
```

---

## 3. Tiến độ (Progress)

### ✅ ĐÃ HOÀN THÀNH

**Tách CSS/JS**
- [x] Đưa toàn bộ `<style>` → `assets/css/style.css`
- [x] Đưa toàn bộ `<script>` logic → `assets/js/main.js`
- [x] `index.html` chỉ còn link ra ngoài (giữ lại `tailwind.config` inline)

**Sửa bug JavaScript**
- [x] `switchTab()` văng lỗi khi gọi từ menu (bỏ phụ thuộc `event` toàn cục)
- [x] Tỉnh "BÀ RỊA – VŨNG TÀU" không highlight (lệch dấu gạch ngang/dài)
- [x] Tỉnh active lúc tải trang không sáng màu
- [x] Testimonial trượt cụt chữ (offset cứng 115px → đo chiều cao thật)
- [x] Hero slider hardcode 3 slide → đếm động theo DOM
- [x] Xoá code chết (`startMockSpeedometer`, `simulateAITest`, `#mock-speed`, `#ai-logs`)

**Accessibility & SEO**
- [x] Bỏ `user-scalable=no` (cho phép zoom lại)
- [x] Thêm `meta description`, Open Graph, canonical, theme-color, favicon
- [x] `aria-label` cho nút icon, `role="dialog"` modal, `role="tab"` tab
- [x] Đóng modal bằng `Esc`, điều hướng lightbox bằng phím ←/→
- [x] Skip-link, focus-ring rõ ràng, tôn trọng `prefers-reduced-motion`

**Tính năng UX**
- [x] Nút "lên đầu trang" (hiện khi cuộn > 600px)
- [x] Khoá scroll nền khi mở drawer/giỏ hàng/modal
- [x] Đánh giá sao + "đã bán" trên card sản phẩm
- [x] Giỏ hàng: ẩn badge khi rỗng, empty-state, format tiền `vi-VN`
- [x] Nút "Chỉ đường" mở Google Maps thật theo địa chỉ
- [x] Form tư vấn validate số điện thoại, toast success/error
- [x] `loading="lazy"` cho toàn bộ ảnh (25 ảnh)

---

### ⏳ ĐANG CHỜ QUYẾT ĐỊNH (next steps)

- [ ] **Build Tailwind tĩnh** (bỏ CDN) để nhẹ & nhanh khi lên production — *chờ khách chốt*
- [ ] **Tách product/news thành dữ liệu JS render động** (giống phần cửa hàng) để dễ ghép hệ thống — *chờ khách chốt*

### 📝 VIỆC CẦN LÀM KHI TRIỂN KHAI THẬT (ghi chú)

- [ ] Ảnh hero slide 1 đang trỏ file local `z782...jpg` (có dấu cách trong tên) → đổi tên không dấu cách, đặt vào `assets/img/`
- [ ] Thay favicon emoji tạm bằng logo thật
- [ ] Thay ảnh Unsplash demo bằng ảnh sản phẩm thật
- [ ] Nối form tư vấn & giỏ hàng với backend thật (hiện chỉ mô phỏng bằng toast)
- [ ] Cập nhật dữ liệu cửa hàng thật trong `mockStores` (`assets/js/main.js`)

---

## 4. Cách xem thử
Mở qua Laragon: `http://dailynew.test/index.html`
hoặc mở trực tiếp file `index.html` bằng trình duyệt.
