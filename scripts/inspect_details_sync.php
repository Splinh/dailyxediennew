<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$bk_db = 'daily_seo_bk';
$new_db = 'dailynew';

echo "====================================================\n";
echo "🔍 DEEP DIVE: MISSING POSTS & PRODUCT MODIFICATIONS\n";
echo "====================================================\n\n";

// 1. DETAIL OF 27 MISSING POSTS
echo "--- 1. THE MISSING POSTS IN NEW ---\n";
$missing_posts = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_date, p.post_modified
    FROM $bk_db.wp_posts p
    WHERE p.post_type = 'post' 
      AND p.post_status IN ('publish', 'draft', 'pending', 'future', 'private')
      AND p.post_name != ''
      AND p.post_name NOT IN (
          SELECT post_name FROM $new_db.w_posts WHERE post_type = 'post' AND post_name != ''
      )
    ORDER BY p.post_modified DESC
");

echo "Count of missing posts with slug: " . count($missing_posts) . "\n";
foreach ($missing_posts as $mp) {
    echo sprintf(
        "ID=%d | Modified: %s | Status: %s | Slug: %s\n   Title: %s\n",
        $mp->ID,
        $mp->post_modified,
        $mp->post_status,
        $mp->post_name,
        $mp->post_title
    );
}

// 2. PRODUCT MODIFICATIONS IN BK
echo "\n--- 2. PRODUCTS IN BK MODIFIED IN 2026 ---\n";
$bk_prods_2026 = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_name, p.post_status, p.post_modified,
           (SELECT meta_value FROM $bk_db.wp_postmeta WHERE post_id = p.ID AND meta_key = '_stock_status') as stock,
           (SELECT meta_value FROM $bk_db.wp_postmeta WHERE post_id = p.ID AND meta_key = '_price') as price
    FROM $bk_db.wp_posts p
    WHERE p.post_type = 'product' 
      AND p.post_status IN ('publish', 'draft', 'private')
      AND p.post_modified >= '2026-01-01'
    ORDER BY p.post_modified DESC
");
echo "Total products in BK modified in 2026: " . count($bk_prods_2026) . "\n";
foreach ($bk_prods_2026 as $bp) {
    $exists_in_new = $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM $new_db.w_posts WHERE post_type = 'product' AND post_name = %s
    ", $bp->post_name));
    echo sprintf(
        "ID=%d | Mod: %s | Stock: %-10s | Price: %-10s | In NEW: %s | Slug: %s | Title: %s\n",
        $bp->ID,
        $bp->post_modified,
        $bp->stock ?? 'N/A',
        $bp->price ?? 'N/A',
        $exists_in_new ? "YES (ID=$exists_in_new)" : "NO (MISSING)",
        $bp->post_name,
        mb_substr($bp->post_title, 0, 35)
    );
}

// 3. CHECK THE 900+ PRODUCTS IN BK NOT IN NEW: WHAT DATES ARE THEY?
echo "\n--- 3. DISTRIBUTION OF PRODUCTS IN BK NOT IN NEW BY YEAR/MODIFIED DATE ---\n";
$missing_prods_distribution = $wpdb->get_results("
    SELECT YEAR(p.post_modified) as yr, p.post_status, count(*) as cnt,
           SUM(CASE WHEN pm.meta_value = 'outofstock' THEN 1 ELSE 0 END) as out_of_stock,
           SUM(CASE WHEN pm.meta_value = 'instock' THEN 1 ELSE 0 END) as in_stock
    FROM $bk_db.wp_posts p
    LEFT JOIN $bk_db.wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_stock_status'
    WHERE p.post_type = 'product'
      AND p.post_status IN ('publish', 'draft', 'private')
      AND p.post_name NOT IN (
          SELECT post_name FROM $new_db.w_posts WHERE post_type = 'product' AND post_name != ''
      )
    GROUP BY yr, p.post_status
    ORDER BY yr DESC, p.post_status
");
foreach ($missing_prods_distribution as $row) {
    echo sprintf("Year: %s | Status: %-10s | Total: %4d | InStock: %4d | OutOfStock: %4d\n",
        $row->yr, $row->post_status, $row->cnt, $row->in_stock, $row->out_of_stock
    );
}

// 4. CHECK IF SEO TEAM TOUCHED RANK MATH ON PRODUCTS IN BK
echo "\n--- 4. PRODUCTS IN BK WITH RANK MATH META MODIFIED OR CONFIGURED ---\n";
$prods_with_rm = $wpdb->get_results("
    SELECT p.ID, p.post_name, p.post_title, p.post_modified,
           m_title.meta_value as rm_title,
           m_desc.meta_value as rm_desc,
           m_kw.meta_value as rm_kw
    FROM $bk_db.wp_posts p
    INNER JOIN $bk_db.wp_postmeta m_title ON p.ID = m_title.post_id AND m_title.meta_key = 'rank_math_title' AND m_title.meta_value != ''
    LEFT JOIN $bk_db.wp_postmeta m_desc ON p.ID = m_desc.post_id AND m_desc.meta_key = 'rank_math_description'
    LEFT JOIN $bk_db.wp_postmeta m_kw ON p.ID = m_kw.post_id AND m_kw.meta_key = 'rank_math_focus_keyword'
    WHERE p.post_type = 'product' AND p.post_status = 'publish'
    ORDER BY p.post_modified DESC
    LIMIT 20
");
echo "Top 20 products with custom rank_math_title in BK:\n";
foreach ($prods_with_rm as $pr) {
    $exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $new_db.w_posts WHERE post_type = 'product' AND post_name = %s", $pr->post_name));
    echo sprintf(
        "Mod: %s | BK_ID=%d | In NEW: %s | Slug: %s\n   Title: %s\n   RM Title: %s\n   RM Desc: %s\n   RM KW: %s\n",
        $pr->post_modified,
        $pr->ID,
        $exists ? "YES ($exists)" : "NO",
        $pr->post_name,
        mb_substr($pr->post_title, 0, 40),
        mb_substr($pr->rm_title ?? '', 0, 50),
        mb_substr($pr->rm_desc ?? '', 0, 50),
        $pr->rm_kw ?? ''
    );
}
