import https from 'node:https';

function fetchText(url) {
    return new Promise(resolve => {
        https.get(url, (res) => {
            let data = '';
            res.on('data', chunk => { data += chunk; });
            res.on('end', () => resolve(data));
        }).on('error', () => resolve(''));
    });
}

(async () => {
    const sitemap = await fetchText('https://dailynew.bluerabike.com/product-sitemap1.xml');
    const locs = Array.from(sitemap.matchAll(/<loc>(.*?)<\/loc>/g)).map(m => m[1]);
    console.log(`Found ${locs.length} product URLs in sitemap1:`);
    console.log(locs.slice(0, 10));

    if (locs.length > 0) {
        const testUrl = locs[0];
        https.get(testUrl, res => {
            console.log(`Testing product URL: ${testUrl} -> [${res.statusCode}]`);
        });
    }
})();
