<?php
/**
 * Script crawl và import sản phẩm không còn bán từ dailyxedien.vn vào dailynew.
 * 
 * Sử dụng: php scripts/crawl_discontinued_products.php [--limit=N] [--force]
 */

declare(strict_types=1);

// Bootstrap WordPress
require_once __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Tăng limit bộ nhớ và thời gian thực thi
@ini_set('memory_limit', '1024M');
@set_time_limit(0);

$json_file = __DIR__ . '/../docs/discontinued_products.json';
$progress_file = __DIR__ . '/../docs/crawl_progress.json';

if (!file_exists($json_file)) {
    die("❌ Không tìm thấy file danh sách URL: $json_file\n");
}

$urls = json_decode(file_get_contents($json_file), true);
if (!is_array($urls) || empty($urls)) {
    die("❌ Danh sách URL rỗng hoặc sai định dạng JSON.\n");
}

// Đọc progress cũ nếu có
$progress = [];
if (file_exists($progress_file)) {
    $progress = json_decode(file_get_contents($progress_file), true) ?: [];
}

// Xử lý CLI arguments
$options = getopt('', ['limit:', 'force', 'start:']);
$limit = isset($options['limit']) ? (int)$options['limit'] : count($urls);
$force = isset($options['force']);
$start_idx = isset($options['start']) ? (int)$options['start'] : 0;

echo "=========================================================\n";
echo "🚀 BẮT ĐẦU CÀO DỮ LIỆU SẢN PHẨM TỪ DAILYXEDIEN.VN\n";
echo "📦 Tổng số link: " . count($urls) . " sản phẩm\n";
echo "⚙️ Giới hạn xử lý đợt này: $limit sản phẩm (Bắt đầu từ STT: $start_idx)\n";
echo "=========================================================\n\n";

/**
 * Fetch HTML with cURL and Gzip support.
 */
function fetch_url(string $url, int $retry = 2): ?string {
    for ($i = 0; $i <= $retry; $i++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // Hỗ trợ gzip/deflate
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !empty($html)) {
            return $html;
        }

        if ($i < $retry) {
            sleep(1);
        }
    }
    return null;
}

/**
 * Tải ảnh và đính kèm vào WordPress Media Library
 */
function download_and_attach_image(string $image_url, int $post_id, string $desc = ''): ?int {
    if (empty($image_url) || !preg_match('/^https?:\/\//i', $image_url)) {
        return null;
    }

    // Làm sạch URL
    $image_url = strtok($image_url, '?');
    $filename = basename(parse_url($image_url, PHP_URL_PATH));
    if (empty($filename)) {
        return null;
    }

    // Kiểm tra xem ảnh này đã từng được tải về chưa (dựa theo post meta hoặc filename)
    global $wpdb;
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_source_url' AND meta_value = %s LIMIT 1",
        $image_url
    ));
    if ($existing) {
        return (int)$existing;
    }

    // Tải ảnh bằng media_sideload_image
    $id = media_sideload_image($image_url, $post_id, $desc, 'id');
    if (!is_wp_error($id) && $id > 0) {
        update_post_meta($id, '_source_url', $image_url);
        return (int)$id;
    }

    return null;
}

/**
 * Tạo danh mục đa cấp nếu chưa có và trả về array term_ids
 */
function resolve_categories(array $cat_names): array {
    $term_ids = [];
    $parent_id = 0;

    foreach ($cat_names as $cat_name) {
        $cat_name = trim($cat_name);
        if (empty($cat_name)) continue;

        $term = term_exists($cat_name, 'product_cat', $parent_id);
        if (!$term) {
            $created = wp_insert_term($cat_name, 'product_cat', [
                'parent' => $parent_id
            ]);
            if (!is_wp_error($created)) {
                $term_id = (int)$created['term_id'];
            } else {
                $existing_by_name = get_term_by('name', $cat_name, 'product_cat');
                $term_id = $existing_by_name ? (int)$existing_by_name->term_id : 0;
            }
        } else {
            $term_id = is_array($term) ? (int)$term['term_id'] : (int)$term;
        }

        if ($term_id > 0) {
            $term_ids[] = $term_id;
            $parent_id = $term_id;
        }
    }

    return $term_ids;
}

/**
 * Xử lý làm sạch nội dung description HTML
 */
function clean_content_html(string $html): string {
    // Xóa các thẻ script, style, form, iframe không cần thiết
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    $html = preg_replace('/<form\b[^>]*>(.*?)<\/form>/is', '', $html);
    
    // Thay thế domain dailyxedien.vn bằng relative link hoặc trang hiện tại
    $html = str_replace('https://dailyxedien.vn', '', $html);
    $html = str_replace('http://dailyxedien.vn', '', $html);
    
    return trim($html);
}

// Bắt đầu vòng lặp xử lý
$count_processed = 0;
$count_success = 0;
$count_skipped = 0;
$count_failed = 0;

