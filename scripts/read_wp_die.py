import urllib.request, ssl, re

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

url = 'https://dailynew.bluerabike.com/product/xe-3-banh-che-suzuki/'
try:
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    urllib.request.urlopen(req, context=ctx)
except urllib.error.HTTPError as e:
    body = e.read().decode('utf-8', errors='ignore')
    m = re.search(r'class="wp-die-message">(.*?)</div>', body, re.S)
    if m:
        print('WP DIE MESSAGE:', re.sub(r'<[^>]+>', '', m.group(1)).strip())
    else:
        print('Full Body:', re.sub(r'<[^>]+>', ' ', body).strip()[:1000])
