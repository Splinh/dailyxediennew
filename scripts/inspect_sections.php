<?php
$html = file_get_contents('https://dailyxedien.vn/san-pham/xe-dap-dien-osakar-a9/');
// decode gzip
$html = gzdecode($html);

// 1. Description snippet
preg_match('/<div[^>]+id="accordion-description-content"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $desc);
if (!empty($desc[1])) {
    echo "--- Description (first 500 chars) ---\n" . substr(strip_tags($desc[1]), 0, 500) . "\n";
} else {
    echo "Description pattern 1 not matched, trying other...\n";
    preg_match('/id="accordion-description-content".*?>(.*?)<\/div>/is', $html, $desc2);
    echo "Desc 2 len: " . strlen($desc2[1] ?? '') . "\n";
}

// 2. TSKT snippet
preg_match('/<div[^>]+id="tskt-spec-block"[^>]*>(.*?)<\/div>\s*<\/div>/is', $html, $tskt);
if (!empty($tskt[0])) {
    echo "--- TSKT Block ---\n" . substr($tskt[0], 0, 1000) . "\n";
} else {
    preg_match('/class="[^"]*tskt-body__table-wrap[^"]*"[^>]*>(.*?)<\/div>/is', $html, $tskt2);
    echo "TSKT 2 len: " . strlen($tskt2[0] ?? '') . "\n";
    echo substr($tskt2[0] ?? '', 0, 800) . "\n";
}

// 3. Gallery snippet
preg_match_all('/<div[^>]+class="[^"]*woocommerce-product-gallery__image[^"]*"[^>]*>(.*?)<\/div>/is', $html, $gal);
echo "Gallery items: " . count($gal[0]) . "\n";
foreach ($gal[0] as $g) {
    preg_match('/(?:href|src|data-large_image|data-src)="([^"]+)"/i', $g, $src);
    echo "Img: " . ($src[1] ?? '') . "\n";
}
