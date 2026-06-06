# SPL Theme — Blueprint: Web WooCommerce Mới

> File này mô tả yêu cầu và khác biệt khi copy theme SPL sang project mới.
> **Đọc trước khi bắt đầu** để không nhầm cấu trúc hiện tại (taodo) với project mới.

---

## ⚠️ Lưu Ý Quan Trọng Khi Copy

### Cái GIỮ NGUYÊN (copy thẳng)

```
✅ Giữ nguyên:
├── src/Contracts/               # Interfaces (Feature, Module, Bootable)
├── src/Core/                    # Helper, DB, Bootstrap
├── src/Features/Optimizer/      # Performance modules
│   ├── PageCache.php            # Page cache (OB + purge)
│   ├── WcAssets.php             # Dequeue WC assets
│   ├── ScriptLoader.php         # Async/defer
│   ├── CssClass.php             # Body classes
│   └── ImageSize.php            # Thumbnail sizes
├── inc/product-cache.php        # Transient invalidation
├── wp-content/advanced-cache.php # Drop-in page cache
├── config/application.php       # WP_CACHE = true
└── docs/                        # Tài liệu (giữ để tham khảo)
```

### Cái PHẢI THAY ĐỔI (không copy y nguyên)

```
❌ Phải thay đổi:
├── .env                         # DB, URL, keys mới
├── parts/home/*.php             # Layout homepage mới (sections khác taodo)
├── parts/product-card.php       # Design card mới (Tailwind classes)
├── header.php / footer.php      # Brand mới, menu mới
├── inc/critical-css.php         # ❌ BỎ — chuyển sang Tailwind
├── inc/critical.css             # ❌ BỎ — Tailwind thay thế
├── inc/pages.css                # ❌ BỎ — Tailwind thay thế
├── acf-json/                    # ACF fields mới cho project mới
├── assets/images/               # Logo, banner mới
└── style.css                    # Theme name, description mới
```

---

## Yêu Cầu Project Mới

### Tổng Quan

| Spec | Chi tiết |
|---|---|
| **Loại site** | WooCommerce e-commerce |
| **Sản phẩm ban đầu** | ~4.000 SP + bài viết |
| **Sản phẩm dài hạn** | 15.000 - 20.000 SP (5-10 năm) |
| **Traffic mục tiêu** | 20.000 visitors/ngày |
| **Mục đích** | Bán hàng + SEO + Google Ads |
| **Ngôn ngữ** | Tiếng Việt (có thể mở rộng) |
| **Data nguồn** | Web cũ đã có sẵn content |

### Frontend Stack (KHÁC taodo)

| | taodo (hiện tại) | Project mới |
|---|---|---|
| **CSS** | Vanilla CSS (critical.css, pages.css) | **Tailwind CSS 4** |
| **Build tool** | Không có | **Vite** |
| **JS** | Vanilla JS (core-ui.js) | **Vanilla JS** (giữ nhẹ) |
| **Fonts** | Google Fonts (Be Vietnam Pro) | Google Fonts (tuỳ brand) |
| **Icons** | SVG inline | SVG inline hoặc icon set |

### Infrastructure (KHÁC taodo)

| | taodo (hiện tại) | Project mới |
|---|---|---|
| **VPS** | FastPanel | **aaPanel** |
| **Web server** | Nginx | **Nginx** |
| **PHP** | 8.4 | **8.3+** |
| **Cache** | advanced-cache.php | **Nginx FastCGI + advanced-cache.php** |
| **Object cache** | Không | **Redis** (bắt buộc) |
| **CDN** | Không | **Cloudflare Free** (bắt buộc) |
| **SSL** | Let's Encrypt | **Cloudflare Full (Strict)** |

---

## Cấu Trúc Thư Mục Project Mới

