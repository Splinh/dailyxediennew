import https from 'node:https';

const footerFallbackUrls = [
    'https://dailynew.bluerabike.com/chinh-sach-bao-hanh/',
    'https://dailynew.bluerabike.com/chinh-sach-doi-tra-hang/',
    'https://dailynew.bluerabike.com/giao-hang-va-lap-dat/',
    'https://dailynew.bluerabike.com/phuong-thuc-thanh-toan/',
    'https://dailynew.bluerabike.com/bao-mat-thong-tin-khach-hang/',
    'https://dailynew.bluerabike.com/huong-dan-mua-hang/',
    'https://dailynew.bluerabike.com/chinh-sach-ban-hang-dailyxedien-vn/',
    'https://dailynew.bluerabike.com/he-thong-cua-hang/',
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

console.log('=== TESTING FOOTER EXACT SLUGS ===');
for (const u of footerFallbackUrls) {
    const res = await fetchUrl(u);
    const redirect = res.location ? ' -> ' + res.location : '';
    console.log(`[${res.status}] ${res.url}${redirect}`);
}
