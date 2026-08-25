import json, urllib.request, re, ssl

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

missing = json.load(open('docs/missing_products.json', encoding='utf-8'))
print(f'Checking {len(missing)} missing products on dailyxedien.vn...')

details = []
for item in missing:
    url = item['url']
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, context=ctx, timeout=10) as resp:
            html = resp.read().decode('utf-8', errors='ignore')
            title_m = re.search(r'<h1[^>]*>(.*?)</h1>', html, re.I)
            title = title_m.group(1).strip() if title_m else 'Unknown'
            # price check
            price_m = re.search(r'<span class="woocommerce-Price-amount amount">(.*?)</span>', html)
            price = price_m.group(1).strip() if price_m else 'Liên hệ'
            # check out of stock
            is_outofstock = 'hết hàng' in html.lower() or 'out-of-stock' in html.lower() or 'ngừng kinh doanh' in html.lower()
            details.append({
                'url': url,
                'slug': item['slug'],
                'title': re.sub(r'<[^<]+?>', '', title).strip(),
                'price': re.sub(r'<[^<]+?>', '', price).strip(),
                'is_outofstock': is_outofstock
            })
            print(f'-> {item["slug"]}: {title} | Price: {price} | OutOfStock: {is_outofstock}')
    except Exception as e:
        print(f'-> Error {url}: {e}')
        details.append({'url': url, 'slug': item['slug'], 'error': str(e)})

with open('docs/missing_products_detailed.json', 'w', encoding='utf-8') as f:
    json.dump(details, f, ensure_ascii=False, indent=2)

print('Done inspecting missing products!')
