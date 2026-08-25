import https from 'node:https';

const urls = [
    'https://dailynew.bluerabike.com/',
    'https://dailynew.bluerabike.com/product-category/xe-dap-dien/',
    'https://dailynew.bluerabike.com/cua-hang/',
    'https://dailynew.bluerabike.com/ve-chung-toi/',
    'https://dailynew.bluerabike.com/gioi-thieu/',
    'https://dailynew.bluerabike.com/lien-he/',
    'https://dailynew.bluerabike.com/co-hoi-hop-tac/',
    'https://dailynew.bluerabike.com/he-thong-cua-hang/',
    'https://dailynew.bluerabike.com/tin-tuc/',
    'https://dailynew.bluerabike.com/basket/',
    'https://dailynew.bluerabike.com/checkout/',
    'https://dailynew.bluerabike.com/wp-json/spl/v1/search?s=xe',
    'https://dailynew.bluerabike.com/wp-json/spl/v1/wc-filter/products?cat=xe-dap-dien',
    'https://dailynew.bluerabike.com/robots.txt',
    'https://dailynew.bluerabike.com/sitemap_index.xml'
];

function fetchUrl(url) {
    return new Promise(resolve => {
        const req = https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' } }, (res) => {
            let data = '';
            res.on('data', chunk => { if (data.length < 2000) data += chunk; });
            res.on('end', () => {
                resolve({ url, status: res.statusCode, location: res.headers.location, dataLength: data.length, bodyPreview: data.slice(0, 100) });
            });
        });
        req.on('error', err => {
            resolve({ url, status: 'ERROR', error: err.message });
        });
        req.setTimeout(8000, () => {
            req.destroy();
            resolve({ url, status: 'TIMEOUT' });
        });
    });
}

console.log('=== CHECKING LIVE DAILYNIEW.BLUERABIKE.COM ===');
for (const u of urls) {
    const res = await fetchUrl(u);
    const redirect = res.location ? ' -> ' + res.location : '';
    console.log(`[${res.status}] ${res.url}${redirect}`);
}
