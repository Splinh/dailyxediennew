<?php
/**
 * Synchronize Products, Store Locations, Homepage SEO, and Rank Math Global Options
 * from daily_seo_bk to dailynew.
 */
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

global $wpdb;

$bk_db = 'daily_seo_bk';

echo "====================================================\n";
echo "🚀 SYNCING PRODUCTS, STORES & SEO OPTIONS\n";
echo "====================================================\n\n";

function sync_media_attachment(int $bk_thumb_id, string $bk_db): ?int {
    global $wpdb;

    $bk_att = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $bk_db.wp_posts WHERE ID = %d AND post_type = 'attachment'
    ", $bk_thumb_id));

    if (!$bk_att) {
        return null;
    }

    $bk_file_meta = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value FROM $bk_db.wp_postmeta WHERE post_id = %d AND meta_key = '_wp_attached_file'
    ", $bk_thumb_id));

    if (!$bk_file_meta) {
        return null;
    }

    $existing_new_att = $wpdb->get_var($wpdb->prepare("
        SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s
    ", $bk_file_meta));

    if ($existing_new_att) {
        return (int)$existing_new_att;
    }

    $upload_dir = wp_upload_dir();
    $target_file = $upload_dir['basedir'] . '/' . $bk_file_meta;
    $target_dir = dirname($target_file);

    if (!is_dir($target_dir)) {
        wp_mkdir_p($target_dir);
    }

    if (!file_exists($target_file)) {
        $source_url = 'https://dailyxedien.vn/wp-content/uploads/' . $bk_file_meta;
        echo "   Downloading image: $source_url ...\n";
        $img_data = @file_get_contents($source_url);
        if ($img_data !== false) {
            file_put_contents($target_file, $img_data);
        } else {
            echo "   ⚠️ Failed to download $source_url\n";
            return null;
        }
    }

    $file_type = wp_check_filetype(basename($target_file), null);
    $attachment = [
        'post_mime_type' => $file_type['type'] ?: 'image/jpeg',
        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($target_file)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attach_id = wp_insert_attachment($attachment, $target_file);
    if (!is_wp_error($attach_id) && $attach_id > 0) {
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_file);
        wp_update_attachment_metadata($attach_id, $attach_data);
        return $attach_id;
    }

    return null;
}

// ---------------------------------------------------------
// 1. SYNC MATCHED PRODUCTS SEO
// ---------------------------------------------------------
echo "--- 1. SYNCING MATCHED PRODUCTS SEO ---\n";

$matched_prods = $wpdb->get_results("
    SELECT b.ID as bk_id, n.ID as new_id, b.post_name, b.post_title, b.post_modified
    FROM $bk_db.wp_posts b
    JOIN {$wpdb->posts} n ON b.post_name = n.post_name
    WHERE b.post_type = 'product' AND n.post_type = 'product'
");

$prod_seo_count = 0;
foreach ($matched_prods as $p) {
    $bk_meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value FROM $bk_db.wp_postmeta 
        WHERE post_id = %d AND meta_key LIKE 'rank_math_%'
    ", $p->bk_id));

    if (!empty($bk_meta)) {
        // Delete old rank_math meta
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'rank_math_%'
        ", $p->new_id));

        foreach ($bk_meta as $m) {
            $val = $m->meta_value;
            // Prevent double-serialized string if string starts with s: or a:
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'    => $p->new_id,
                    'meta_key'   => $m->meta_key,
                    'meta_value' => $val,
                ],
                ['%d', '%s', '%s']
            );
        }
        $prod_seo_count++;
    }
}
echo "✅ Synced Rank Math SEO on $prod_seo_count matched products.\n\n";

// ---------------------------------------------------------
// 2. IMPORT NEW PRODUCT: AIE DONE 911 (aie-done-911)
// ---------------------------------------------------------
echo "--- 2. IMPORTING PRODUCT: AIE DONE 911 ---\n";

$existing_aie = $wpdb->get_var("
    SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_name = 'aie-done-911'
");

if ($existing_aie) {
    echo "Product AIE DONE 911 already exists with ID: $existing_aie\n";
    $aie_id = (int)$existing_aie;
} else {
    // 2.1 Download images for AIE DONE 911
    $aie_thumb_id = sync_media_attachment(82736, $bk_db);
    $aie_gallery_id = sync_media_attachment(82735, $bk_db);

    // 2.2 Insert product
    $bk_aie = $wpdb->get_row("SELECT * FROM $bk_db.wp_posts WHERE ID = 82734");

    $wpdb->insert($wpdb->posts, [
        'post_author'           => 1,
        'post_date'             => $bk_aie->post_date,
        'post_date_gmt'         => $bk_aie->post_date_gmt,
        'post_content'          => $bk_aie->post_content,
        'post_title'            => $bk_aie->post_title,
        'post_excerpt'          => $bk_aie->post_excerpt,
        'post_status'           => 'publish',
        'comment_status'        => 'open',
        'ping_status'           => 'closed',
        'post_name'             => 'aie-done-911',
        'post_modified'         => $bk_aie->post_modified,
        'post_modified_gmt'     => $bk_aie->post_modified_gmt,
        'post_parent'           => 0,
        'menu_order'            => 63,
        'post_type'             => 'product',
        'comment_count'         => 0,
    ]);
    $aie_id = (int)$wpdb->insert_id;
    echo "✅ Created Product AIE DONE 911 with ID: $aie_id\n";

    // 2.3 Insert parent product meta
    $attributes = [
        'pa_ac-quy-pin' => [
            'name'         => 'pa_ac-quy-pin',
            'value'        => '',
            'position'     => 0,
            'is_visible'   => 1,
            'is_variation' => 1,
            'is_taxonomy'  => 1,
        ],
    ];

    $product_meta = [
        '_sku'                     => 'AIE-DONE-911',
        'total_sales'              => '0',
        '_tax_status'              => 'taxable',
        '_tax_class'               => '',
        '_manage_stock'            => 'no',
        '_backorders'              => 'no',
        '_sold_individually'       => 'no',
        '_virtual'                 => 'no',
        '_downloadable'            => 'no',
        '_download_limit'          => '-1',
        '_download_expiry'         => '-1',
        '_stock'                   => null,
        '_stock_status'            => 'instock',
        '_wc_average_rating'       => '0',
        '_wc_review_count'         => '0',
        '_product_attributes'      => serialize($attributes),
        '_product_version'         => '10.7.0',
        '_price'                   => '15000000',
        '_regular_price'           => '17500000',
        '_sale_price'              => '15000000',
        '_thumbnail_id'            => (string)($aie_thumb_id ?? ''),
        '_product_image_gallery'   => (string)($aie_gallery_id ?? ''),
        '_tskt_product_image_id'   => (string)($aie_gallery_id ?? ''),
        'rank_math_seo_score'      => '75',
        'rank_math_title'          => 'Xe đạp điện AIE DONE 911 chính hãng Ai EBike %sep% Giá Tốt %sep% %sitename%',
        'rank_math_description'    => 'Xe đạp điện AIE DONE 911 chính hãng chất lượng cao, bền bỉ, thiết kế trẻ trung hiện đại, trả góp 0% tại Đại Lý Xe Điện.',
        'rank_math_focus_keyword'  => 'AIE DONE 911, xe đạp điện AIE DONE 911',
        'rank_math_robots'         => serialize(['index']),
    ];

    foreach ($product_meta as $mk => $mv) {
        $wpdb->insert($wpdb->postmeta, [
            'post_id'    => $aie_id,
            'meta_key'   => $mk,
            'meta_value' => $mv,
        ]);
    }

    // 2.4 Insert variations
    // Variation 1: 48V - 20Ah
    $wpdb->insert($wpdb->posts, [
        'post_author'           => 1,
        'post_date'             => $bk_aie->post_date,
        'post_date_gmt'         => $bk_aie->post_date_gmt,
        'post_content'          => '',
        'post_title'            => 'AIE DONE 911 - Ắc-quy: 48V – 20Ah',
        'post_excerpt'          => '',
        'post_status'           => 'publish',
        'comment_status'        => 'closed',
        'ping_status'           => 'closed',
        'post_name'             => 'aie-done-911-ac-quy-48v-20ah',
        'post_modified'         => $bk_aie->post_modified,
        'post_modified_gmt'     => $bk_aie->post_modified_gmt,
        'post_parent'           => $aie_id,
        'menu_order'            => 0,
        'post_type'             => 'product_variation',
        'comment_count'         => 0,
    ]);
    $var1_id = (int)$wpdb->insert_id;

    $var1_meta = [
        '_sku'                    => 'AIE-DONE-911-48V',
        'total_sales'             => '0',
        '_tax_status'             => 'taxable',
        '_tax_class'              => 'parent',
        '_manage_stock'           => 'no',
        '_backorders'             => 'no',
        '_stock'                  => null,
        '_stock_status'           => 'instock',
        '_regular_price'          => '17500000',
        '_sale_price'             => '15000000',
        '_price'                  => '15000000',
        'attribute_pa_ac-quy-pin' => 'ac-quy-48v-20ah',
        '_thumbnail_id'           => (string)($aie_thumb_id ?? ''),
    ];
    foreach ($var1_meta as $mk => $mv) {
        $wpdb->insert($wpdb->postmeta, ['post_id' => $var1_id, 'meta_key' => $mk, 'meta_value' => $mv]);
    }
    echo "   Created variation 1 (48V-20Ah): ID $var1_id\n";

    // Variation 2: 60V - 20Ah
    $wpdb->insert($wpdb->posts, [
        'post_author'           => 1,
        'post_date'             => $bk_aie->post_date,
        'post_date_gmt'         => $bk_aie->post_date_gmt,
        'post_content'          => '',
        'post_title'            => 'AIE DONE 911 - Ắc-quy: 60V – 20Ah',
        'post_excerpt'          => '',
        'post_status'           => 'publish',
        'comment_status'        => 'closed',
        'ping_status'           => 'closed',
        'post_name'             => 'aie-done-911-ac-quy-60v-20ah',
        'post_modified'         => $bk_aie->post_modified,
        'post_modified_gmt'     => $bk_aie->post_modified_gmt,
        'post_parent'           => $aie_id,
        'menu_order'            => 1,
        'post_type'             => 'product_variation',
        'comment_count'         => 0,
    ]);
    $var2_id = (int)$wpdb->insert_id;

    $var2_meta = [
        '_sku'                    => 'AIE-DONE-911-60V',
        'total_sales'             => '0',
        '_tax_status'             => 'taxable',
        '_tax_class'              => 'parent',
        '_manage_stock'           => 'no',
        '_backorders'             => 'no',
        '_stock'                  => null,
        '_stock_status'           => 'instock',
        '_regular_price'          => '18000000',
        '_sale_price'             => '15500000',
        '_price'                  => '15500000',
        'attribute_pa_ac-quy-pin' => 'ac-quy-60v-20ah',
        '_thumbnail_id'           => (string)($aie_thumb_id ?? ''),
    ];
    foreach ($var2_meta as $mk => $mv) {
        $wpdb->insert($wpdb->postmeta, ['post_id' => $var2_id, 'meta_key' => $mk, 'meta_value' => $mv]);
    }
    echo "   Created variation 2 (60V-20Ah): ID $var2_id\n";

    // 2.5 Assign terms & taxonomies
    $terms_to_assign = [
        ['term_taxonomy_id' => 4],   // product_type = variable
        ['term_taxonomy_id' => 42],  // product_cat = Xe Đạp Điện
        ['term_taxonomy_id' => 43],  // product_cat = Xe Đạp Điện Ai EBike
        ['term_taxonomy_id' => 224], // language = vi
    ];

    // pa_ac-quy-pin terms
    $tt_48 = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = 'ac-quy-48v-20ah' AND tt.taxonomy = 'pa_ac-quy-pin'");
    if ($tt_48) $terms_to_assign[] = ['term_taxonomy_id' => (int)$tt_48];
    $tt_60 = $wpdb->get_var("SELECT tt.term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = 'ac-quy-60v-20ah' AND tt.taxonomy = 'pa_ac-quy-pin'");
    if ($tt_60) $terms_to_assign[] = ['term_taxonomy_id' => (int)$tt_60];

    foreach ($terms_to_assign as $t) {
        $wpdb->insert($wpdb->term_relationships, [
            'object_id'        => $aie_id,
            'term_taxonomy_id' => $t['term_taxonomy_id'],
            'term_order'       => 0,
        ]);
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = %d
        ", $t['term_taxonomy_id']));
    }

    // 2.6 Lookup table
    $wpdb->replace(
        "{$wpdb->prefix}wc_product_meta_lookup",
        [
            'product_id'     => $aie_id,
            'sku'            => 'AIE-DONE-911',
            'virtual'        => 0,
            'downloadable'   => 0,
            'min_price'      => '15000000',
            'max_price'      => '15500000',
            'onsale'         => 1,
            'stock_quantity' => null,
            'stock_status'   => 'instock',
            'rating_count'   => 0,
            'average_rating' => 0,
            'total_sales'    => 0,
            'tax_status'     => 'taxable',
            'tax_class'      => '',
        ]
    );

    // Variation lookups
    $wpdb->replace("{$wpdb->prefix}wc_product_meta_lookup", [
        'product_id' => $var1_id, 'sku' => 'AIE-DONE-911-48V', 'virtual' => 0, 'downloadable' => 0,
        'min_price' => '15000000', 'max_price' => '15000000', 'onsale' => 1, 'stock_quantity' => null,
        'stock_status' => 'instock', 'rating_count' => 0, 'average_rating' => 0, 'total_sales' => 0,
        'tax_status' => 'taxable', 'tax_class' => 'parent',
    ]);
    $wpdb->replace("{$wpdb->prefix}wc_product_meta_lookup", [
        'product_id' => $var2_id, 'sku' => 'AIE-DONE-911-60V', 'virtual' => 0, 'downloadable' => 0,
        'min_price' => '15500000', 'max_price' => '15500000', 'onsale' => 1, 'stock_quantity' => null,
        'stock_status' => 'instock', 'rating_count' => 0, 'average_rating' => 0, 'total_sales' => 0,
        'tax_status' => 'taxable', 'tax_class' => 'parent',
    ]);
}

// ---------------------------------------------------------
// 3. IMPORT 6 NEW STORE LOCATIONS (local_store)
// ---------------------------------------------------------
echo "\n--- 3. IMPORTING 6 NEW STORE LOCATIONS ---\n";

$new_stores = [
    81931 => 'cua-hang-bluera-viet-nhat-nhan-tam',
    82179 => 'dai-ly-bluera-viet-nhat-quang-minh',
    82370 => 'cua-hang-bluera-viet-nhat-lan-anh',
    82388 => 'dai-ly-bluera-viet-nhat-ut-ngoc',
    82424 => 'cua-hang-bluera-viet-nhat-huy-hoang',
    82578 => 'cua-hang-bluera-viet-nhat-toan-phat',
];

foreach ($new_stores as $bk_store_id => $slug) {
    $exists = $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts} WHERE post_type = 'local_store' AND post_name = %s
    ", $slug));

    if ($exists) {
        echo "Store $slug already exists with ID: $exists\n";
        continue;
    }

    $bk_s = $wpdb->get_row($wpdb->prepare("SELECT * FROM $bk_db.wp_posts WHERE ID = %d", $bk_store_id));
    if (!$bk_s) continue;

    $wpdb->insert($wpdb->posts, [
        'post_author'           => 1,
        'post_date'             => $bk_s->post_date,
        'post_date_gmt'         => $bk_s->post_date_gmt,
        'post_content'          => $bk_s->post_content,
        'post_title'            => $bk_s->post_title,
        'post_excerpt'          => $bk_s->post_excerpt,
        'post_status'           => 'publish',
        'comment_status'        => 'closed',
        'ping_status'           => 'closed',
        'post_name'             => $bk_s->post_name,
        'post_modified'         => $bk_s->post_modified,
        'post_modified_gmt'     => $bk_s->post_modified_gmt,
        'post_parent'           => 0,
        'menu_order'            => (int)$bk_s->menu_order,
        'post_type'             => 'local_store',
        'comment_count'         => 0,
    ]);
    $new_store_id = (int)$wpdb->insert_id;
    echo "✅ Created Store [{$bk_s->post_title}] with ID: $new_store_id\n";

    // Postmeta
    $store_meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value FROM $bk_db.wp_postmeta WHERE post_id = %d
    ", $bk_store_id));

    $bk_thumb_id = 0;
    foreach ($store_meta as $m) {
        if ($m->meta_key === '_thumbnail_id') {
            $bk_thumb_id = (int)$m->meta_value;
            continue;
        }
        $wpdb->insert($wpdb->postmeta, [
            'post_id'    => $new_store_id,
            'meta_key'   => $m->meta_key,
            'meta_value' => $m->meta_value,
        ]);
    }

    if ($bk_thumb_id > 0) {
        $new_thumb_id = sync_media_attachment($bk_thumb_id, $bk_db);
        if ($new_thumb_id) {
            $wpdb->insert($wpdb->postmeta, [
                'post_id'    => $new_store_id,
                'meta_key'   => '_thumbnail_id',
                'meta_value' => (string)$new_thumb_id,
            ]);
            echo "   Attached store thumbnail ID: $new_thumb_id\n";
        }
    }

    // Terms
    $bk_terms = $wpdb->get_results($wpdb->prepare("
        SELECT t.name, t.slug, tt.taxonomy
        FROM $bk_db.wp_term_relationships tr
        JOIN $bk_db.wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN $bk_db.wp_terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %d
    ", $bk_store_id));

    foreach ($bk_terms as $bt) {
        $new_tt_id = $wpdb->get_var($wpdb->prepare("
            SELECT tt.term_taxonomy_id
            FROM {$wpdb->terms} t
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE (t.slug = %s OR t.name = %s) AND tt.taxonomy = %s
        ", $bt->slug, $bt->name, $bt->taxonomy));

        if (!$new_tt_id && $bt->taxonomy === 'local_store_state') {
            // Create term if state doesn't exist
            $inserted_term = wp_insert_term($bt->name, 'local_store_state', ['slug' => $bt->slug]);
            if (!is_wp_error($inserted_term)) {
                $new_tt_id = $inserted_term['term_taxonomy_id'];
            }
        }

        if ($new_tt_id) {
            $wpdb->insert($wpdb->term_relationships, [
                'object_id'        => $new_store_id,
                'term_taxonomy_id' => (int)$new_tt_id,
                'term_order'       => 0,
            ]);
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = %d
            ", $new_tt_id));
        }
    }
}

// ---------------------------------------------------------
// 4. HOMEPAGE SEO METADATA
// ---------------------------------------------------------
echo "\n--- 4. UPDATING HOMEPAGE SEO METADATA (Page 10) ---\n";

$home_page_id = 10;
$home_meta = [
    'rank_math_seo_score'     => '85',
    'rank_math_title'         => 'Đại lý Xe Điện - Xe Đạp Điện - Xe Máy Điện - Xe 3 Bánh',
    'rank_math_description'   => 'Chuyên bán xe đạp điện Bluera, xe đạp trợ lực, xe máy điện, xe điện 3 bánh, xe đạp, phụ tùng và chế xe 3 bánh theo nhu cầu của khách hàng.',
    'rank_math_focus_keyword' => 'xe điện',
    'rank_math_robots'        => serialize(['index']),
];

foreach ($home_meta as $k => $v) {
    $exists = $wpdb->get_var($wpdb->prepare("
        SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s
    ", $home_page_id, $k));

    if ($exists) {
        $wpdb->update($wpdb->postmeta, ['meta_value' => $v], ['meta_id' => $exists]);
    } else {
        $wpdb->insert($wpdb->postmeta, ['post_id' => $home_page_id, 'meta_key' => $k, 'meta_value' => $v]);
    }
}
echo "✅ Homepage SEO metadata configured.\n\n";

// ---------------------------------------------------------
// 5. MERGE GLOBAL RANK MATH OPTIONS
// ---------------------------------------------------------
echo "--- 5. MERGING GLOBAL RANK MATH SETTINGS ---\n";

$options_to_sync = ['rank-math-options-titles', 'rank-math-options-general', 'rank-math-options-sitemap'];

foreach ($options_to_sync as $opt_name) {
    $bk_opt = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM $bk_db.wp_options WHERE option_name = %s", $opt_name));
    $new_opt = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $opt_name));

    if ($bk_opt) {
        $bk_arr = maybe_unserialize($bk_opt);
        $new_arr = maybe_unserialize($new_opt) ?: [];

        if (is_array($bk_arr)) {
            // Merge BK into NEW while preserving local url if needed
            $merged = array_merge($new_arr, $bk_arr);
            // Ensure local site URL is preserved
            if (isset($merged['url'])) {
                $merged['url'] = home_url();
            }
            update_option($opt_name, $merged);
            echo "✅ Merged $opt_name (" . count($merged) . " settings)\n";
        }
    }
}

echo "\n🏁 Product, Store, and Global SEO sync complete!\n";