for ($idx = $start_idx; $idx < count($urls); $idx++) {
    if ($count_processed >= $limit) {
        break;
    }

    $url = $urls[$idx];
    $stt = $idx + 1;
    $count_processed++;

    // Lấy slug từ URL
    $path = trim(parse_url($url, PHP_URL_PATH), '/');
    $parts = explode('/', $path);
    $slug = end($parts);

    echo "[$stt/" . count($urls) . "] 🔍 Đang xử lý: $slug\n";

    // Kiểm tra đã crawl thành công trước đó chưa
    if (!$force && isset($progress[$url]) && $progress[$url]['status'] === 'success') {
        echo "   ⏭️ Đã cào trước đó (ID: {$progress[$url]['product_id']}). Bỏ qua.\n";
        $count_skipped++;
        continue;
    }

    // Kiểm tra xem sản phẩm đã có trong WP chưa theo slug
    $existing_product = get_page_by_path($slug, OBJECT, 'product');
    if (!$force && $existing_product) {
        echo "   ⏭️ Sản phẩm đã tồn tại trong WP (ID: {$existing_product->ID}). Cập nhật trạng thái...\n";
        $progress[$url] = [
            'status' => 'success',
            'product_id' => $existing_product->ID,
            'slug' => $slug,
            'title' => $existing_product->post_title,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($progress_file, json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $count_skipped++;
        continue;
    }

    // Fetch HTML
    $html = fetch_url($url);
    if (!$html) {
        echo "   ❌ Không thể tải trang HTML (404 hoặc mạng lỗi)\n";
        $progress[$url] = [
            'status' => 'failed',
            'error' => 'Fetch failed / 404',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        file_put_contents($progress_file, json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $count_failed++;
        continue;
    }

    // 1. Parse Title
    $title = '';
    if (preg_match('/<h1[^>]*product-title[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        $title = trim(strip_tags($m[1]));
    } elseif (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $m)) {
        $title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } elseif (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $parts = explode('|', $m[1]);
        $title = trim(html_entity_decode($parts[0], ENT_QUOTES, 'UTF-8'));
    }
    if (empty($title)) {
        $title = ucwords(str_replace('-', ' ', $slug));
    }

    // 2. Parse Price
    $price = '';
    if (preg_match('/property="product:price:amount"\s+content="([^"]+)"/i', $html, $m)) {
        $price = trim($m[1]);
    }

    // 3. Parse SEO
    $seo_title = '';
    $seo_desc = '';
    if (preg_match('/<meta\s+property="og:title"\s+content="([^"]+)"/i', $html, $m)) {
        $seo_title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } elseif (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
        $seo_title = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }
    if (preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $m)) {
        $seo_desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    } elseif (preg_match('/<meta\s+property="og:description"\s+content="([^"]+)"/i', $html, $m)) {
        $seo_desc = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }

    // 4. Parse Breadcrumbs -> Categories
    $cat_names = [];
    if (preg_match('/<nav[^>]*breadcrumbs[^>]*>(.*?)<\/nav>/is', $html, $bm)) {
        preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $bm[1], $am);
        foreach ($am[1] as $cname) {
            $clean_c = trim(strip_tags($cname));
            if (!empty($clean_c) && !in_array(mb_strtolower($clean_c, 'UTF-8'), ['trang chủ', 'sản phẩm', 'home'])) {
                $cat_names[] = $clean_c;
            }
        }
    }

    // 5. Parse Description
    $content = '';
    if (preg_match('/<div[^>]+id="accordion-description-content"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $cm)) {
        $content = clean_content_html($cm[1]);
    } elseif (preg_match('/<div[^>]+class="[^"]*woocommerce-Tabs-panel--description[^"]*"[^>]*>(.*?)<\/div>/is', $html, $cm)) {
        $content = clean_content_html($cm[1]);
    } elseif (preg_match('/<div[^>]+class="[^"]*product-short-description[^"]*"[^>]*>(.*?)<\/div>/is', $html, $cm)) {
        $content = clean_content_html($cm[1]);
    }

    // 6. Parse Short description
    $excerpt = '';
    if (preg_match('/<div[^>]+class="[^"]*product-short-description[^"]*"[^>]*>(.*?)<\/div>/is', $html, $em)) {
        $excerpt = clean_content_html($em[1]);
    }

    // 7. Parse Images
    $featured_image_url = '';
    if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $im)) {
        $featured_image_url = trim($im[1]);
    }

    $gallery_image_urls = [];
    preg_match_all('/class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*>.*?<a[^>]+href="([^"]+)"/is', $html, $gm);
    if (!empty($gm[1])) {
        foreach ($gm[1] as $gurl) {
            $gurl = trim($gurl);
            if (!empty($gurl) && $gurl !== $featured_image_url && !in_array($gurl, $gallery_image_urls)) {
                $gallery_image_urls[] = $gurl;
            }
        }
    }

    // 8. Parse Specifications (TSKT)
    $specs = [];
    preg_match_all('/<tr[^>]*>\s*<t[hd][^>]*>(.*?)<\/t[hd]>\s*<t[hd][^>]*>(.*?)<\/t[hd]>\s*<\/tr>/is', $html, $trows);
    if (!empty($trows[1])) {
        for ($k = 0; $k < count($trows[1]); $k++) {
            $lbl = trim(strip_tags($trows[1][$k]));
            $val = trim(strip_tags($trows[2][$k]));
            if (!empty($lbl) && !empty($val) && mb_strlen($lbl) < 60 && mb_strlen($val) < 250) {
                $specs[] = [
                    'tskt_label' => $lbl,
                    'tskt_value' => $val
                ];
            }
        }
    }

    // ── TẠO SẢN PHẨM WOOCOMMERCE ──
    $product = new WC_Product_Simple();
    $product->set_name($title);
    $product->set_slug($slug);
    $product->set_status('publish'); // Giữ publish để không chết link / mất SEO traffic
    $product->set_description($content);
    $product->set_short_description($excerpt);
    
    // Đặt tình trạng hết hàng vì đây là sản phẩm không còn bán
    $product->set_stock_status('outofstock');
    $product->set_manage_stock('no');

    if (!empty($price) && is_numeric($price)) {
        $product->set_regular_price((string)$price);
        $product->set_price((string)$price);
    }

    // Gán categories
    if (!empty($cat_names)) {
        $cat_ids = resolve_categories($cat_names);
        if (!empty($cat_ids)) {
            $product->set_category_ids($cat_ids);
        }
    }

    $product_id = $product->save();
    if (!$product_id) {
        echo "   ❌ Không thể tạo WooCommerce product cho: $title\n";
        $count_failed++;
        continue;
    }

    // Tải và gán Featured Image
    if (!empty($featured_image_url)) {
        $feat_id = download_and_attach_image($featured_image_url, $product_id, $title);
        if ($feat_id) {
            set_post_thumbnail($product_id, $feat_id);
            echo "   🖼️ Đã gán Featured Image (ID: $feat_id)\n";
        }
    }

    // Tải và gán Gallery Images
    if (!empty($gallery_image_urls)) {
        $gal_ids = [];
        foreach (array_slice($gallery_image_urls, 0, 5) as $gurl) {
            $gid = download_and_attach_image($gurl, $product_id, $title);
            if ($gid) {
                $gal_ids[] = $gid;
            }
        }
        if (!empty($gal_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gal_ids));
            echo "   🖼️ Đã gán " . count($gal_ids) . " Gallery Images\n";
        }
    }

    // Lưu ACF TSKT
    if (!empty($specs)) {
        if (function_exists('update_field')) {
            update_field('tskt_rows', $specs, $product_id);
        } else {
            update_post_meta($product_id, 'tskt_rows', count($specs));
            foreach ($specs as $s_idx => $s_item) {
                update_post_meta($product_id, "tskt_rows_{$s_idx}_tskt_label", $s_item['tskt_label']);
                update_post_meta($product_id, "tskt_rows_{$s_idx}_tskt_value", $s_item['tskt_value']);
            }
        }
        echo "   📋 Đã lưu " . count($specs) . " dòng thông số kỹ thuật (TSKT)\n";
    }

    // Lưu Rank Math SEO
    if (!empty($seo_title)) {
        update_post_meta($product_id, 'rank_math_title', $seo_title);
    }
    if (!empty($seo_desc)) {
        update_post_meta($product_id, 'rank_math_description', $seo_desc);
    }
    update_post_meta($product_id, 'rank_math_focus_keyword', $title);

    // Lưu cờ đánh dấu sản phẩm chuyển giao / ngưng kinh doanh
    update_post_meta($product_id, '_discontinued_imported', '1');
    update_post_meta($product_id, '_source_dailyxedien_url', $url);

    echo "   ✅ ĐÃ TẠO THÀNH CÔNG: [ID: $product_id] $title ($slug)\n";

    $progress[$url] = [
        'status' => 'success',
        'product_id' => $product_id,
        'slug' => $slug,
        'title' => $title,
        'specs_count' => count($specs),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents($progress_file, json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $count_success++;

    // Thư giãn nhẹ tránh DDoS server
    usleep(200000); // 0.2s
}

echo "\n=========================================================\n";
echo "🏁 HOÀN THÀNH ĐỢT CÀO!\n";
echo "📊 Thống kê:\n";
echo "   - Thành công: $count_success\n";
echo "   - Đã có / Bỏ qua: $count_skipped\n";
echo "   - Lỗi: $count_failed\n";
echo "   - Tổng tiến độ: " . count($progress) . "/" . count($urls) . " link đã ghi nhận trong progress.\n";
echo "=========================================================\n";
