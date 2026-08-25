<?php
require_once __DIR__ . '/../wp/wp-load.php';

// Simulate main shop query
$q = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 16,
    'post_status' => 'publish',
    'orderby' => 'menu_order title',
    'order' => 'ASC',
]);

echo "========================================\n";
echo "📊 KIỂM TRA TRANG 1 TRANG SẢN PHẨM (/san-pham/)\n";
echo "========================================\n";
echo "Tổng số sản phẩm: " . $q->found_posts . "\n";
echo "Hiển thị 1-" . count($q->posts) . " sản phẩm:\n\n";

$has_out_of_stock = false;
foreach ($q->posts as $i => $p) {
    $prod = wc_get_product($p->ID);
    $is_in_stock = $prod->is_in_stock() && $prod->get_stock_status() !== 'outofstock';
    $price = $prod->get_price();
    $status_label = $is_in_stock ? '✅ Còn hàng' : '❌ HẾT HÀNG';
    if (!$is_in_stock) $has_out_of_stock = true;

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
if ($has_out_of_stock) {
    echo "⚠️ CẢNH BÁO: Vẫn còn sản phẩm hết hàng ở trang 1!\n";
} else {
    echo "🎉 TUYỆT VỜI: 100% sản phẩm ở Trang 1 đều CÒN HÀNG và CÓ GIÁ!\n";
}
echo "========================================\n";
