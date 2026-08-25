<?php
/**
 * Cập nhật 158 sản phẩm ngừng kinh doanh:
 * 1. Chuyển giá thành rỗng (để hiển thị Giá: Liên hệ)
 * 2. Đặt menu_order = 9999 để xếp cuối cùng trong danh mục sản phẩm
 * 3. Đảm bảo _stock_status = 'outofstock'
 * 4. Xóa cache transient để hiển thị ngay lập tức
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
echo "🔄 ĐANG CẬP NHẬT 158 SẢN PHẨM: GIÁ LIÊN HỆ & XẾP CUỐI\n";
echo "=========================================================\n\n";

global $wpdb;

$outofstock_term = get_term_by('slug', 'outofstock', 'product_visibility');
$outofstock_tt_id = $outofstock_term ? (int)$outofstock_term->term_taxonomy_id : 0;

$count = 0;

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
        continue;
    }

    // 1. Cập nhật menu_order = 9999 trong wp_posts
    $wpdb->update(
        $wpdb->posts,
        ['menu_order' => 9999],
        ['ID' => $product_id]
    );

    // 2. Cập nhật postmeta giá rỗng & outofstock
    update_post_meta($product_id, '_price', '');
    update_post_meta($product_id, '_regular_price', '');
    update_post_meta($product_id, '_sale_price', '');
    update_post_meta($product_id, '_stock_status', 'outofstock');
    update_post_meta($product_id, '_manage_stock', 'no');

    // 3. Gán term outofstock vào taxonomy product_visibility
    if ($outofstock_tt_id > 0) {
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES (%d, %d, 0)",
            $product_id,
            $outofstock_tt_id
        ));
    }

    // 4. Xóa cache transient
    delete_transient('spl_pcard_' . $product_id);
    wc_delete_product_transients($product_id);

    $count++;
}

// Xóa tất cả transient card của theme
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_spl_pcard_%' OR option_name LIKE '_transient_timeout_spl_pcard_%'");

if ($outofstock_term) {
    wp_update_term_count_now([(int)$outofstock_term->term_taxonomy_id], 'product_visibility');
}

echo "✅ Hoàn tất cập nhật $count sản phẩm!\n";
echo "   - Giá: Đã chuyển thành 'Liên hệ'\n";
echo "   - Trạng thái: 'Hết hàng' (outofstock)\n";
echo "   - Thứ tự hiển thị: Xếp cuối cùng (menu_order = 9999)\n";
echo "   - Cache transient: Đã làm sạch toàn bộ\n";
