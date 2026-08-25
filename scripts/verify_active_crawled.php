<?php
require_once __DIR__ . '/../wp/wp-load.php';

$slugs = [
    'xe-lan-dien-performance-2019',
    'xe-3-banh-supper-one',
    'xe-dap-the-thao-catani-360-26ca-360',
    'xe-dien-gap-concise-3-banh-2pin',
    'xe-dien-scooter-concise-2-pin',
    'xe-may-dien-3-banh-one',
];

echo "=========================================================\n";
echo "📊 KIỂM TRA 6 SẢN PHẨM CÒN BÁN VỪA CÀO\n";
echo "=========================================================\n";

foreach ($slugs as $i => $slug) {
    $found = get_page_by_path($slug, OBJECT, 'product');
    if ($found) {
        $p = wc_get_product($found->ID);
        echo sprintf("%d. [ID %d] %s\n", $i + 1, $p->get_id(), $p->get_name());
        echo "   - Tình trạng: " . ($p->is_in_stock() ? '✅ Còn hàng (instock)' : '❌ Hết hàng') . "\n";
        echo "   - Giá bán: " . number_format((float)$p->get_price()) . "đ\n";
        if ($p->get_regular_price() > $p->get_price()) {
            echo "   - Giá gốc: " . number_format((float)$p->get_regular_price()) . "đ\n";
        }
        echo "   - Thứ tự hiển thị (menu_order): " . $p->get_menu_order() . "\n";
        echo "   - Ảnh: " . ($p->get_image_id() > 0 ? "Media ID " . $p->get_image_id() : "Chưa có") . "\n";
        $terms = wp_get_post_terms($p->get_id(), 'product_cat', ['fields' => 'names']);
        echo "   - Danh mục: " . implode(', ', $terms) . "\n\n";
    } else {
        echo sprintf("%d. ❌ Không tìm thấy slug: %s\n\n", $i + 1, $slug);
    }
}
echo "=========================================================\n";
