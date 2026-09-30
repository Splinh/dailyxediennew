<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

$bk = unserialize($wpdb->get_var("SELECT option_value FROM daily_seo_bk.wp_options WHERE option_name = 'rank-math-options-titles'"));
$new = unserialize($wpdb->get_var("SELECT option_value FROM dailynew.w_options WHERE option_name = 'rank-math-options-titles'"));

echo "Keys in BK: " . count($bk) . ", Keys in NEW: " . count($new) . "\n";
foreach ($bk as $k => $v) {
    if (!isset($new[$k]) || $new[$k] !== $v) {
        if (is_scalar($v)) {
            $nv = $new[$k] ?? 'NOT_SET';
            echo sprintf("Diff on %-30s | BK: %-40s | NEW: %s\n",
                $k,
                substr((string)$v, 0, 40),
                substr((string)$nv, 0, 40)
            );
        }
    }
}
