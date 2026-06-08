# Brand Guide — dailyxedien.vn

> Extracted from live site. Use this to configure theme tokens.
> Business/content source of truth: `docs/DAILYXEDIEN-SOURCE-OF-TRUTH.md`.

---

## Logo

| Asset | URL |
|---|---|
| Main Logo (1400×273) | `https://dailyxedien.vn/wp-content/uploads/2025/01/Logo-tong-hop-CMYK-mauxanh-03-1400x273.png` |
| Favicon (100×100) | `https://dailyxedien.vn/wp-content/uploads/2024/05/logo-dailyxedien-512x512-1-1-100x100.png` |
| Apple Touch Icon (512×512) | `https://dailyxedien.vn/wp-content/uploads/2024/05/logo-dailyxedien-512x512-1-1.png` |

---

## Color Palette

### Primary
| Token | Hex | RGB | Usage |
|---|---|---|---|
| `primary` | `#1e73be` | `30, 115, 190` | Buttons, active nav, links, product titles, section headers |

### Secondary / Accent
| Token | Hex | RGB | Usage |
|---|---|---|---|
| `accent` | `#ffa500` | `255, 165, 0` | CTA buttons, hover states, cookie bar |
| `accent-dark` | `#dd9933` | `221, 153, 51` | Active link states, alert badges |
| `sale` | `#f41e1e` | `244, 30, 30` | Sale/discount prices |

### Neutrals
| Token | Hex | RGB | Usage |
|---|---|---|---|
| `navy` | `#002647` | `0, 38, 71` | Top header bar, footer dark section |
| `dark-gray` | `#4d4d4d` | `77, 77, 77` | Absolute footer background |
| `light-gray` | `#f1f1f1` | `241, 241, 241` | Footer widget area background |
| `white` | `#ffffff` | — | Body & section backgrounds |
| `black` | `#000000` | — | Primary body text |

### Text on Dark
| Token | Value | Usage |
|---|---|---|
| `text-light` | `#ffffff` | Headings on dark bg |
| `text-light-muted` | `rgba(255,255,255,0.8)` | Paragraphs on dark bg |
| `text-muted` | `rgba(74,74,74,0.85)` | Subtle text, placeholders |

### Gradient
```css
/* Header widget collapsed state */
background: linear-gradient(315deg, #ace0f9 0%, #fff1eb 100%);
```

---

## Typography

| Property | Value |
|---|---|
| Font Family | **Be Vietnam Pro**, sans-serif |
| Google Fonts URL | `https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap` |

> Both body text and headings use the same font family.

---

## Tailwind 4 / CSS Token Mapping

```css
/* resources/styles/tailwind/themes.css */
@theme {
  --color-primary: #1e73be;
  --color-accent: #ffa500;
  --color-accent-dark: #dd9933;
  --color-sale: #f41e1e;
  --color-navy: #002647;
  --color-dark-gray: #4d4d4d;
  --color-light-gray: #f1f1f1;
  --font-family-sans: "Be Vietnam Pro", sans-serif;
}
```

---

## Site Info (SEO / meta)

| Field | Value |
|---|---|
| Site Title | Đại lý Xe Điện - Xe Đạp Điện - Xe Máy Điện - Xe 3 Bánh |
| Meta Description | Chuyên bán xe đạp điện Bluera, xe đạp trợ lực, xe máy điện, xe điện 3 bánh, xe đạp, phụ tùng và chế xe 3 bánh theo nhu cầu của khách hàng. |
| Phone (primary) | 0933 505 222 |
| Phone (landline) | 028 2253 0524 |
| Email | Dailyxedien.vn@gmail.com |
| Company | Công ty TNHH Xe Điện BLUERA Việt Nhật |
| Tax code | 0312473259 |
| Address | 466 Nguyễn Duy Trinh, P. Bình Trưng, TP. Hồ Chí Minh |
| Working hours | Thứ 2 - Chủ nhật (8:00AM - 08:00PM) |
| Facebook | https://www.facebook.com/DaiLyXeDien/ |
| YouTube | https://www.youtube.com/@XeDien |
| TikTok | https://www.tiktok.com/@dailyxedienhcm |
| Languages | Tiếng Việt, English |

---

## Navigation Structure

### Main Menu
- Trang Chủ
- Giới Thiệu
- Hot Sale
- Sản Phẩm
  - Xe Đạp Trợ Lực (Bluesuda, ADO)
  - Xe Đạp Điện (Bluera, AI Ebike, ADO, Asama, FMT, Honda, Nijia, Osakar, Pega, Yadea, Yamaha, Concise, Nhập Khẩu)
  - Ô Tô Điện (Buggy, Chở Khách, Sân Golf)
  - Xe Điện Cũ
  - Xe Máy Điện (Pusan, Anbico, Lixi, Nijia, TH CEO, Yadea, Xe Giao Hàng)
  - Xe Trẻ Em
  - Xe Pin
  - Xe Ba Gác Điện
  - Xe Máy 50CC
  - Xe 3 Bánh
  - Xe Đạp
  - Phụ Tùng Xe Điện
- Tin Tức
  - Tin Dailyxedien (Khuyến Mãi, Sự Kiện, Kinh Nghiệm, Video, Tuyển Dụng)
  - Tin Cộng Đồng (Công Nghệ, Du Lịch, Thể Thao, Ảnh Đẹp, Thị Trường Xe Điện)
- Dịch Vụ (Sửa Chữa, Nâng Cấp, Bảo Dưỡng, Bảo Hành, Cứu Hộ)
- Liên Hệ

### Top Bar Links
- Sứ Mệnh
- Cơ Hội Hợp Tác
- Hệ Thống Cửa Hàng
