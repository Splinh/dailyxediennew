import https from 'node:https';

const urls = [
    'https://dailynew.bluerabike.com/chinh-sach-bao-hanh/',
    'https://dailynew.bluerabike.com/chinh-sach-doi-tra-hang/',
    'https://dailynew.bluerabike.com/giao-hang-va-lap-dat/',
    'https://dailynew.bluerabike.com/phuong-thuc-thanh-toan/',
    'https://dailynew.bluerabike.com/bao-mat-thong-tin-khach-hang/',
    'https://dailynew.bluerabike.com/huong-dan-mua-hang/',
    'https://dailynew.bluerabike.com/chinh-sach-ban-hang-dailyxedien-vn/'
];

function checkPage(url) {
    return new Promise(resolve => {
        https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, res => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => {
                const titleMatch = data.match(/<h1[^>]*>(.*?)<\/h1>/is);
                const title = titleMatch ? titleMatch[1].replace(/<[^>]+>/g, '').trim() : 'No H1';
                resolve({
                    url,
                    status: res.statusCode,
                    h1: title,
                    contentLength: data.length
                });
            });
        }).on('error', err => resolve({ url, status: 'ERR', h1: err.message, contentLength: 0 }));
    });
}

console.log('=== VERIFYING FOOTER PAGES CONTENT & H1 ===');
for (const u of urls) {
    const res = await checkPage(u);
    console.log(`[${res.status}] ${res.url}`);
    console.log(`     H1: "${res.h1}" | Size: ${(res.contentLength / 1024).toFixed(1)} KB`);
}
