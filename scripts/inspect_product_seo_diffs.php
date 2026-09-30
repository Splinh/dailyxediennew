<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$prods = $wpdb->get_results("
    SELECT b.ID as bk_id, n.ID as new_id, b.post_name, b.post_title, b.post_modified as bk_mod, n.post_modified as new_mod
    FROM daily_seo_bk.wp_posts b
    JOIN dailynew.w_posts n ON b.post_name = n.post_name
    WHERE b.post_type = 'product' AND n.post_type = 'product'
");

$diff_keys = [];
$sample_diffs = [];

foreach ($prods as $p) {
    $bm = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM daily_seo_bk.wp_postmeta WHERE post_id = %d AND meta_key LIKE 'rank_math_%'", $p->bk_id), OBJECT_K);
    $nm = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM dailynew.w_postmeta WHERE post_id = %d AND meta_key LIKE 'rank_math_%'", $p->new_id), OBJECT_K);

    foreach ($bm as $k => $r) {
        $nv = isset($nm[$k]) ? $nm[$k]->meta_value : null;
        if ($r->meta_value !== $nv) {
            $diff_keys[$k] = ($diff_keys[$k] ?? 0) + 1;
            if (count($sample_diffs[$k] ?? []) < 3) {
                $sample_diffs[$k][] = [
                    'slug' => $p->post_name,
                    'title' => $p->post_title,
                    'bk' => $r->meta_value,
                    'new' => $nv,
                ];
            }
        }
    }
}

echo "Total matched products: " . count($prods) . "\n";
echo "Rank Math meta difference count by key across matched products:\n";
print_r($diff_keys);

echo "\n--- Samples of differences ---\n";
foreach ($sample_diffs as $k => $samples) {
    echo "Key: $k\n";
    foreach ($samples as $s) {
        echo "  [{$s['slug']}]\n    BK : {$s['bk']}\n    NEW: {$s['new']}\n";
    }
}
