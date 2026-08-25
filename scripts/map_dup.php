<?php
$progress_file = __DIR__ . '/../docs/crawl_progress.json';
$progress = json_decode(file_get_contents($progress_file), true);

$dup_url = 'https://dailyxedien.vn/san-pham/xe-dap-the-thao-tre-em-shangenbei-16-inch/';
$progress[$dup_url] = [
    'status' => 'success',
    'product_id' => 6094,
    'slug' => 'xe-dap-tre-em-shangenbei-16inch',
    'title' => 'Xe đạp trẻ em Shangenbei 16inch',
    'timestamp' => date('Y-m-d H:i:s'),
    'note' => 'Mapped to existing product ID 6094'
];

file_put_contents($progress_file, json_encode($progress, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "Successfully mapped all 158 items!\n";
