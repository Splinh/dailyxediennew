<?php
require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$out_or_noprice = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order, pm_stock.meta_value as stock_status, pm_price.meta_value as price
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
      AND (pm_stock.meta_value = 'outofstock' OR pm_price.meta_value = '' OR pm_price.meta_value IS NULL OR pm_price.meta_value = '0')
");

echo "Total products out of stock or without price: " . count($out_or_noprice) . "\n";

$instock_with_price = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.menu_order, pm_price.meta_value as price
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock_status'
    LEFT JOIN {$wpdb->postmeta} pm_price ON p.ID = pm_price.post_id AND pm_price.meta_key = '_price'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
      AND pm_stock.meta_value = 'instock' AND pm_price.meta_value != '' AND pm_price.meta_value > 0
    ORDER BY p.menu_order ASC, p.post_title ASC
");

echo "Total products IN STOCK with price: " . count($instock_with_price) . "\n";
echo "\nFirst 10 in-stock products:\n";
foreach (array_slice($instock_with_price, 0, 10) as $p) {
    echo " - [ID {$p->ID}] {$p->post_title} | Price: {$p->price} | MenuOrder: {$p->menu_order}\n";
}
