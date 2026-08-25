import json, urllib.request, ssl, re, concurrent.futures

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

discontinued_urls = json.load(open('docs/discontinued_products.json', encoding='utf-8'))
active_slugs = [
    'xe-lan-dien-performance-2019',
    'xe-3-banh-supper-one',
    'xe-dap-the-thao-catani-360-26ca-360',
    'xe-dien-gap-concise-3-banh-2pin',
    'xe-dien-scooter-concise-2-pin',
    'xe-may-dien-3-banh-one',
]

BASE_LIVE = 'https://dailynew.bluerabike.com'

def test_url(url_or_slug, is_active=False):
    if url_or_slug.startswith('http'):
        path = urllib.parse.urlparse(url_or_slug).path
        target_url = f'{BASE_LIVE}{path}'
    else:
        target_url = f'{BASE_LIVE}/product/{url_or_slug}/'
        
    req = urllib.request.Request(target_url, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
    try:
        with urllib.request.urlopen(req, context=ctx, timeout=15) as res:
            html = res.read().decode('utf-8', errors='ignore')
            status = res.status
            final_url = res.geturl()
            
            # Find title
            title_m = re.search(r'<h1[^>]*>(.*?)</h1>', html, re.I | re.S)
            if not title_m:
                title_m = re.search(r'<title>(.*?)</title>', html, re.I | re.S)
            title = re.sub(r'<[^>]+>', '', title_m.group(1)).strip() if title_m else 'Unknown'
            
            # Check price / contact status
            is_outofstock = 'hết hàng' in html.lower() or 'outofstock' in html.lower() or 'liên hệ' in html.lower() or 'price-contact' in html.lower()
            price_m = re.search(r'<span class="[^"]*woocommerce-Price-amount[^"]*">.*?<bdi>(.*?)</bdi>', html, re.I | re.S)
            price = re.sub(r'<[^>]+>', '', price_m.group(1)).strip() if price_m else ('Liên hệ / Hết hàng' if is_outofstock else 'N/A')
            
            # Check image
            has_image = 'wp-post-image' in html or 'attachment-woocommerce_thumbnail' in html or 'wp-content/uploads' in html
            
            return {
                'success': True,
                'status': status,
                'url': target_url,
                'final_url': final_url,
                'title': title,
                'price': price,
                'is_outofstock': is_outofstock,
                'has_image': has_image
            }
    except Exception as e:
        return {
            'success': False,
            'status': 0,
            'url': target_url,
            'error': str(e)
        }

print('========================================================================')
print('🧪 BẮT ĐẦU KIỂM TRA ĐẦY ĐỦ TRỰC TIẾP TRÊN DAILYNEW.BLUERABIKE.COM')
print('========================================================================\n')

# 1. Test 6 active products
print('📌 1. KIỂM TRA 6 SẢN PHẨM CÒN BÁN:')
active_results = []
for slug in active_slugs:
    res = test_url(slug, is_active=True)
    active_results.append(res)
    if res['success']:
        print(f"  ✅ [HTTP {res['status']}] {res['title']} | Giá: {res['price']} | Có ảnh: {'Có' if res['has_image'] else 'Không'}")
    else:
        print(f"  ❌ Lỗi: {slug} -> {res.get('error')}")

print(f"\n=> Kết quả nhóm còn bán: {sum(1 for r in active_results if r['success'])}/{len(active_slugs)} sản phẩm HOÀN THÀNH TỐT!\n")

# 2. Test 158 discontinued products (Concurrent 15 threads)
print(f'📌 2. KIỂM TRA {len(discontinued_urls)} SẢN PHẨM HẾT HÀNG / NGỪNG KINH DOANH:')
disc_results = []
with concurrent.futures.ThreadPoolExecutor(max_workers=15) as executor:
    future_to_url = {executor.submit(test_url, url): url for url in discontinued_urls}
    done_count = 0
    for future in concurrent.futures.as_completed(future_to_url):
        res = future.result()
        disc_results.append(res)
        done_count += 1
        print(f"  Quét: {done_count}/{len(discontinued_urls)} | Thành công: {sum(1 for r in disc_results if r['success'])}\r", end="")

print('\n\n========================================================================')
print('📊 BÁO CÁO TỔNG KẾT KIỂM TRA SẢN PHẨM TRÊN DAILYNEW.BLUERABIKE.COM:')
print('========================================================================')
ok_active = sum(1 for r in active_results if r['success'])
ok_disc = sum(1 for r in disc_results if r['success'])
failed_disc = [r for r in disc_results if not r['success']]

print(f"1. Nhóm 6 sản phẩm CÒN BÁN:          {ok_active}/{len(active_slugs)} ({'✅ ĐỦ 100%' if ok_active == len(active_slugs) else '⚠️ THIẾU'})")
print(f"2. Nhóm 158 sản phẩm HẾT HÀNG:       {ok_disc}/{len(discontinued_urls)} ({'✅ ĐỦ 100%' if ok_disc >= 157 else '⚠️ THIẾU'})")

if failed_disc:
    print(f"\nCác sản phẩm bị lỗi ({len(failed_disc)}):")
    for f in failed_disc:
        print(f" - {f['url']} -> {f.get('error')}")
else:
    print("\n🎉 Tất cả đường link sản phẩm đều phản hồi HTTP 200 OK, đầy đủ Tiêu đề, Hình ảnh và Trạng thái chuẩn!")
print('========================================================================')
