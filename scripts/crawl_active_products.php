<?php
/**
 * Script cào và nhập các sản phẩm "CÒN BÁN" từ dailyxedien.vn vào dailynew.
 * 
 * - Tình trạng kho: instock (Còn hàng)
 * - Giá bán: Lấy nguyên giá niêm yết và giá khuyến mãi
 * - Hình ảnh: Tải về và đính kèm vào Media Library
 * - Danh mục: Tự động tạo và gán danh mục chuẩn
 * - ACF Thông số kỹ thuật: Gán vào repeater tskt_rows
 * - Rank Math SEO: Đồng bộ Title, Meta Description, Focus Keyword
 * - Thứ tự: Sắp xếp tiếp theo sau các sản phẩm còn hàng
 */

declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$urls = [
    'https://dailyxedien.vn/san-pham/xe-lan-dien-performance-2019/',
    'https://dailyxedien.vn/san-pham/xe-3-banh-supper-one/',
    'https://dailyxedien.vn/san-pham/xe-dap-the-thao-catani-360-26ca-360/',
    'https://dailyxedien.vn/san-pham/xe-dien-gap-concise-3-banh-2pin/',
    'https://dailyxedien.vn/san-pham/xe-dien-scooter-concise-2-pin/',
    'https://dailyxedien.vn/san-pham/xe-may-dien-3-banh-one/',
];

echo "=========================================================\n";
echo "📦 BẮT ĐẦU CÀO " . count($urls) . " SẢN PHẨM CÒN BÁN TỪ DAILYXEDIEN.VN\n";
echo "=========================================================\n\n";

global $wpdb;

// Lấy menu_order lớn nhất hiện tại của sản phẩm còn hàng
$max_order = (int)$wpdb->get_var("
    SELECT MAX(p.menu_order)
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
      AND (pm.meta_value = 'instock' OR pm.meta_value IS NULL)
      AND p.menu_order < 9999
");

$next_order = max(56, $max_order) + 1;

function fetch_page(string $url): ?string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_ENCODING       => '',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && is_string($html) && strlen($html) > 500) {
        return $html;
    }
    return null;
}

function sideload_media(string $img_url, int $post_id, string $desc = ''): ?int {
    if (empty($img_url)) return null;

    global $wpdb;
    $filename = basename(parse_url($img_url, PHP_URL_PATH));
    $clean_filename = preg_replace('/-\d+x\d+(\.[a-zA-Z]{3,4})$/', '$1', $filename);

    $existing_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
        '%' . $wpdb->esc_like($clean_filename)
    ));

    if ($existing_id > 0) {
        return $existing_id;
    }

    $tmp = download_url($img_url, 30);
    if (is_wp_error($tmp)) {
        return null;
    }

    $file_array = [
        'name'     => $filename,
        'tmp_name' => $tmp,
    ];

    $id = media_handle_sideload($file_array, $post_id, $desc);
    if (is_wp_error($id)) {
        @unlink($tmp);
        return null;
    }

    return (int)$id;
}