```
project-root/
├── .env                          # Environment config
├── .env.local                    # Local overrides (gitignored)
├── config/
│   ├── application.php           # WP config (WP_CACHE, DB, etc.)
│   └── environments/
│       ├── development.php
│       └── production.php
├── vendor/                       # Composer autoload
├── wp/                           # WordPress core
│   ├── wp-config.php             # Points to config/
│   └── wp-content/
│       ├── advanced-cache.php    # Drop-in page cache ✅
│       ├── object-cache.php      # Redis drop-in (auto by plugin)
│       ├── cache/spl-pages/      # Page cache files (gitignored)
│       ├── plugins/
│       │   ├── hda/              # HD Admin plugin
│       │   ├── hdat/             # HD Admin Tools plugin
│       │   ├── redis-cache/      # Redis Object Cache
│       │   └── ...
│       └── themes/
│           └── spl/              # Theme mới (copy + customise)
│               ├── docs/         # Tài liệu ✅ Giữ
│               ├── src/          # PHP OOP ✅ Giữ core
│               ├── parts/        # Templates 🔄 Redesign
│               ├── inc/          # Procedural 🔄 Sửa lại
│               ├── assets/       # Source files
│               │   ├── css/      # Tailwind source ➕ MỚI
│               │   ├── js/       # JS modules
│               │   └── images/   # Brand assets 🔄 Mới
│               ├── dist/         # Vite build output ➕ MỚI
│               ├── vite.config.js # ➕ MỚI
│               ├── tailwind.config.js # ➕ MỚI (TW4 dùng CSS config)
│               └── package.json  # ➕ MỚI
```

---

## Các Bước Chuyển Đổi Từ taodo → Project Mới

### Bước 1: Copy & Rename

```bash
cp -r thaphaco/ newproject/
cd newproject/

# Sửa .env
# - WP_HOME, DB_NAME, DB_USER, DB_PASSWORD
# - Tạo keys mới: https://roots.io/salts.html

# Sửa style.css
# - Theme Name, Description, Author
```

### Bước 2: Setup Tailwind (thay thế vanilla CSS)

```bash
pnpm init
pnpm add -D vite tailwindcss @tailwindcss/vite
```

```js
// vite.config.js
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    outDir: 'dist',
    rollupOptions: {
      input: {
        main: 'assets/css/main.css',
        app:  'assets/js/app.js',
      },
    },
  },
});
```

```css
/* assets/css/main.css */
@import 'tailwindcss';

@theme {
  --color-primary: oklch(0.55 0.2 260);    /* Thay brand color */
  --color-secondary: oklch(0.7 0.15 150);
  --font-sans: 'Be Vietnam Pro', sans-serif;
}
```

### Bước 3: Xoá CSS cũ của taodo

```bash
# Xoá các file CSS vanilla không cần
rm inc/critical.css
rm inc/pages.css

# Sửa inc/critical-css.php:
# - Bỏ enqueue critical.css, pages.css
# - Thay bằng enqueue dist/main.css từ Vite build
```

### Bước 4: Sửa critical-css.php cho Tailwind

```php
// inc/critical-css.php — SỬA LẠI
function spl_enqueue_core_css(): void {
    // Google Fonts (tối đa 3 weights)
    wp_enqueue_style(
        'spl-fonts',
        'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap',
        [],
        null
    );

    // Tailwind compiled CSS (từ Vite build)
    wp_enqueue_style(
        'spl-main',
        get_template_directory_uri() . '/dist/main.css',
        [ 'spl-fonts' ],
        spl_theme_asset_version( 'dist/main.css' )
    );
}
```

### Bước 5: Redesign Templates

```
Cần redesign (dùng Tailwind classes thay CSS cũ):
├── header.php          # Nav, logo, search, cart icon
├── footer.php          # Footer links, contact, copyright
├── parts/product-card.php  # Product grid card
├── parts/home/*.php    # Homepage sections
├── single.php          # Blog post
├── archive.php         # Blog/product listing
└── page.php            # Static pages
```

### Bước 6: Import Data

