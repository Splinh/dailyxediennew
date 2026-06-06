# SPL Theme — Scaling Guide for Large WooCommerce Sites

> Hướng dẫn triển khai web WooCommerce lớn (4K → 20K sản phẩm)
> dựa trên kiến trúc SPL Theme + Tailwind + aaPanel VPS.

---

## Mục Lục

1. [Kiến Trúc Tổng Quan](#1-kiến-trúc-tổng-quan)
2. [VPS & Infrastructure](#2-vps--infrastructure)
3. [Database & Caching](#3-database--caching)
4. [Frontend Stack](#4-frontend-stack)
5. [SEO & Ads Ready](#5-seo--ads-ready)
6. [Checklist Triển Khai Project Mới](#6-checklist-triển-khai-project-mới)
7. [Scaling Roadmap](#7-scaling-roadmap)
8. [Troubleshooting](#8-troubleshooting)

---

## 1. Kiến Trúc Tổng Quan

### Theme Stack

```
spl-theme/
├── src/                        # PHP 8.3 OOP
│   ├── Contracts/              # Interfaces (Feature, Module, Bootable)
│   ├── Core/                   # Helper, DB, Bootstrap
│   ├── Features/               # Theme infrastructure (always loaded)
│   │   └── Optimizer/          # Performance modules
│   │       ├── PageCache.php   # OB capture + save HTML
│   │       ├── WcAssets.php    # Dequeue WC scripts on non-shop pages
│   │       ├── ScriptLoader.php # Async/defer/lazy load
│   │       ├── CssClass.php    # Body class optimization
│   │       └── ImageSize.php   # Thumbnail sizes
│   └── Modules/                # Plugin integrations (optional, safe when disabled)
├── parts/                      # Template parts (product-card, home sections)
├── inc/                        # Procedural includes (critical-css, setup, hooks)
├── config/                     # Vite + Tailwind config
├── assets/                     # Source CSS/JS/images
├── docs/                       # Tài liệu (bạn đang đọc file này)
└── wp-content/
    └── advanced-cache.php      # Drop-in page cache (pre-WP load)
```

### Request Flow (Cached vs Uncached)

```
                        ┌─ advanced-cache.php ─── HIT? ──→ readfile() + exit (90ms)
                        │
Browser → Nginx → PHP ──┤
                        │
                        └─ MISS → WordPress → Theme → OB capture → save HTML file
                                                                    ↓
                                                              Lần sau = HIT
```

---

## 2. VPS & Infrastructure

### Recommended Stack (aaPanel)

| Component | Version | Config |
|---|---|---|
| **OS** | Ubuntu 22.04+ | LTS |
| **Web Server** | OpenLiteSpeed hoặc Nginx | OLS nhanh hơn cho WP |
| **PHP** | 8.3+ | OPcache ON |
| **MySQL** | 8.0+ | InnoDB tuning |
| **Redis** | 7.x | Object cache |
| **CDN** | Cloudflare Free | DNS proxy mode |

### VPS Sizing

| Traffic | Products | RAM | CPU | Disk |
|---|---|---|---|---|
| **5-10K/ngày** | ≤4K | 4GB | 2 cores | 40GB |
| **10-20K/ngày** | 4-10K | 8GB | 4 cores | 50GB |
| **20-50K/ngày** | 10-20K | 16GB | 4-8 cores | 80GB |
| **50K+/ngày** | 20K+ | 32GB | 8 cores | 100GB+ |

### aaPanel Setup (1 lần duy nhất)

```bash
# 1. OPcache
sed -i 's/;opcache.enable=.*/opcache.enable=1/' /etc/php/8.3/fpm/php.ini
sed -i 's/opcache.memory_consumption=.*/opcache.memory_consumption=256/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.max_accelerated_files=.*/opcache.max_accelerated_files=10000/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.revalidate_freq=.*/opcache.revalidate_freq=120/' /etc/php/8.3/fpm/php.ini
systemctl restart php8.3-fpm

# 2. MySQL tuning (cho 8GB RAM VPS)
cat >> /etc/mysql/conf.d/tuning.cnf << 'EOF'
[mysqld]
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
max_connections = 100
tmp_table_size = 64M
max_heap_table_size = 64M
EOF
systemctl restart mysql

# 3. Redis (aaPanel App Store → Install Redis)
# Sau đó cài plugin: wp plugin install redis-cache --activate
# wp redis enable

# 4. Gzip (check đã bật chưa)
nginx -T | grep "gzip on"
```

### Cloudflare Setup

```
1. Trỏ DNS qua Cloudflare (Proxy ON - orange cloud)
2. SSL → Full (Strict)
3. Speed → Optimization:
   - Auto Minify: CSS, JS, HTML ✅
   - Brotli: ON ✅
4. Caching:
   - Browser Cache TTL: 1 month
   - Cache Level: Standard
5. Page Rules (3 free):
   - wp-admin/* → Cache Level: Bypass
   - wp-login.php → Cache Level: Bypass
   - *.css,*.js,*.png,*.jpg → Cache Everything, Edge TTL: 1 month
```

---

## 3. Database & Caching

### 4-Layer Cache Strategy

```
Layer 0: Nginx FastCGI Cache    → Server-level, không chạy PHP (~10ms TTFB)
         ↓ miss
Layer 1: advanced-cache.php     → PHP drop-in, serve file HTML (~90ms TTFB)
         ↓ miss
Layer 2: Redis Object Cache     → WP options, transients, WC sessions
         ↓ miss
Layer 3: MySQL + InnoDB Buffer  → Raw queries (buffer_pool giữ data trong RAM)
```

> **Layer 0 vs Layer 1**: FastCGI cache nhanh hơn vì Nginx serve trực tiếp
> từ RAM/disk mà **không cần khởi tạo PHP process**. advanced-cache.php là
> fallback khi FastCGI cache không được config hoặc bị bypass.

### Nginx FastCGI Cache Setup (aaPanel)

> **Tương đương LSCache** nhưng cho Nginx. Cache response ở server-level,
> bypass PHP hoàn toàn trên cache HIT. TTFB có thể xuống **<10ms**.

**Bước 1**: Thêm cache zone vào Nginx main config (`/etc/nginx/nginx.conf`):

```nginx
# Thêm NGOÀI block server {}, trong block http {}
fastcgi_cache_path /var/cache/nginx/fastcgi levels=1:2
    keys_zone=WPCACHE:256m
    max_size=1g
    inactive=12h
    use_temp_path=off;

fastcgi_cache_key "$scheme$request_method$host$request_uri";
```

**Bước 2**: Thêm cache rules vào site config (aaPanel → Website → domain → Config):

```nginx
# Bên trong server {} block, TRƯỚC location ~ \.php$

# --- FastCGI Cache Rules ---
set $skip_cache 0;

# Không cache POST, query string, logged-in, WooCommerce
if ($request_method = POST)          { set $skip_cache 1; }
if ($query_string != "")             { set $skip_cache 1; }
if ($http_cookie ~* "wordpress_logged_in|woocommerce_items_in_cart|wp_woocommerce_session") {
    set $skip_cache 1;
}

# Không cache WC dynamic pages
if ($request_uri ~* "/cart/|/checkout/|/my-account/|/wp-admin/|/wp-json/") {
    set $skip_cache 1;
}

# Áp dụng trong location ~ \.php$ block:
# fastcgi_cache WPCACHE;
# fastcgi_cache_valid 200 12h;
# fastcgi_cache_bypass $skip_cache;
# fastcgi_no_cache $skip_cache;
# fastcgi_cache_use_stale error timeout updating;
# add_header X-FastCGI-Cache $upstream_cache_status;
```

**Bước 3**: Sửa `location ~ \.php$` block trong site config:

```nginx
location ~ \.php$ {
    # ... existing fastcgi_pass, fastcgi_param, etc. ...

    # Thêm vào cuối:
    fastcgi_cache WPCACHE;
    fastcgi_cache_valid 200 12h;
    fastcgi_cache_bypass $skip_cache;
    fastcgi_no_cache $skip_cache;
    fastcgi_cache_use_stale error timeout updating;
    add_header X-FastCGI-Cache $upstream_cache_status;
}
```

**Bước 4**: Test + reload:

```bash
nginx -t && systemctl reload nginx

# Verify cache hoạt động
curl -sI https://example.com | grep X-FastCGI-Cache
# HIT = cache đang serve (không qua PHP)
# MISS = lần đầu (build cache)
# BYPASS = logged-in / WC page
```

**Purge cache**:

```bash
# Xóa toàn bộ FastCGI cache
rm -rf /var/cache/nginx/fastcgi/*

# Hoặc tự động purge khi product save (thêm vào theme):
# exec( 'rm -rf /var/cache/nginx/fastcgi/*' ); // Cần sudoers config
```

### So sánh các giải pháp Page Cache trên Nginx

| Giải pháp | TTFB | PHP process | Config | Purge tự động |
|---|---|---|---|---|
| **FastCGI Cache** | **~10ms** | ❌ Không cần | Nginx config | Cần script |
| **advanced-cache.php** | ~90ms | ✅ Minimal | Theme code | ✅ Hook WP |
| **WP Super Cache** | ~100ms | ✅ PHP | Plugin | ✅ Plugin |

> **Khuyến nghị**: Dùng **cả 2 layer** — FastCGI cache ở Layer 0 (nhanh nhất),
> advanced-cache.php ở Layer 1 (fallback + tự purge khi save post).
> FastCGI miss → advanced-cache HIT → vẫn nhanh 90ms thay vì uncached 2-3s.

### Redis Object Cache Setup

```bash
# aaPanel: App Store → Redis → Install
# WordPress:
wp plugin install redis-cache --activate
wp redis enable

# Verify
wp redis status
# Status: Connected ✅
```

File `wp-content/object-cache.php` sẽ tự tạo bởi plugin.

### Transient Fragment Cache (Theme-level)

```php
// Cache product grid HTML — skip WP_Query on HIT
$key    = 'spl_products_' . md5( $cat_id . '_' . $count );
$cached = get_transient( $key );

if ( false !== $cached ) {
    echo $cached;
    return;
}

ob_start();
// ... WP_Query + product card loop ...
$html = ob_get_clean();
set_transient( $key, $html, 2 * HOUR_IN_SECONDS );
echo $html;
```

### Auto-invalidation

```php
// inc/product-cache.php — clear transients on product save
add_action( 'woocommerce_update_product', 'spl_clear_product_transients' );
add_action( 'woocommerce_new_product', 'spl_clear_product_transients' );

function spl_clear_product_transients(): void {
    global $wpdb;
    $wpdb->query(
        "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_spl_%'
            OR option_name LIKE '_transient_timeout_spl_%'"
    );
    // Purge page cache too.
    if ( class_exists( 'SPL\\Features\\Optimizer\\PageCache' ) ) {
        \SPL\Features\Optimizer\PageCache::purgeAll();
    }
}
```

### Database Growth & wp_postmeta

| Products | Meta rows | DB size | Uncached TTFB |
|---|---|---|---|
| 1K | 50K | ~100MB | 1-2s |
| 4K | 200K | ~500MB | 2-3s |
| 10K | 500K | ~1.5GB | 3-5s |
| 20K | 1M+ | ~3-5GB | 5-8s |

> **Lưu ý**: Uncached TTFB chỉ xảy ra lần đầu sau purge cache. Với advanced-cache.php,
> 99% requests sẽ là HIT (90ms). Chỉ cần lo uncached khi update nội dung.

### MySQL Index Optimization (khi >10K products)

```sql
-- Thêm index cho product lookup nhanh hơn
ALTER TABLE wp_postmeta ADD INDEX idx_meta_value (meta_key, meta_value(50));

-- Check slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
-- Log tại /var/log/mysql/slow.log
```

---

## 4. Frontend Stack

### Tailwind CSS 4 Setup

```bash
# Init Vite + Tailwind
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
  --color-primary: #your-brand-color;
  --font-sans: 'Be Vietnam Pro', sans-serif;
}
```

### Asset Loading Strategy

```php
// Chỉ load khi cần
wp_enqueue_style( 'spl-main', get_template_directory_uri() . '/dist/main.css', [], $ver );

// JS: defer + module
wp_enqueue_script( 'spl-app', get_template_directory_uri() . '/dist/app.js', [], $ver, true );

// WC assets: chỉ trên shop pages (WcAssets.php)
// Google Fonts: tối đa 3 weights (400, 600, 700)
// Emoji: disabled
// wp_head junk: removed
```

### Image Optimization

```php
// WebP support — thêm vào functions.php
add_filter( 'upload_mimes', function ( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
} );

// Lazy load mặc định (WP 5.5+)
// loading="lazy" tự động cho img tags

// Product card: chỉ dùng thumbnail size
add_image_size( 'product-card', 400, 400, true );
```

### Critical Rendering Path

```
1. HTML (cached, gzip) ──→ 15KB transfer
2. Critical CSS (inline hoặc preload) ──→ Render ngay
3. Google Fonts (preconnect + swap) ──→ Không block render
4. JS (defer) ──→ Load sau DOM ready
5. Images (lazy load) ──→ Load khi scroll
```

---

## 5. SEO & Ads Ready

### SEO Checklist

```
□ Yoast SEO hoặc RankMath (sitemap, schema, meta)
□ Breadcrumb schema (Product → Category → Home)
□ Proper heading hierarchy (1x h1 per page)
□ Semantic HTML5 (article, section, nav, main)
□ Open Graph + Twitter Cards meta
□ robots.txt + sitemap.xml (auto by plugin)
□ Canonical URLs (tránh duplicate content)
□ Hreflang nếu đa ngôn ngữ
□ Page speed: Core Web Vitals pass
    ├── LCP < 2.5s (hero image preload)
    ├── CLS < 0.1 (reserved image dimensions)
    └── INP < 200ms (lightweight JS)
```

### Google Ads Landing Page Optimization

```
Tiêu chí Google Ads Quality Score:
├── 1. Page Speed (đã tối ưu ✅)
│   └── TTFB 90ms, gzip, CDN
├── 2. Mobile Responsive (cần ensure)
│   └── Tailwind responsive utilities
├── 3. Relevant Content
│   └── Landing page match ad keywords
├── 4. Easy Navigation
│   └── Clear CTA, breadcrumb
└── 5. SSL + Trust Signals
    └── HTTPS (Cloudflare), reviews, badges
```

### Ads Traffic Spike Handling

```
20K/ngày = ~14 visits/phút (average)
Ads spike  = 50-100 visits/phút (peak)

Với advanced-cache.php:
├── Cache HIT = 90ms → 1 PHP process xong ngay
├── 100 req/phút × 0.09s = 9 CPU-seconds/phút → VPS 4 cores OK
└── Cloudflare cache static assets → giảm 80% bandwidth

Không cần lo spike nếu:
✅ Page cache ON
✅ CDN ON
✅ Redis ON
```

### Structured Data (Schema)

```php
// Product schema (tự động bởi Yoast + WooCommerce)
// Cần ensure:
// - Price (giá hiện tại)
// - Availability (InStock/OutOfStock)
// - Review/Rating
// - Brand
// - SKU
// - Image (sản phẩm chính)

// FAQ schema cho bài viết SEO
// How-to schema cho hướng dẫn
// Breadcrumb schema (auto by Yoast)
```

---

## 6. Checklist Triển Khai Project Mới

### Phase 1: Setup (Ngày 1-2)

```
□ Copy SPL theme → rename
□ Cài đặt VPS aaPanel
   □ PHP 8.3 + OPcache
   □ MySQL 8.0 + InnoDB tuning
   □ Redis
   □ Nginx hoặc OpenLiteSpeed
□ Setup Cloudflare DNS
□ Import database / content cũ
□ Cài WP plugins:
   □ Yoast SEO / RankMath
   □ Redis Object Cache
   □ WooCommerce
   □ ACF Pro
   □ FluentSMTP (email)
```

### Phase 2: Theme Customization (Ngày 3-7)

```
□ Config Tailwind design tokens (colors, fonts, spacing)
□ Build header/footer/nav
□ Homepage sections:
   □ Hero banner
   □ Product grids (transient cached)
   □ Flash sale / Featured
   □ Blog posts
   □ Testimonials
   □ Partners/Brands
□ Product templates:
   □ Product card (single price, lazy images)
   □ Single product page
   □ Category archive
   □ Cart + Checkout
□ Blog templates:
   □ Blog listing
   □ Single post
   □ Category/tag archives
□ Static pages:
   □ About
   □ Contact (CF7)
   □ Policy pages
□ Mobile responsive check
```

### Phase 3: Performance (Ngày 8-9)

```
□ advanced-cache.php (copy từ project gốc)
□ WP_CACHE = true trong config
□ PageCache module (OB capture + purge)
□ WcAssets dequeue
□ Font: 3 weights max
□ Emoji: disabled
□ wp_head junk: removed
□ Transient cache: product grids
□ Product card: get_variation_prices(true)
□ WP_Query: no_found_rows, orderby=date
□ Redis: wp redis enable
□ Cloudflare: cache rules
□ Benchmark: TTFB < 200ms
```

### Phase 4: SEO & Launch (Ngày 10-14)

```
□ Yoast/RankMath config
□ Sitemap submitted to GSC
□ robots.txt
□ 301 redirects (URL cũ → mới)
□ Open Graph images
□ Schema markup verify (Rich Results Test)
□ Core Web Vitals check (PageSpeed Insights)
□ Google Analytics / GTM
□ Google Ads conversion tracking
□ Facebook Pixel
□ Test: mobile, tablet, desktop
□ Test: cart + checkout flow
□ Test: payment gateway
□ Go live! 🚀
```

---

## 7. Scaling Roadmap

### Mốc theo thời gian

```
Năm 1 (4K SP, 20K traffic/ngày):
├── VPS: 4 cores / 8GB RAM
├── Redis: 256MB
├── CDN: Cloudflare Free
├── Cache: advanced-cache.php
├── DB: ~500MB
└── Cost: ~$20-30/tháng VPS

Năm 2-3 (10K SP, 30K traffic/ngày):
├── VPS: 4 cores / 16GB RAM            ⬆️
├── Redis: 512MB                       ⬆️
├── MySQL buffer_pool: 4GB             ⬆️
├── Thêm index wp_postmeta             ➕
├── DB: ~1.5GB
└── Cost: ~$40-50/tháng VPS

Năm 5-10 (20K SP, 50K+ traffic/ngày):
├── VPS: 8 cores / 32GB RAM            ⬆️
├── Redis: 1GB                         ⬆️
├── ElasticSearch (search nhanh)        ➕
├── S3 image offload (giảm disk)       ➕
├── Cloudflare Pro ($20/tháng)         ⬆️
├── DB: ~3-5GB
└── Cost: ~$80-100/tháng total
```

### Khi nào upgrade?

| Triệu chứng | Giải pháp |
|---|---|
| RAM > 80% liên tục | Upgrade RAM |
| Uncached TTFB > 5s | Thêm Redis / MySQL tuning |
| Disk > 80% | S3 offload images |
| Search chậm > 3s | ElasticSearch |
| Traffic spike > 100 req/s | Cloudflare Pro + cache everything |
| Admin product list lag | Custom admin queries + pagination |

---

## 8. Troubleshooting

### Performance Debug

```bash
# Check cache hoạt động
curl -sI https://example.com | grep X-SPL-Cache

# Check Redis
wp redis status

# Check OPcache
php -i | grep "opcache.enable"

# Check MySQL slow queries
tail -f /var/log/mysql/slow.log

# Check PHP memory
php -i | grep memory_limit

# Check server load
htop
```

### Common Issues

| Vấn đề | Nguyên nhân | Fix |
|---|---|---|
| TTFB > 3s (cached) | advanced-cache.php không load | Check `WP_CACHE = true` |
| Sản phẩm mới không hiện | Cache chưa purge | Lưu SP bất kỳ → auto purge |
| Admin chậm | Không ảnh hưởng frontend | Bình thường, cache chỉ cho frontend |
| Out of memory | PHP memory_limit thấp | Tăng lên 512M trong php.ini |
| Redis disconnect | Redis service stop | `systemctl restart redis` |
| CSS/JS cũ | Browser cache | Hard refresh Ctrl+Shift+R |
| Cloudflare cache cũ | Edge cache chưa purge | Cloudflare → Purge Everything |

### Files Quan Trọng (Copy sang project mới)

```
Bắt buộc copy:
├── wp-content/advanced-cache.php       # Page cache drop-in
├── src/Features/Optimizer/PageCache.php # OB capture + purge
├── src/Features/Optimizer/WcAssets.php  # Dequeue WC assets
├── src/Features/Optimizer.php          # Emoji + wp_head cleanup
├── inc/product-cache.php               # Transient invalidation
└── config/application.php              # WP_CACHE = true

Tham khảo:
├── parts/product-card.php              # Single price pattern
├── parts/home/products.php             # Transient cached grid
├── inc/critical-css.php                # Font loading (3 weights)
└── docs/PERFORMANCE.md                 # Performance playbook
```
