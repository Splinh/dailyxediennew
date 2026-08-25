<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

$slugs = ['xe-3-banh-che-suzuki', 'xe-3-banh-che-vision-trang', 'xe-dap-dien-asama-ebk'];
foreach ($slugs as $slug) {
    $p = get_page_by_path($slug, OBJECT, 'product');
    if (!$p) {
        echo "Product not found: $slug\n";
        continue;
    }
    echo "Testing render for: $slug (ID: {$p->ID})\n";
    global $wp_query, $post, $product;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
    $wp_query->queried_object = $p;
    $wp_query->queried_object_id = $p->ID;
    $wp_query->posts = [$p];
    $wp_query->post_count = 1;
    $wp_query->current_post = -1;
    $post = $p;
    
    ob_start();
    try {
        include get_template_directory() . '/woocommerce/single-product.php';
        $out = ob_get_clean();
        echo " -> Rendered OK, HTML length: " . strlen($out) . "\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo " -> ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