```bash
# Option 1: WP All Export/Import plugin
# Export từ web cũ → Import vào web mới

# Option 2: Database migration
# mysqldump từ web cũ → import → search-replace URL
wp search-replace 'https://old-domain.com' 'https://new-domain.com' --all-tables

# Option 3: WooCommerce CSV import
# Export products CSV → Import vào WC mới
```

### Bước 7: 301 Redirects (Giữ SEO)

```php
// Nếu URL structure thay đổi, thêm redirects:
// Dùng plugin Redirection hoặc trong functions.php

// Hoặc trong nginx config:
// rewrite ^/old-url$ /new-url permanent;
```

---

## Tính Năng Cần Có (Project Mới)

### Must Have (V1 - Launch)

```
□ Homepage
  □ Hero banner (slider hoặc static)
  □ Product categories grid
  □ Featured/bestseller products
  □ Flash sale section
  □ Blog posts latest
  □ Brand/partner logos
  □ Trust signals (reviews, certifications)

□ Product Pages
  □ Product card (image, name, price, badge)
  □ Single product (gallery, description, specs, reviews)
  □ Category archive (filter, sort, pagination)
  □ Search results

□ Blog
  □ Blog listing
  □ Single post (TOC, related posts)
  □ Category/tag archives

□ Static Pages
  □ About us
  □ Contact (form)
  □ Policy (privacy, returns, shipping)

□ WooCommerce
  □ Cart
  □ Checkout
  □ My Account
  □ Order tracking
  □ Payment gateway (COD, bank transfer, VNPay/Momo)

□ SEO
  □ Yoast/RankMath
  □ Sitemap
  □ Schema markup
  □ Open Graph

□ Performance
  □ advanced-cache.php
  □ Redis
  □ Cloudflare
  □ FastCGI cache (nếu VPS cho phép)
```

### Nice to Have (V2 - Sau Launch)

```
□ AJAX product filter (price, attributes, categories)
□ Quick view product modal
□ Wishlist
□ Compare products
□ Product reviews with images
□ Live chat widget (Zalo, Messenger)
□ Newsletter popup
□ Social share buttons
□ Mega menu (product categories)
□ Mobile app-like PWA
□ ElasticSearch (khi >10K products)
□ Multi-language (Polylang)
```

---

## Lưu Ý Đặc Biệt

### 1. Không nhầm CSS

```
taodo dùng:    Vanilla CSS → inc/critical.css + inc/pages.css
Project mới:   Tailwind CSS → assets/css/main.css → dist/main.css (Vite build)

→ KHÔNG copy critical.css, pages.css sang project mới
→ Viết lại toàn bộ UI bằng Tailwind utility classes
```

### 2. Không nhầm ACF fields

```
taodo có:     ACF fields cho Lạc Huy (about, products, flash sale)
Project mới:  ACF fields khác tuỳ nội dung

→ KHÔNG copy acf-json/ sang project mới
→ Tạo ACF fields mới phù hợp content mới
```

### 3. Giữ nguyên performance code

```
COPY NGUYÊN:
├── advanced-cache.php     # Đã test, hoạt động tốt
├── PageCache.php          # Capture + purge logic
├── WcAssets.php           # Dequeue patterns
├── Optimizer.php          # Emoji + wp_head cleanup
└── product-cache.php      # Transient invalidation

Chỉ cần sửa:
├── product-card.php       # Design mới nhưng GIỮ price logic
│   └── get_variation_prices(true) — KHÔNG đổi
├── products.php           # Layout mới nhưng GIỮ transient cache pattern
│   └── get_transient / set_transient — KHÔNG đổi
└── flash-sale.php         # Tương tự
```

### 4. Database migration

```
Web cũ có ~4K sản phẩm → cần:
1. Export products (CSV hoặc XML)
2. Export blog posts
3. Export categories/tags
4. Export images (wp-content/uploads/)
5. Import vào web mới
6. search-replace domain
7. 301 redirects cho URL cũ
8. Submit sitemap mới lên GSC
9. Monitor 404 errors (1-2 tuần)
```
