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
    echo "=== Full Web Simulation: $slug (ID: {$p->ID}) ===\n";
    global $wp_query, $post, $product;
    $wp_query->is_single = true;
    $wp_query->is_singular = true;
    $wp_query->is_page = false;
    $wp_query->queried_object = $p;
    $wp_query->queried_object_id = $p->ID;
    $wp_query->posts = [$p];
    $wp_query->post_count = 1;
    $wp_query->current_post = -1;
    $post = $p;
    $product = wc_get_product($p->ID);
    
    // Test wp_head
    try {
        ob_start();
        do_action('wp_head');
        $head_out = ob_get_clean();
        echo "  [wp_head]: OK (len: " . strlen($head_out) . ")\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "  [wp_head] ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    // Test wp_footer
    try {
        ob_start();
        do_action('wp_footer');
        $footer_out = ob_get_clean();
        echo "  [wp_footer]: OK (len: " . strlen($footer_out) . ")\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "  [wp_footer] ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }

    // Test full template
    try {
        ob_start();
        get_header();
        include get_template_directory() . '/woocommerce/single-product.php';
        get_footer();
        $full_out = ob_get_clean();
        echo "  [Full Template]: OK (len: " . strlen($full_out) . ")\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "  [Full Template] ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
