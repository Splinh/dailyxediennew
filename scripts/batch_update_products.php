<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

$progress_file = __DIR__ . '/../docs/crawl_progress.json';
$progress = json_decode(file_get_contents($progress_file), true);

$ids = [];
foreach ($progress as $url => $item) {
    $product_id = (int)($item['product_id'] ?? 0);
    if ($product_id > 0) {
        $ids[] = $product_id;
    }
}

if (empty($ids)) {
    die("No product IDs found.\n");
}

global $wpdb;
$ids_str = implode(',', array_map('intval', $ids));

// 1. Update menu_order = 9999
$wpdb->query("UPDATE {$wpdb->posts} SET menu_order = 9999 WHERE ID IN ($ids_str)");

// 2. Clear prices
$wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = '' WHERE meta_key IN ('_price', '_regular_price', '_sale_price') AND post_id IN ($ids_str)");

// 3. Set stock_status = 'outofstock'
$wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = 'outofstock' WHERE meta_key = '_stock_status' AND post_id IN ($ids_str)");
$wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = 'no' WHERE meta_key = '_manage_stock' AND post_id IN ($ids_str)");

// 4. Ensure term relationship for outofstock in product_visibility
$outofstock_term = get_term_by('slug', 'outofstock', 'product_visibility');
if ($outofstock_term) {
    $tt_id = (int)$outofstock_term->term_taxonomy_id;
    foreach ($ids as $id) {
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES (%d, %d, 0)",
            $id,
            $tt_id
        ));
    }
    wp_update_term_count_now([$tt_id], 'product_visibility');
}

// 5. Delete all transient caches
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spl_pcard_%' OR option_name LIKE '_transient_timeout_spl_pcard_%' OR option_name LIKE '_transient_wc_product_%' OR option_name LIKE '_transient_timeout_wc_product_%'");

wp_cache_flush();

echo "Successfully updated " . count($ids) . " products to menu_order 9999 and cleared prices!\n";