function parse_product_html(string $html, string $url): array {
    $data = [
        'title'             => '',
        'slug'              => '',
        'regular_price'     => '',
        'sale_price'        => '',
        'description'       => '',
        'short_description' => '',
        'featured_image'    => '',
        'gallery'           => [],
        'categories'        => [],
        'specs'             => [],
        'seo_title'         => '',
        'seo_desc'          => '',
        'seo_focuskw'       => '',
    ];

    // Slug from URL
    if (preg_match('/\/san-pham\/([^\/]+)\/?/', $url, $m)) {
        $data['slug'] = $m[1];
    }

    // Title
    if (preg_match('/<h1[^>]*class="[^"]*product-title[^"]*"[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        $data['title'] = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $m)) {
        $data['title'] = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    // Prices
    // Case 1: Sale price + Regular price (del / ins)
    if (preg_match('/<ins[^>]*>.*?<bdi[^>]*>(.*?)<\/bdi>.*?<\/ins>/is', $html, $m_ins)) {
        $data['sale_price'] = preg_replace('/[^\d]/', '', $m_ins[1]);
    }
    if (preg_match('/<del[^>]*>.*?<bdi[^>]*>(.*?)<\/bdi>.*?<\/del>/is', $html, $m_del)) {
        $data['regular_price'] = preg_replace('/[^\d]/', '', $m_del[1]);
    }
    // Case 2: Single price (p.price bdi)
    if (empty($data['regular_price']) && preg_match('/<p[^>]*class="[^"]*price[^"]*"[^>]*>.*?<bdi[^>]*>(.*?)<\/bdi>/is', $html, $m_p)) {
        $data['regular_price'] = preg_replace('/[^\d]/', '', $m_p[1]);
    }
    // Case 3: Price in schema / meta
    if (empty($data['regular_price']) && preg_match('/"price":\s*"(\d+)"/i', $html, $m_schema)) {
        $data['regular_price'] = $m_schema[1];
    }
    if (empty($data['regular_price']) && preg_match('/"price":\s*(\d+)/i', $html, $m_schema2)) {
        $data['regular_price'] = (string)$m_schema2[1];
    }

    // Featured Image
    if (preg_match('/<div[^>]*class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*data-thumb="([^"]+)"/i', $html, $m)) {
        $data['featured_image'] = preg_replace('/-\d+x\d+(\.[a-zA-Z]{3,4})$/', '$1', $m[1]);
    } elseif (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
        $data['featured_image'] = $m[1];
    }

    // Gallery Images
    if (preg_match_all('/<div[^>]*class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*data-thumb="([^"]+)"/i', $html, $gall_m)) {
        foreach ($gall_m[1] as $g_url) {
            $full_g = preg_replace('/-\d+x\d+(\.[a-zA-Z]{3,4})$/', '$1', $g_url);
            if ($full_g !== $data['featured_image'] && !in_array($full_g, $data['gallery'], true)) {
                $data['gallery'][] = $full_g;
            }
        }
    }

    // Categories from breadcrumbs
    if (preg_match('/<nav[^>]*class="[^"]*woocommerce-breadcrumb[^"]*"[^>]*>(.*?)<\/nav>/is', $html, $m)) {
        if (preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $m[1], $cat_matches)) {
            foreach ($cat_matches[1] as $cname) {
                $clean_c = trim(strip_tags($cname));
                if ($clean_c && !in_array(mb_strtolower($clean_c), ['trang chủ', 'sản phẩm', 'cửa hàng', 'home', 'shop'], true)) {
                    $data['categories'][] = $clean_c;
                }
            }
        }
    }

    // Description
    if (preg_match('/<div[^>]*id="tab-description"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $m)) {
        $data['description'] = trim($m[1]);
    } elseif (preg_match('/<div[^>]*class="[^"]*woocommerce-product-details__short-description[^"]*"[^>]*>(.*?)<\/div>/is', $html, $m)) {
        $data['short_description'] = trim($m[1]);
    }

    // Clean Flatsome shortcodes
    if ($data['description']) {
        $data['description'] = preg_replace('/\[\/?(ux_[^\]]*|row[^\]]*|col[^\]]*|section[^\]]*|accordion[^\]]*|tab[^\]]*)\]/i', '', $data['description']);
    }

    // Specifications (TSKT table)
    if (preg_match('/<table[^>]*>(.*?)<\/table>/is', $html, $tbl_match)) {
        if (preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $tbl_match[1], $row_matches, PREG_SET_ORDER)) {
            foreach ($row_matches as $rm) {
                $k = trim(strip_tags($rm[1]));
                $v = trim(strip_tags($rm[2]));
                if ($k && $v) {
                    $data['specs'][] = [
                        'tskt_label' => $k,
                        'tskt_value' => $v,
                    ];
                }
            }
        }
    }

    // SEO Data
    if (preg_match('/<title>(.*?)<\/title>/i', $html, $m)) {
        $data['seo_title'] = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta name="description" content="([^"]+)"/i', $html, $m)) {
        $data['seo_desc'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta name="keywords" content="([^"]+)"/i', $html, $m)) {
        $data['seo_focuskw'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    return $data;
}

$results = [];

