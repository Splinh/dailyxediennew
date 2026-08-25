import https from 'node:https';

const urls = [
    'https://dailynew.bluerabike.com/hop-tac/',
    'https://dailynew.bluerabike.com/co-hoi-hop-tac/',
    'https://dailynew.bluerabike.com/gioi-thieu/',
    'https://dailynew.bluerabike.com/ve-chung-toi/',
    'https://dailynew.bluerabike.com/san-pham/',
    'https://dailynew.bluerabike.com/shop/',
    'https://dailynew.bluerabike.com/cua-hang/',
    'https://dailynew.bluerabike.com/product/xe-dap-dien-bluera-cap-a-x/',
    'https://dailynew.bluerabike.com/product/xe-may-dien-bluera-camry/',
    'https://dailynew.bluerabike.com/product-category/xe-dap-dien/',
    'https://dailynew.bluerabike.com/product-category/xe-may-dien/',
    'https://dailynew.bluerabike.com/product-category/xe-dien-3-banh/',
    'https://dailynew.bluerabike.com/he-thong-cua-hang/',
    'https://dailynew.bluerabike.com/tin-tuc/',
    'https://dailynew.bluerabike.com/lien-he/'
];

function fetchUrl(url) {
    return new Promise(resolve => {
        const req = https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, (res) => {
            resolve({ url, status: res.statusCode, location: res.headers.location });
        });
        req.on('error', err => resolve({ url, status: 'ERROR', error: err.message }));
        req.setTimeout(8000, () => {
            req.destroy();
            resolve({ url, status: 'TIMEOUT' });
        });
    });
}

for (const u of urls) {
    const res = await fetchUrl(u);
    const redirect = res.location ? ' -> ' + res.location : '';
    console.log(`[${res.status}] ${res.url}${redirect}`);
}
