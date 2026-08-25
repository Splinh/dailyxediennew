<?php
$json_file = __DIR__ . '/../docs/discontinued_products.json';
$progress_file = __DIR__ . '/../docs/crawl_progress.json';

$urls = json_decode(file_get_contents($json_file), true);
$progress = json_decode(file_get_contents($progress_file), true);

// Sửa lại 3 URL chính xác
$replacements = [
    'https://dailyxedien.vn/san-pham/xe-dap-dien-bluera-cap-super-max-2020/' => 'https://dailyxedien.vn/san-pham/xe-dap-dien-bluera-cap-super-max-2025/',
    'https://dailyxedien.vn/san-pham/xe-may-dien-anbico-new-zoomer-ap1509/' => 'https://dailyxedien.vn/san-pham/xe-may-dien-anbico-new-zoomer-ap1607/',
    'https://dailyxedien.vn/san-pham/xe-may-dien-v5-2019/' => 'https://dailyxedien.vn/san-pham/xe-may-dien-anbico-v5-2019/'
];

foreach ($replacements as $old => $new) {
    $idx = array_search($old, $urls);
    if ($idx !== false) {
        $urls[$idx] = $new;
    }
    unset($progress[$old]);
}

file_put_contents($json_file, json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
file_put_contents($progress_file, json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "Updated URLs and reset progress for fixed items.\n";