foreach ($urls as $url) {
    echo "---------------------------------------------------------\n";
    echo "🌐 Đang xử lý: $url\n";

    $html = fetch_page($url);
    if (!$html) {
        echo "❌ Lỗi: Không thể cào dữ liệu từ $url\n";
        continue;
    }

    $parsed = parse_product_html($html, $url);
    $slug = $parsed['slug'];
    $title = $parsed['title'];

    if (empty($title) || empty($slug)) {
        echo "❌ Lỗi: Không trích xuất được tiêu đề/slug.\n";
        continue;
    }

    echo "   🏷️  Tiêu đề: $title\n";
    echo "   💰 Giá thường: " . ($parsed['regular_price'] ? number_format((float)$parsed['regular_price']) . 'đ' : 'Không') . "\n";
    echo "   🔥 Giá sale: " . ($parsed['sale_price'] ? number_format((float)$parsed['sale_price']) . 'đ' : 'Không') . "\n";

    // Tìm sản phẩm hiện có theo slug
    $existing_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'product' LIMIT 1",
        $slug
    ));

    $is_new = false;
    if ($existing_id > 0) {
        $product_id = $existing_id;
        echo "   🔄 Cập nhật sản phẩm đã có [ID: $product_id]\n";
    } else {
        $is_new = true;
        $product_id = wp_insert_post([
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $parsed['description'],
            'post_excerpt' => $parsed['short_description'],
            'post_status'  => 'publish',
            'post_type'    => 'product',
            'menu_order'   => $next_order++,
        ]);
        echo "   ✨ Đã tạo mới sản phẩm [ID: $product_id] | menu_order: " . ($next_order - 1) . "\n";
    }

    if (is_wp_error($product_id) || $product_id <= 0) {
        echo "❌ Lỗi tạo/cập nhật sản phẩm.\n";
        continue;
    }

    // Cập nhật menu_order cho sản phẩm còn hàng
    if (!$is_new) {
        $wpdb->update(
            $wpdb->posts,
            ['menu_order' => $next_order++],
            ['ID' => $product_id]
        );
    }

    // Giá & Trạng thái còn hàng
    $reg = $parsed['regular_price'];
    $sale = $parsed['sale_price'];
    $price = $sale ?: $reg;

    update_post_meta($product_id, '_stock_status', 'instock');
    update_post_meta($product_id, '_manage_stock', 'no');
    update_post_meta($product_id, '_price', $price);
    update_post_meta($product_id, '_regular_price', $reg);
    update_post_meta($product_id, '_sale_price', $sale);

    // Gỡ term outofstock khỏi product_visibility nếu có
    $outofstock_term = get_term_by('slug', 'outofstock', 'product_visibility');
    if ($outofstock_term) {
        $wpdb->delete($wpdb->term_relationships, [
            'object_id'        => $product_id,
            'term_taxonomy_id' => (int)$outofstock_term->term_taxonomy_id,
        ]);
    }

    // Hình ảnh đại diện & gallery
    if ($parsed['featured_image']) {
        $thumb_id = sideload_media($parsed['featured_image'], $product_id, $title);
        if ($thumb_id) {
            set_post_thumbnail($product_id, $thumb_id);
            echo "   🖼️  Ảnh đại diện: Đã đính kèm [Media ID: $thumb_id]\n";
        }
    }

    if (!empty($parsed['gallery'])) {
        $gal_ids = [];
        foreach ($parsed['gallery'] as $g_url) {
            $gid = sideload_media($g_url, $product_id, $title);
            if ($gid) {
                $gal_ids[] = $gid;
            }
        }
        if (!empty($gal_ids)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gal_ids));
            echo "   📸 Thư viện ảnh: Đã tải " . count($gal_ids) . " ảnh\n";
        }
    }

    // Danh mục
    if (!empty($parsed['categories'])) {
        $cat_ids = [];
        foreach ($parsed['categories'] as $cname) {
            $term = term_exists($cname, 'product_cat');
            if (!$term) {
                $term = wp_insert_term($cname, 'product_cat');
            }
            if (!is_wp_error($term) && isset($term['term_id'])) {
                $cat_ids[] = (int)$term['term_id'];
            }
        }
        if (!empty($cat_ids)) {
            wp_set_object_terms($product_id, $cat_ids, 'product_cat', true);
            echo "   📂 Danh mục: " . implode(', ', $parsed['categories']) . "\n";
        }
    }

    // TSKT ACF repeater tskt_rows
    if (!empty($parsed['specs'])) {
        update_field('tskt_rows', $parsed['specs'], $product_id);
        echo "   📋 Thông số kỹ thuật: Đã lưu " . count($parsed['specs']) . " thông số vào ACF\n";
    }

    // SEO Rank Math
    if ($parsed['seo_title']) {
        update_post_meta($product_id, 'rank_math_title', $parsed['seo_title']);
    }
    if ($parsed['seo_desc']) {
        update_post_meta($product_id, 'rank_math_description', $parsed['seo_desc']);
    }
    if ($parsed['seo_focuskw']) {
        update_post_meta($product_id, 'rank_math_focus_keyword', $parsed['seo_focuskw']);
    }

    // Xóa transient cache
    delete_transient('spl_pcard_' . $product_id);
    wc_delete_product_transients($product_id);

    $results[$url] = [
        'product_id' => $product_id,
        'title'      => $title,
        'price'      => $price,
        'stock'      => 'instock',
    ];
}

// Làm sạch transient toàn bộ
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spl_pcard_%' OR option_name LIKE '_transient_timeout_spl_pcard_%'");
wp_cache_flush();

echo "\n=========================================================\n";
echo "🎉 HOÀN TẤT NHẬP " . count($results) . " SẢN PHẨM CÒN BÁN VÀO DAILYNEW!\n";
echo "=========================================================\n";
