<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

$all_urls = json_decode(file_get_contents(__DIR__ . '/../docs/all_dailyxedien_products.json'), true);
echo "Total URLs from dailyxedien.vn: " . count($all_urls) . PHP_EOL;

global $wpdb;
$wp_products = $wpdb->get_results(
    "SELECT ID, post_name, post_title, post_status FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status IN ('publish', 'draft', 'pending', 'private')",
    ARRAY_A
);

$wp_slugs = [];
foreach ($wp_products as $p) {
    $wp_slugs[$p['post_name']] = $p;
}

echo "Total products in local WP: " . count($wp_slugs) . PHP_EOL;

$missing = [];
$matched = [];

foreach ($all_urls as $url) {
    $path = trim(parse_url($url, PHP_URL_PATH), '/');
    $parts = explode('/', $path);
    $slug = end($parts);
    
    if (isset($wp_slugs[$slug])) {
        $matched[] = ['url' => $url, 'slug' => $slug, 'post' => $wp_slugs[$slug]];
    } else {
        $missing[] = ['url' => $url, 'slug' => $slug];
    }
}

echo "Matched in WP: " . count($matched) . PHP_EOL;
echo "Missing in WP: " . count($missing) . PHP_EOL;

file_put_contents(__DIR__ . '/../docs/missing_products.json', json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
