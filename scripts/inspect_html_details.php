<?php
require_once __DIR__ . '/../wp/wp-load.php';

$test_url = 'https://dailyxedien.vn/san-pham/xe-dap-dien-osakar-a9/';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$html = curl_exec($ch);
curl_close($ch);

// Search for tabs or description or content classes
preg_match_all('/class="([^"]*(?:tab|content|desc|thong-so|spec|gallery|product)[^"]*)"/i', $html, $matches);
echo "Unique matched classes:\n";
print_r(array_unique(array_slice($matches[1], 0, 50)));

// Let's search for table in HTML
preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $html, $tables);
echo "Tables found: " . count($tables[0]) . "\n";
foreach ($tables[0] as $i => $tbl) {
    echo "--- Table $i (first 300 chars) ---\n" . substr($tbl, 0, 300) . "\n";
}

// Search for entry-content or similar
preg_match_all('/<div[^>]+id="([^"]*)"[^>]*>/i', $html, $ids);
echo "IDs found:\n";
print_r(array_filter($ids[1], fn($id) => preg_match('/tab|content|desc|info|spec|gal/i', $id)));

// Search for images in product area
preg_match_all('/<div[^>]+class="[^"]*product-gallery[^"]*"[^>]*>(.*?)<\/div>/is', $html, $gal);
if (!empty($gal[0])) {
    echo "Product gallery found!\n";
}
