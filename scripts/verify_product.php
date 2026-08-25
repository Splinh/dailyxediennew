<?php
require_once __DIR__ . '/../wp/wp-load.php';

$pid = 6147;
$p = wc_get_product($pid);
if ($p) {
    echo 'Title: ' . $p->get_name() . PHP_EOL;
    echo 'Slug: ' . $p->get_slug() . PHP_EOL;
    echo 'Status: ' . $p->get_status() . PHP_EOL;
    echo 'Stock: ' . $p->get_stock_status() . PHP_EOL;
    echo 'Price: ' . $p->get_price() . PHP_EOL;
    echo 'Thumb ID: ' . $p->get_image_id() . PHP_EOL;
    echo 'Thumb URL: ' . wp_get_attachment_url($p->get_image_id()) . PHP_EOL;
    echo 'Categories: ' . implode(' > ', wp_get_post_terms($pid, 'product_cat', ['fields'=>'names'])) . PHP_EOL;
    echo 'SEO Title: ' . get_post_meta($pid, 'rank_math_title', true) . PHP_EOL;
    echo 'SEO Desc: ' . get_post_meta($pid, 'rank_math_description', true) . PHP_EOL;
    echo 'Content length: ' . strlen($p->get_description()) . ' chars' . PHP_EOL;
}
