<?php
/**
 * Chuyển toàn bộ 158 sản phẩm trong danh sách không còn bán về trạng thái Hết hàng (outofstock).
 */

declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

$progress_file = __DIR__ . '/../docs/crawl_progress.json';
if (!file_exists($progress_file)) {
    die("❌ Không tìm thấy file $progress_file\n");
}

$progress = json_decode(file_get_contents($progress_file), true);
if (empty($progress)) {
    die("❌ Danh sách rỗng.\n");
}

echo "=========================================================\n";
echo "📦 ĐANG CẬP NHẬT TRẠNG THÁI HẾT HÀNG (OUT OF STOCK)\n";
echo "=========================================================\n\n";

global $wpdb;

// Lấy term_taxonomy_id của outofstock trong product_visibility
$outofstock_term = get_term_by('slug', 'outofstock', 'product_visibility');
$outofstock_tt_id = $outofstock_term ? (int)$outofstock_term->term_taxonomy_id : 0;

$updated_count = 0;
$already_out = 0;
$not_found = 0;

foreach ($progress as $url => $item) {
    $product_id = (int)($item['product_id'] ?? 0);

    if ($product_id <= 0 && !empty($item['slug'])) {
        $slug = sanitize_title($item['slug']);
        $product_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'product' LIMIT 1",
            $slug
        ));
    }

    if ($product_id <= 0) {
        echo "⚠️ Không tìm thấy ID cho: $url\n";
        $not_found++;
        continue;
    }

    $current_status = get_post_meta($product_id, '_stock_status', true);

    // Cập nhật postmeta
    update_post_meta($product_id, '_stock_status', 'outofstock');
    update_post_meta($product_id, '_manage_stock', 'no');

    // Gán term outofstock vào taxonomy product_visibility
    if ($outofstock_tt_id > 0) {
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES (%d, %d, 0)",
            $product_id,
            $outofstock_tt_id
        ));
    }

    // Xóa transient cache WC
    wc_delete_product_transients($product_id);

    if ($current_status !== 'outofstock') {
        $title = get_the_title($product_id);
        echo "🔄 [ID: $product_id] Đã chuyển sang 'outofstock': $title\n";
        $updated_count++;
    } else {
        $already_out++;
    }
}

// Cập nhật lại term count cho product_visibility
if ($outofstock_term) {
    wp_update_term_count_now([(int)$outofstock_term->term_taxonomy_id], 'product_visibility');
}

echo "\n=========================================================\n";
echo "🏁 HOÀN TẤT CẬP NHẬT TRẠNG THÁI HẾT HÀNG!\n";
echo "📊 Thống kê:\n";
echo "   - Đã chuyển sang hết hàng: $updated_count sản phẩm\n";
echo "   - Đã ở trạng thái hết hàng từ trước: $already_out sản phẩm\n";
echo "   - Không tìm thấy: $not_found sản phẩm\n";
echo "   - Tổng số sản phẩm trong danh sách: " . count($progress) . "\n";
echo "=========================================================\n";
