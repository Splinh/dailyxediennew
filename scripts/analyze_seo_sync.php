<?php
/**
 * Fast bulk analysis of posts & products between daily_seo_bk and dailynew.
 */
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$bk_db = 'daily_seo_bk';
$new_db = 'dailynew';

echo "====================================================\n";
echo "📊 FAST SEO & CONTENT SYNC ANALYSIS: $bk_db vs $new_db\n";
echo "====================================================\n\n";

// 1. POSTS OVERVIEW
echo "--- 1. POSTS ANALYSIS ---\n";

$bk_posts = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified, post_date,
           LENGTH(post_content) as content_len, LENGTH(post_excerpt) as excerpt_len,
           MD5(post_content) as content_hash, MD5(post_title) as title_hash
    FROM $bk_db.wp_posts
    WHERE post_type = 'post' AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')
", OBJECT_K);

$new_posts = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified, post_date,
           LENGTH(post_content) as content_len, LENGTH(post_excerpt) as excerpt_len,
           MD5(post_content) as content_hash, MD5(post_title) as title_hash
    FROM $new_db.w_posts
    WHERE post_type = 'post' AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')
", OBJECT_K);

echo "Total active posts in BK: " . count($bk_posts) . "\n";
echo "Total active posts in NEW: " . count($new_posts) . "\n";

$new_posts_by_slug = [];
foreach ($new_posts as $np) {
    if (!empty($np->post_name)) {
        $new_posts_by_slug[$np->post_name] = $np;
    }
}

$missing_posts = [];
$matched_posts = [];
$content_diff_posts = [];

foreach ($bk_posts as $bp) {
    $slug = $bp->post_name;
    if (empty($slug)) continue;

    if (!isset($new_posts_by_slug[$slug])) {
        $missing_posts[] = $bp;
    } else {
        $np = $new_posts_by_slug[$slug];
        $matched_posts[$slug] = [
            'bk_id' => (int)$bp->ID,
            'new_id' => (int)$np->ID,
            'bk' => $bp,
            'new' => $np,
        ];

        $title_diff = ($bp->title_hash !== $np->title_hash);
        $content_diff = ($bp->content_hash !== $np->content_hash);
        $status_diff = ($bp->post_status !== $np->post_status);

        if ($title_diff || $content_diff || $status_diff) {
            $content_diff_posts[] = [
                'slug' => $slug,
                'bk_id' => $bp->ID,
                'new_id' => $np->ID,
                'title_diff' => $title_diff,
                'content_diff' => $content_diff,
                'status_diff' => $status_diff,
                'bk_modified' => $bp->post_modified,
                'new_modified' => $np->post_modified,
                'bk_title' => $bp->post_title,
                'new_title' => $np->post_title,
            ];
        }
    }
}

echo "Matched posts by slug: " . count($matched_posts) . "\n";
echo "Missing posts in NEW (present in BK): " . count($missing_posts) . "\n";
echo "Posts with Title/Content/Status differences: " . count($content_diff_posts) . "\n";

// Sort content diff posts by bk_modified DESC
usort($content_diff_posts, function($a, $b) {
    return strcmp($b['bk_modified'], $a['bk_modified']);
});

echo "\nTop 10 most recently modified posts with Content/Title differences:\n";
for ($i = 0; $i < min(10, count($content_diff_posts)); $i++) {
    $d = $content_diff_posts[$i];
    echo sprintf(
        "[%s] BK_ID=%d, NEW_ID=%d | Slug: %s\n   Diff: Title=%s, Content=%s, Status=%s\n   BK Title: %s\n   NEW Title: %s\n",
        $d['bk_modified'],
        $d['bk_id'],
        $d['new_id'],
        $d['slug'],
        $d['title_diff'] ? 'YES' : 'no',
        $d['content_diff'] ? 'YES' : 'no',
        $d['status_diff'] ? 'YES' : 'no',
        mb_substr($d['bk_title'], 0, 50),
        mb_substr($d['new_title'], 0, 50)
    );
}

// 2. CHECK SEO METADATA FOR POSTS
echo "\n--- Checking SEO Metadata Differences for Posts ---\n";
// Load all rank_math meta from BK for all matched posts
$bk_ids = array_column($matched_posts, 'bk_id');
$new_ids = array_column($matched_posts, 'new_id');

$id_map_bk_to_new = [];
foreach ($matched_posts as $m) {
    $id_map_bk_to_new[$m['bk_id']] = $m['new_id'];
}

