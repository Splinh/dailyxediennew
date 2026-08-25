<?php
require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$rows = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order, p.post_date, pm_stock.meta_value as stock_status, pm_price.meta_value as price
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ORDER BY (CASE WHEN pm_stock.meta_value = 'outofstock' OR pm_price.meta_value = '' OR pm_price.meta_value = '0' THEN 1 ELSE 0 END) ASC, p.menu_order ASC, p.post_title ASC
");

echo "=== FIRST 20 PRODUCTS UNDER STOCK SORTING ===\n";
foreach (array_slice($rows, 0, 20) as $r) {
    echo "ID: {$r->ID} | Stock: {$r->stock_status} | Price: '{$r->price}' | MenuOrder: {$r->menu_order} | Title: {$r->post_title}\n";
}

echo "\n=== LAST 20 PRODUCTS UNDER STOCK SORTING ===\n";
foreach (array_slice($rows, -20) as $r) {
    echo "ID: {$r->ID} | Stock: {$r->stock_status} | Price: '{$r->price}' | MenuOrder: {$r->menu_order} | Title: {$r->post_title}\n";
}
