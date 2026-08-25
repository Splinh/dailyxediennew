<?php
require_once __DIR__ . '/../wp/wp-load.php';

// Simulate last page query
$q = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 16,
    'paged' => 17,
    'post_status' => 'publish',
    'orderby' => 'menu_order title',
    'order' => 'ASC',
]);

echo "========================================\n";
echo "📊 KIỂM TRA TRANG CUỐI (Trang 17 /san-pham/page/17/)\n";
echo "========================================\n";
echo "Hiển thị " . count($q->posts) . " sản phẩm trang cuối:\n\n";

foreach ($q->posts as $i => $p) {
    $prod = wc_get_product($p->ID);
    $is_in_stock = $prod->is_in_stock() && $prod->get_stock_status() !== 'outofstock';
    $price = $prod->get_price();
    $status_label = $is_in_stock ? '✅ Còn hàng' : '❌ HẾT HÀNG';

    echo sprintf("%2d. [ID %4d] (Order: %4d) %-40s | %s | Giá: %s\n", 
        $i + 1, 
        $p->ID, 
        $p->menu_order, 
        mb_substr($prod->get_name(), 0, 40), 
        $status_label, 
        $price ? number_format((float)$price) . 'đ' : 'Liên hệ'
    );
}
echo "========================================\n";
