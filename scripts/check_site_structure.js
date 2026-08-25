import https from 'node:https';

function fetchText(url) {
    return new Promise(resolve => {
        https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, res => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => resolve({ status: res.statusCode, data }));
        }).on('error', () => resolve({ status: 'ERROR', data: '' }));
    });
}

(async () => {
    // 1. Get categories from sitemap
    const catSitemap = await fetchText('https://dailynew.bluerabike.com/category-sitemap.xml');
    const catLocs = Array.from(catSitemap.data.matchAll(/<loc>(.*?)<\/loc>/g)).map(m => m[1]);
    console.log(`=== CATEGORY SITEMAP (${catLocs.length} URLs) ===`);
    console.log(catLocs.slice(0, 10));

    // 2. Check header/footer key links
    const keyUrls = [
        'https://dailynew.bluerabike.com/su-menh/',
        'https://dailynew.bluerabike.com/tam-nhin-su-menh/',
        'https://dailynew.bluerabike.com/chinh-sach-bao-hanh/',
        'https://dailynew.bluerabike.com/chinh-sach-doi-tra/',
        'https://dailynew.bluerabike.com/chinh-sach-giao-hang/',
        'https://dailynew.bluerabike.com/chinh-sach-bao-mat/',
        'https://dailynew.bluerabike.com/huong-dan-mua-hang/',
        'https://dailynew.bluerabike.com/huong-dan-tra-gop/'
    ];

    console.log('=== KEY POLICY / HEADER LINKS ===');
    for (const u of keyUrls) {
        const res = await fetchText(u);
        console.log(`[${res.status}] ${u}`);
    }

    // 3. Check stores
    const storesPage = await fetchText('https://dailynew.bluerabike.com/he-thong-cua-hang/');
    console.log(`=== STORES PAGE === [${storesPage.status}] length: ${storesPage.data.length}`);
    const storeLinks = Array.from(storesPage.data.matchAll(/href="(https:\/\/dailynew\.bluerabike\.com\/(?:local_store|cua-hang|he-thong-cua-hang)\/[^"]+)"/g)).map(m => m[1]);
    console.log(`Found ${storeLinks.length} store links:`, storeLinks.slice(0, 5));
})();
