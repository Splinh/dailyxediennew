<?php
require_once __DIR__ . '/../wp/wp-load.php';

$progress = json_decode(file_get_contents(__DIR__ . '/../docs/crawl_progress.json'), true);
$total = count($progress);
$found_in_wp = 0;
$published = 0;
$outofstock = 0;
$with_thumb = 0;

foreach ($progress as $url => $item) {
    if (!empty($item['product_id'])) {
        $p = wc_get_product($item['product_id']);
        if ($p) {
            $found_in_wp++;
            if ($p->get_status() === 'publish') {
                $published++;
            }
            if ($p->get_stock_status() === 'outofstock') {
                $outofstock++;
            }
            if ($p->get_image_id() > 0) {
                $with_thumb++;
            }
        }
    }
}

echo "========================================\n";
echo "📊 BÁO CÁO KIỂM TRA SẢN PHẨM TRÊN DAILYNEW\n";
echo "========================================\n";
echo "Tổng số URL trong danh sách: $total\n";
echo "Sản phẩm tồn tại trong WooCommerce: $found_in_wp / $total\n";
echo "Trạng thái Publish (giữ SEO traffic): $published / $total\n";
echo "Trạng thái Hết hàng (outofstock): $outofstock / $total\n";
echo "Có hình ảnh đại diện: $with_thumb / $total\n";
echo "========================================\n";
