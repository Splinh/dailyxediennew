<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

$progress_file = __DIR__ . '/../docs/crawl_progress.json';
$progress = json_decode(file_get_contents($progress_file), true);

global $wpdb;

foreach ($progress as $url => $item) {
    $product_id = (int)($item['product_id'] ?? 0);
    if ($product_id <= 0) continue;

    $p = wc_get_product($product_id);
    if ($p) {
        $p->set_menu_order(9999);
        $p->set_price('');
        $p->set_regular_price('');
        $p->set_sale_price('');
        $p->set_stock_status('outofstock');
        $p->set_manage_stock('no');
        $p->save();
    }

    clean_post_cache($product_id);
    delete_transient('spl_pcard_' . $product_id);
    wc_delete_product_transients($product_id);
}

echo "Done updating menu_order = 9999 for all products with post cache cleaned.\n";
