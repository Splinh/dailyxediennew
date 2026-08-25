<?php
require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

// 1. Check all outofstock products
$all_outofstock = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order, pm.meta_value as stock_status
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ORDER BY p.ID DESC
");

$instock_count = 0;
$outofstock_count = 0;
$null_stock = 0;

foreach ($all_outofstock as $row) {
    if ($row->stock_status === 'instock') $instock_count++;
    elseif ($row->stock_status === 'outofstock') $outofstock_count++;
    else $null_stock++;
}

echo "Total published products: " . count($all_outofstock) . "\n";
echo "In stock: $instock_count\n";
echo "Out of stock: $outofstock_count\n";
echo "Null/empty stock: $null_stock\n";

// 2. Check "Xe 3 bánh 50cc"
$x3b = $wpdb->get_row("
    SELECT p.ID, p.post_title, p.menu_order, pm.meta_value as stock_status
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_title LIKE '%Xe 3 bánh 50cc%' AND p.post_type = 'product'
");
if ($x3b) {
    echo "\nXe 3 bánh 50cc: ID={$x3b->ID}, MenuOrder={$x3b->menu_order}, StockStatus={$x3b->stock_status}\n";
    $terms = wp_get_post_terms($x3b->ID, 'product_cat', ['fields' => 'names']);
    echo "Categories: " . implode(', ', $terms) . "\n";
}

// 3. Check products with menu_order < 9999 that have outofstock
$out_low_menu = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order, pm.meta_value as stock_status
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_value = 'outofstock' AND p.menu_order < 9999
");
echo "\nOut of stock products with menu_order < 9999: " . count($out_low_menu) . "\n";
foreach (array_slice($out_low_menu, 0, 10) as $r) {
    echo " - [ID {$r->ID}] {$r->post_title} | menu_order: {$r->menu_order}\n";
}

// 4. Check category "Sản phẩm mới"
$sp_moi = get_term_by('slug', 'san-pham-moi', 'product_cat');
if ($sp_moi) {
    $sp_moi_prods = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => [[
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $sp_moi->term_id
        ]]
    ]);
    echo "\nTotal products in 'Sản phẩm mới': " . count($sp_moi_prods) . "\n";
}
