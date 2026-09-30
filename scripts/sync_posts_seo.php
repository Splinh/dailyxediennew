<?php
/**
 * Synchronize Posts & Rank Math SEO from daily_seo_bk to dailynew.
 */
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

global $wpdb;

$bk_db = 'daily_seo_bk';

echo "====================================================\n";
echo "🚀 SYNCING POSTS & POST SEO FROM $bk_db TO dailynew\n";
echo "====================================================\n\n";

function sync_attachment_from_bk(int $bk_thumb_id, string $bk_db): ?int {
    global $wpdb;

    // Check if attachment exists in BK
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

    // Check if this file already exists in new media library
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

    // If file doesn't exist locally, download from live site
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

    // Create attachment post in new db
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

// 1. UPDATE MATCHED POSTS
echo "--- 1. UPDATING MATCHED POSTS ---\n";

$bk_posts = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified, post_modified_gmt, post_content, post_excerpt, post_date, post_date_gmt
    FROM $bk_db.wp_posts
    WHERE post_type = 'post' AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')
", OBJECT_K);

$new_posts_by_slug = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified
    FROM {$wpdb->posts}
    WHERE post_type = 'post' AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')
", OBJECT_K);

$slug_to_new = [];
foreach ($new_posts_by_slug as $np) {
    if (!empty($np->post_name)) {
        $slug_to_new[$np->post_name] = $np;
    }
}

$updated_posts_count = 0;
$updated_seo_count = 0;

foreach ($bk_posts as $bp) {
    $slug = $bp->post_name;
    if (empty($slug) || !isset($slug_to_new[$slug])) {
        continue;
    }

    $np = $slug_to_new[$slug];
    $new_id = (int)$np->ID;

    // Update post fields
    $wpdb->update(
        $wpdb->posts,
        [
            'post_title'        => $bp->post_title,
            'post_content'      => $bp->post_content,
            'post_excerpt'      => $bp->post_excerpt,
            'post_modified'     => $bp->post_modified,
            'post_modified_gmt' => $bp->post_modified_gmt,
        ],
        ['ID' => $new_id],
        ['%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );
    $updated_posts_count++;

    // Sync Rank Math SEO postmeta
    $bk_meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value FROM $bk_db.wp_postmeta 
        WHERE post_id = %d AND meta_key LIKE 'rank_math_%'
    ", $bp->ID));

    if (!empty($bk_meta)) {
        // Delete old rank_math meta
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE 'rank_math_%'
        ", $new_id));

        // Insert new rank_math meta
        foreach ($bk_meta as $m) {
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'    => $new_id,
                    'meta_key'   => $m->meta_key,
                    'meta_value' => $m->meta_value,
                ],
                ['%d', '%s', '%s']
            );
        }
        $updated_seo_count++;
    }
}

echo "✅ Updated content & dates on $updated_posts_count matched posts.\n";
echo "✅ Synced Rank Math SEO metadata on $updated_seo_count matched posts.\n\n";

// 2. INSERT MISSING PUBLISHED POSTS
echo "--- 2. INSERTING NEW / MISSING PUBLISHED POSTS ---\n";

$missing_posts = $wpdb->get_results("
    SELECT p.*
    FROM $bk_db.wp_posts p
    WHERE p.post_type = 'post' 
      AND p.post_status = 'publish'
      AND p.post_name != ''
      AND p.post_name NOT IN (
          SELECT post_name FROM {$wpdb->posts} WHERE post_type = 'post' AND post_name != ''
      )
    ORDER BY p.post_date ASC
");

echo "Found " . count($missing_posts) . " missing published posts to import.\n";

foreach ($missing_posts as $mp) {
    echo "Importing: [{$mp->post_date}] {$mp->post_name} - " . mb_substr($mp->post_title, 0, 40) . "\n";

    // 1. Insert post into w_posts
    $post_data = [
        'post_author'           => 1,
        'post_date'             => $mp->post_date,
        'post_date_gmt'         => $mp->post_date_gmt,
        'post_content'          => $mp->post_content,
        'post_title'            => $mp->post_title,
        'post_excerpt'          => $mp->post_excerpt,
        'post_status'           => 'publish',
        'comment_status'        => $mp->comment_status ?: 'open',
        'ping_status'           => $mp->ping_status ?: 'closed',
        'post_name'             => $mp->post_name,
        'post_modified'         => $mp->post_modified,
        'post_modified_gmt'     => $mp->post_modified_gmt,
        'post_parent'           => 0,
        'menu_order'            => (int)$mp->menu_order,
        'post_type'             => 'post',
        'comment_count'         => 0,
    ];

    $res = $wpdb->insert($wpdb->posts, $post_data);
    $new_post_id = (int)$wpdb->insert_id;

    if ($res === false || $new_post_id <= 0) {
        echo "   ❌ Failed to insert post {$mp->post_name}: " . $wpdb->last_error . "\n";
        continue;
    }

    echo "   Created new post ID: $new_post_id\n";

    // 2. Sync all postmeta
    $all_bk_meta = $wpdb->get_results($wpdb->prepare("
        SELECT meta_key, meta_value FROM $bk_db.wp_postmeta WHERE post_id = %d
    ", $mp->ID));

    $bk_thumb_id = 0;
    foreach ($all_bk_meta as $m) {
        if ($m->meta_key === '_thumbnail_id') {
            $bk_thumb_id = (int)$m->meta_value;
            continue; // Handle thumbnail separately
        }
        $wpdb->insert(
            $wpdb->postmeta,
            [
                'post_id'    => $new_post_id,
                'meta_key'   => $m->meta_key,
                'meta_value' => $m->meta_value,
            ],
            ['%d', '%s', '%s']
        );
    }

    // 3. Handle thumbnail attachment
    if ($bk_thumb_id > 0) {
        $new_thumb_id = sync_attachment_from_bk($bk_thumb_id, $bk_db);
        if ($new_thumb_id) {
            $wpdb->insert(
                $wpdb->postmeta,
                [
                    'post_id'    => $new_post_id,
                    'meta_key'   => '_thumbnail_id',
                    'meta_value' => (string)$new_thumb_id,
                ],
                ['%d', '%s', '%s']
            );
            echo "   Attached thumbnail ID: $new_thumb_id\n";
        }
    }

    // 4. Assign categories & tags
    $bk_terms = $wpdb->get_results($wpdb->prepare("
        SELECT t.slug, tt.taxonomy
        FROM $bk_db.wp_term_relationships tr
        JOIN $bk_db.wp_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN $bk_db.wp_terms t ON tt.term_id = t.term_id
        WHERE tr.object_id = %d
    ", $mp->ID));

    foreach ($bk_terms as $bt) {
        // Find matching term in new db
        $new_tt_id = $wpdb->get_var($wpdb->prepare("
            SELECT tt.term_taxonomy_id
            FROM {$wpdb->terms} t
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE t.slug = %s AND tt.taxonomy = %s
        ", $bt->slug, $bt->taxonomy));

        if ($new_tt_id) {
            $wpdb->insert(
                $wpdb->term_relationships,
                [
                    'object_id'        => $new_post_id,
                    'term_taxonomy_id' => (int)$new_tt_id,
                    'term_order'       => 0,
                ],
                ['%d', '%d', '%d']
            );
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = %d
            ", $new_tt_id));
        }
    }
}

echo "\n🏁 Posts and Post SEO sync complete!\n";
