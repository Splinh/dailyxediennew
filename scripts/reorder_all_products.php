<?php
/**
 * Chuẩn hóa toàn bộ sản phẩm:
 * 1. Tất cả sản phẩm CÒN HÀNG (có giá): Đặt menu_order từ 1 -> N (hiển thị trang đầu)
 * 2. Tất cả sản phẩm HẾT HÀNG / KHÔNG CÒN BÁN (giá rỗng hoặc outofstock):
 *    - Đặt menu_order = 9999 (đẩy về trang cuối)
 *    - Đặt stock_status = 'outofstock'
 *    - Xóa khỏi danh mục 'Sản phẩm mới' (san-pham-moi)
 *    - Xóa toàn bộ transient cache để giao diện cập nhật ngay
 */

declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

echo "=========================================================\n";
echo "🚀 BẮT ĐẦU CHUẨN HÓA THỨ TỰ TOÀN BỘ SẢN PHẨM TRÊN SITE\n";
echo "=========================================================\n\n";

$sp_moi_term = get_term_by('slug', 'san-pham-moi', 'product_cat');
$sp_moi_tt_id = $sp_moi_term ? (int)$sp_moi_term->term_taxonomy_id : 0;

$outofstock_term = get_term_by('slug', 'outofstock', 'product_visibility');
$outofstock_tt_id = $outofstock_term ? (int)$outofstock_term->term_taxonomy_id : 0;

// 1. Lấy toàn bộ sản phẩm CÒN HÀNG có giá hợp lệ
$instock_rows = $wpdb->get_results("
    SELECT p.ID, p.post_title, pm_price.meta_value as price
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
      AND (pm_stock.meta_value IS NULL OR pm_stock.meta_value != 'outofstock')
      AND pm_price.meta_value IS NOT NULL AND pm_price.meta_value != '' AND pm_price.meta_value > 0
    ORDER BY p.menu_order ASC, p.ID DESC
");

$order = 1;
$instock_ids = [];
foreach ($instock_rows as $row) {
    $instock_ids[] = (int)$row->ID;
    $wpdb->update(
        $wpdb->posts,
        ['menu_order' => $order++],
        ['ID' => $row->ID]
    );
    update_post_meta((int)$row->ID, '_stock_status', 'instock');
}

echo "✅ Đã sắp xếp " . count($instock_ids) . " sản phẩm CÒN HÀNG lên đầu (menu_order 1 -> " . ($order - 1) . ")\n";

// 2. Lấy tất cả sản phẩm HẾT HÀNG / KHÔNG CÒN BÁN (giá rỗng, hoặc stock = outofstock)
$out_rows = $wpdb->get_results("
    SELECT p.ID, p.post_title
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
      AND (
          pm_stock.meta_value = 'outofstock'
          OR pm_price.meta_value IS NULL
          OR pm_price.meta_value = ''
          OR pm_price.meta_value = '0'
      )
");

$out_ids = [];
foreach ($out_rows as $row) {
    $id = (int)$row->ID;
    $out_ids[] = $id;

    // menu_order = 9999
    $wpdb->update(
        $wpdb->posts,
        ['menu_order' => 9999],
        ['ID' => $id]
    );

    // Xóa giá & cập nhật outofstock
    update_post_meta($id, '_price', '');
    update_post_meta($id, '_regular_price', '');
    update_post_meta($id, '_sale_price', '');
    update_post_meta($id, '_stock_status', 'outofstock');
    update_post_meta($id, '_manage_stock', 'no');

    // Xóa khỏi danh mục 'Sản phẩm mới' nếu có
    if ($sp_moi_tt_id > 0) {
        $wpdb->delete(
            $wpdb->term_relationships,
            [
                'object_id'        => $id,
                'term_taxonomy_id' => $sp_moi_tt_id,
            ]
        );
    }

    // Gán nhãn outofstock vào taxonomy product_visibility
    if ($outofstock_tt_id > 0) {
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES (%d, %d, 0)",
            $id,
            $outofstock_tt_id
        ));
    }
}

echo "✅ Đã chuyển " . count($out_ids) . " sản phẩm HẾT HÀNG về các trang cuối (menu_order = 9999)\n";
echo "   - Đã xóa toàn bộ sản phẩm hết hàng khỏi danh mục 'Sản phẩm mới'\n";
echo "   - Đã gán trạng thái outofstock cho tất cả sản phẩm không có giá\n";

// Cập nhật lại đếm số lượng term
if ($sp_moi_tt_id > 0) {
    wp_update_term_count_now([$sp_moi_tt_id], 'product_cat');
}
if ($outofstock_tt_id > 0) {
    wp_update_term_count_now([$outofstock_tt_id], 'product_visibility');
}

// 3. Xóa sạch mọi transient cache
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spl_pcard_%' OR option_name LIKE '_transient_timeout_spl_pcard_%' OR option_name LIKE '_transient_wc_product_%' OR option_name LIKE '_transient_timeout_wc_product_%' OR option_name LIKE '_transient_wc_var_prices_%'");

wp_cache_flush();

echo "\n=========================================================\n";
echo "🏁 HOÀN TẤT CHUẨN HÓA DỮ LIỆU SẢN PHẨM!\n";
echo "=========================================================\n";
