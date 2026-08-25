<?php
declare(strict_types=1);

require_once __DIR__ . '/../wp/wp-load.php';

global $wpdb;

echo "=========================================================\n";
echo "🔍 KIỂM TRA VÀ SỬA POSTMETA RANK MATH SCHEMA\n";
echo "=========================================================\n";

$slugs = ['xe-3-banh-che-suzuki', 'xe-3-banh-che-vision-trang', 'xe-dap-dien-asama-ebk'];

foreach ($slugs as $slug) {
    $p = get_page_by_path($slug, OBJECT, 'product');
    if (!$p) continue;
    
    echo "\nSản phẩm: $slug (ID: {$p->ID})\n";
    $metas = $wpdb->get_results($wpdb->prepare(
        "SELECT meta_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
        $p->ID,
        'rank_math_schema_%'
    ), ARRAY_A);
    
    foreach ($metas as $m) {
        echo "  - {$m['meta_key']}: " . substr($m['meta_value'], 0, 100) . "\n";
        $val = maybe_unserialize($m['meta_value']);
        if (!is_array($val) || empty($val['@type'])) {
            echo "    ⚠️ Schema không hợp lệ -> Xóa meta_id: {$m['meta_id']}\n";
            delete_post_meta_by_mid((int)$m['meta_id']);
        }
    }
}

// Xóa mọi schema rỗng hoặc bị double-serialized lỗi trên toàn bộ database
$all_schemas = $wpdb->get_results("SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE 'rank_math_schema_%'", ARRAY_A);
$deleted = 0;
$fixed = 0;

foreach ($all_schemas as $row) {
    $val = maybe_unserialize($row['meta_value']);
    // Kiểm tra nếu bị double serialized (val là string)
    if (is_string($val) && is_serialized($val)) {
        $val = maybe_unserialize($val);
        if (is_array($val) && !empty($val['@type'])) {
            $wpdb->update(
                $wpdb->postmeta,
                ['meta_value' => maybe_serialize($val)],
                ['meta_id' => $row['meta_id']]
            );
            $fixed++;
            continue;
        }
    }

    // Nếu vẫn không phải array hợp lệ có @type thì xóa bỏ để Rank Math tự tạo schema chuẩn
    if (!is_array($val) || empty($val['@type'])) {
        delete_post_meta_by_mid((int)$row['meta_id']);
        $deleted++;
    }
}

echo "\n✅ Đã sửa $fixed schema bị double-serialized và xóa $deleted schema Rank Math bị hỏng.\n";