// Query rank math meta in bulk
$bk_rm_meta = $wpdb->get_results("
    SELECT post_id, meta_key, meta_value
    FROM $bk_db.wp_postmeta
    WHERE meta_key LIKE 'rank_math_%'
      AND post_id IN (" . implode(',', $bk_ids) . ")
", OBJECT);

$bk_meta_by_post = [];
foreach ($bk_rm_meta as $row) {
    $bk_meta_by_post[$row->post_id][$row->meta_key] = $row->meta_value;
}

$new_rm_meta = $wpdb->get_results("
    SELECT post_id, meta_key, meta_value
    FROM $new_db.w_postmeta
    WHERE meta_key LIKE 'rank_math_%'
      AND post_id IN (" . implode(',', $new_ids) . ")
", OBJECT);

$new_meta_by_post = [];
foreach ($new_rm_meta as $row) {
    $new_meta_by_post[$row->post_id][$row->meta_key] = $row->meta_value;
}

$posts_with_seo_diff = [];
foreach ($matched_posts as $slug => $m) {
    $bk_id = $m['bk_id'];
    $new_id = $m['new_id'];

    $bkm = $bk_meta_by_post[$bk_id] ?? [];
    $newm = $new_meta_by_post[$new_id] ?? [];

    $diffs = [];
    foreach ($bkm as $k => $v) {
        $nv = $newm[$k] ?? null;
        if ($v !== $nv) {
            $diffs[$k] = ['bk' => $v, 'new' => $nv];
        }
    }
    // Also check keys present in new but not in bk?
    if (!empty($diffs)) {
        $posts_with_seo_diff[] = [
            'slug' => $slug,
            'bk_id' => $bk_id,
            'new_id' => $new_id,
            'diffs' => $diffs,
            'bk_modified' => $m['bk']->post_modified,
        ];
    }
}

echo "Posts with Rank Math SEO differences: " . count($posts_with_seo_diff) . "\n";
usort($posts_with_seo_diff, function($a, $b) {
    return strcmp($b['bk_modified'], $a['bk_modified']);
});

echo "Sample 5 posts with SEO diffs:\n";
for ($i = 0; $i < min(5, count($posts_with_seo_diff)); $i++) {
    $s = $posts_with_seo_diff[$i];
    echo sprintf("[%s] Slug: %s (BK_ID=%d, NEW_ID=%d) Diff keys: %s\n",
        $s['bk_modified'],
        $s['slug'],
        $s['bk_id'],
        $s['new_id'],
        implode(', ', array_keys($s['diffs']))
    );
    foreach ($s['diffs'] as $k => $v) {
        if (in_array($k, ['rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'])) {
            echo "   $k:\n      BK : " . mb_substr((string)$v['bk'], 0, 70) . "\n      NEW: " . mb_substr((string)$v['new'], 0, 70) . "\n";
        }
    }
}

// 3. PRODUCTS OVERVIEW
echo "\n--- 3. PRODUCTS ANALYSIS ---\n";

$bk_prods = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified, post_date,
           LENGTH(post_content) as content_len, LENGTH(post_excerpt) as excerpt_len,
           MD5(post_content) as content_hash, MD5(post_title) as title_hash
    FROM $bk_db.wp_posts
    WHERE post_type = 'product' AND post_status IN ('publish', 'draft', 'pending', 'private')
", OBJECT_K);

$new_prods = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_status, post_modified, post_date,
           LENGTH(post_content) as content_len, LENGTH(post_excerpt) as excerpt_len,
           MD5(post_content) as content_hash, MD5(post_title) as title_hash
    FROM $new_db.w_posts
    WHERE post_type = 'product' AND post_status IN ('publish', 'draft', 'pending', 'private')
", OBJECT_K);

echo "Total active products in BK: " . count($bk_prods) . "\n";
echo "Total active products in NEW: " . count($new_prods) . "\n";

$new_prods_by_slug = [];
foreach ($new_prods as $np) {
    if (!empty($np->post_name)) {
        $new_prods_by_slug[$np->post_name] = $np;
    }
}

$missing_prods = [];
$matched_prods = [];
$content_diff_prods = [];

foreach ($bk_prods as $bp) {
    $slug = $bp->post_name;
    if (empty($slug)) continue;

    if (!isset($new_prods_by_slug[$slug])) {
        $missing_prods[] = $bp;
    } else {
        $np = $new_prods_by_slug[$slug];
        $matched_prods[$slug] = [
            'bk_id' => (int)$bp->ID,
            'new_id' => (int)$np->ID,
            'bk' => $bp,
            'new' => $np,
        ];

        $title_diff = ($bp->title_hash !== $np->title_hash);
        $content_diff = ($bp->content_hash !== $np->content_hash);
        $status_diff = ($bp->post_status !== $np->post_status);

        if ($title_diff || $content_diff || $status_diff) {
            $content_diff_prods[] = [
                'slug' => $slug,
                'bk_id' => $bp->ID,
                'new_id' => $np->ID,
                'title_diff' => $title_diff,
                'content_diff' => $content_diff,
                'status_diff' => $status_diff,
                'bk_modified' => $bp->post_modified,
                'new_modified' => $np->post_modified,
                'bk_title' => $bp->post_title,
                'new_title' => $np->post_title,
            ];
        }
    }
}

echo "Matched products by slug: " . count($matched_prods) . "\n";
echo "Products in BK but not in NEW: " . count($missing_prods) . "\n";
echo "Products with Title/Content/Status differences: " . count($content_diff_prods) . "\n";

// Let's check products in BK that have been modified recently (e.g. in 2026 or since July 2026)
$recent_bk_prods = [];
foreach ($bk_prods as $bp) {
    if ($bp->post_modified >= '2026-07-01') {
        $recent_bk_prods[] = $bp;
    }
}
echo "Products in BK modified since 2026-07-01: " . count($recent_bk_prods) . "\n";

// Check which of these recent products are matched vs missing
$recent_matched = 0;
$recent_missing = 0;
foreach ($recent_bk_prods as $bp) {
    if (isset($new_prods_by_slug[$bp->post_name])) {
        $recent_matched++;
    } else {
        $recent_missing++;
        echo sprintf("   Recent missing product: [%s] ID=%d | Slug: %s | Title: %s\n",
            $bp->post_modified, $bp->ID, $bp->post_name, mb_substr($bp->post_title, 0, 40)
        );
    }
}
echo "Recent BK products: Matched = $recent_matched, Missing in NEW = $recent_missing\n";

// Also check SEO for matched products
if (!empty($matched_prods)) {
    $bk_pids = array_column($matched_prods, 'bk_id');
    $new_pids = array_column($matched_prods, 'new_id');

    $bk_p_meta = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value FROM $bk_db.wp_postmeta
        WHERE meta_key LIKE 'rank_math_%' AND post_id IN (" . implode(',', $bk_pids) . ")
    ", OBJECT);
    $bk_pm_by_post = [];
    foreach ($bk_p_meta as $r) {
        $bk_pm_by_post[$r->post_id][$r->meta_key] = $r->meta_value;
    }

    $new_p_meta = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value FROM $new_db.w_postmeta
        WHERE meta_key LIKE 'rank_math_%' AND post_id IN (" . implode(',', $new_pids) . ")
    ", OBJECT);
    $new_pm_by_post = [];
    foreach ($new_p_meta as $r) {
        $new_pm_by_post[$r->post_id][$r->meta_key] = $r->meta_value;
    }

    $prods_with_seo_diff = [];
    foreach ($matched_prods as $slug => $m) {
        $bkm = $bk_pm_by_post[$m['bk_id']] ?? [];
        $newm = $new_pm_by_post[$m['new_id']] ?? [];

        $diffs = [];
        foreach ($bkm as $k => $v) {
            $nv = $newm[$k] ?? null;
            if ($v !== $nv) {
                $diffs[$k] = ['bk' => $v, 'new' => $nv];
            }
        }
        if (!empty($diffs)) {
            $prods_with_seo_diff[] = [
                'slug' => $slug,
                'bk_id' => $m['bk_id'],
                'new_id' => $m['new_id'],
                'diffs' => $diffs,
                'bk_modified' => $m['bk']->post_modified,
            ];
        }
    }
    echo "Products with Rank Math SEO differences: " . count($prods_with_seo_diff) . "\n";
    usort($prods_with_seo_diff, function($a, $b) {
        return strcmp($b['bk_modified'], $a['bk_modified']);
    });
    for ($i = 0; $i < min(5, count($prods_with_seo_diff)); $i++) {
        $s = $prods_with_seo_diff[$i];
        echo sprintf("[%s] Slug: %s (BK_ID=%d, NEW_ID=%d) Diff keys: %s\n",
            $s['bk_modified'],
            $s['slug'],
            $s['bk_id'],
            $s['new_id'],
            implode(', ', array_keys($s['diffs']))
        );
        foreach ($s['diffs'] as $k => $v) {
            if (in_array($k, ['rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'])) {
                echo "   $k:\n      BK : " . mb_substr((string)$v['bk'], 0, 70) . "\n      NEW: " . mb_substr((string)$v['new'], 0, 70) . "\n";
            }
        }
    }
}
