<?php
$progress = json_decode(file_get_contents(__DIR__ . '/../docs/crawl_progress.json'), true);
echo "=== THỐNG KÊ CHI TIẾT ===\n";
$success = 0;
$skipped = 0;
$failed = 0;
$failed_items = [];

foreach ($progress as $url => $data) {
    if ($data['status'] === 'success') {
        if (isset($data['specs_count'])) {
            $success++;
        } else {
            $skipped++;
        }
    } else {
        $failed++;
        $failed_items[] = ['url' => $url, 'error' => $data['error'] ?? ''];
    }
}

echo "Thành công cào mới: $success\n";
echo "Đã có sẵn trên web (bỏ qua): $skipped\n";
echo "Lỗi khi cào: $failed\n\n";

if (!empty($failed_items)) {
    echo "Các link bị lỗi (thường là 404 trên dailyxedien.vn):\n";
    foreach ($failed_items as $item) {
        echo "- {$item['url']}: {$item['error']}\n";
    }
}
