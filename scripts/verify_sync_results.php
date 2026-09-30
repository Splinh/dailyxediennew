<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

echo "=========================================================\n";
echo "🔍 FINAL COMPREHENSIVE VERIFICATION OF SYNC RESULTS\n";
echo "=========================================================\n\n";

// 1. Verify AIE DONE 911
echo "--- 1. VERIFYING PRODUCT: AIE DONE 911 ---\n";
$p = wc_get_product(6214);
if ($p) {
    echo 'Name: ' . $p->get_name() . PHP_EOL;
    echo 'Slug: ' . $p->get_slug() . PHP_EOL;
    echo 'Type: ' . $p->get_type() . PHP_EOL;
    echo 'Stock: ' . $p->get_stock_status() . PHP_EOL;
    echo 'Price HTML: ' . strip_tags($p->get_price_html()) . PHP_EOL;
    echo 'Menu Order: ' . $p->get_menu_order() . PHP_EOL;
    echo 'Variations count: ' . count($p->get_children()) . PHP_EOL;
    foreach ($p->get_children() as $vid) {
        $v = wc_get_product($vid);
        echo '  - Variation ' . $vid . ': ' . $v->get_name() . ' | Price: ' . number_format((float)$v->get_price()) . 'đ | Stock: ' . $v->get_stock_status() . PHP_EOL;
    }
    echo 'Thumb ID: ' . $p->get_image_id() . ' | URL: ' . wp_get_attachment_url((int)$p->get_image_id()) . PHP_EOL;
    echo 'Categories: ' . implode(', ', wp_get_post_terms(6214, 'product_cat', ['fields' => 'names'])) . PHP_EOL;
    echo 'Rank Math Title: ' . get_post_meta(6214, 'rank_math_title', true) . PHP_EOL;
    echo 'Rank Math Desc: ' . get_post_meta(6214, 'rank_math_description', true) . PHP_EOL;
} else {
    echo "❌ Product 6214 not found!\n";
}

// 2. Verify Posts & Newly Imported Posts
echo "\n--- 2. VERIFYING NEWLY IMPORTED POSTS ---\n";
$new_posts_slugs = [
    'pin-lithium-la-gi',
    'bluera-viet-nhat-toan-phat-khai-truong-uu-dai',
    'cua-hang-xe-dien-huy-hoang-khai-truong-tai-an-giang',
    'uu-dai-xe-dien-tai-gia-lai-cung-cua-hang-toan-phat',
    'coming-soon-xe-dien-bluera-viet-nhat-huy-hoang',
    'dai-ly-xe-dien-bluera-viet-nhat-ut-ngoc-khai-truong-tai-lam-dong',
    'uu-dai-mua-xe-dien-tai-lam-dong-bluera-viet-nhat-ut-ngoc',
    'grand-opening-uu-dai-xe-dien-chien-an-giang',
    'uu-dai-khai-truong-cua-hang-xe-dien-chien-an-giang',
];

foreach ($new_posts_slugs as $slug) {
    $post = get_page_by_path($slug, OBJECT, 'post');
    if ($post) {
        $thumb_id = get_post_thumbnail_id($post->ID);
        $thumb_url = $thumb_id ? wp_get_attachment_url($thumb_id) : 'NONE';
        $cats = wp_get_post_terms($post->ID, 'category', ['fields' => 'names']);
        $rm_title = get_post_meta($post->ID, 'rank_math_title', true);
        echo sprintf("✅ ID=%d | Date: %s | Slug: %s\n   Title: %s\n   Thumb: %s\n   Cats: %s\n   RM Title: %s\n",
            $post->ID,
            $post->post_date,
            $post->post_name,
            mb_substr($post->post_title, 0, 45),
            $thumb_url,
            implode(', ', $cats),
            $rm_title ?: '(uses default)'
        );
    } else {
        echo "❌ Post with slug $slug NOT found!\n";
    }
}

// 3. Verify Store Locations
echo "\n--- 3. VERIFYING 6 NEW STORE LOCATIONS ---\n";
$store_slugs = [
    'cua-hang-bluera-viet-nhat-nhan-tam',
    'dai-ly-bluera-viet-nhat-quang-minh',
    'cua-hang-bluera-viet-nhat-lan-anh',
    'dai-ly-bluera-viet-nhat-ut-ngoc',
    'cua-hang-bluera-viet-nhat-huy-hoang',
    'cua-hang-bluera-viet-nhat-toan-phat',
];

foreach ($store_slugs as $slug) {
    $store = get_page_by_path($slug, OBJECT, 'local_store');
    if ($store) {
        $addr = get_post_meta($store->ID, 'localstore_address', true);
        $phone = get_post_meta($store->ID, 'localstore_phone', true);
        $state = wp_get_post_terms($store->ID, 'local_store_state', ['fields' => 'names']);
        $type = wp_get_post_terms($store->ID, 'store_type', ['fields' => 'names']);
        echo sprintf("✅ Store ID=%d | Name: %s\n   Addr: %s | Phone: %s | State: %s | Type: %s\n",
            $store->ID,
            mb_substr($store->post_title, 0, 40),
            mb_substr($addr, 0, 45),
            $phone,
            implode(', ', $state),
            implode(', ', $type)
        );
    } else {
        echo "❌ Store with slug $slug NOT found!\n";
    }
}

// 4. Verify Homepage SEO
echo "\n--- 4. VERIFYING HOMEPAGE SEO (Page 10) ---\n";
echo "Homepage ID: 10\n";
echo "Rank Math Title: " . get_post_meta(10, 'rank_math_title', true) . PHP_EOL;
echo "Rank Math Desc: " . get_post_meta(10, 'rank_math_description', true) . PHP_EOL;
echo "Rank Math KW: " . get_post_meta(10, 'rank_math_focus_keyword', true) . PHP_EOL;

// 5. Total counts check
global $wpdb;
$post_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
$prod_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'");
$store_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->posts} WHERE post_type = 'local_store' AND post_status = 'publish'");

echo "\n--- 5. TOTAL COUNTS IN DAILYNEW ---\n";
echo "Published Posts: $post_count\n";
echo "Published Products: $prod_count\n";
echo "Published Local Stores: $store_count\n";
