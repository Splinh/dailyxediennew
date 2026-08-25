import https from 'node:https';

function testPost(url, body) {
    return new Promise(resolve => {
        const data = JSON.stringify(body);
        const req = https.request(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(data),
                'User-Agent': 'Mozilla/5.0'
            }
        }, (res) => {
            let resData = '';
            res.on('data', chunk => { resData += chunk; });
            res.on('end', () => {
                resolve({ status: res.statusCode, data: resData.slice(0, 200) });
            });
        });
        req.on('error', err => resolve({ status: 'ERROR', error: err.message }));
        req.write(data);
        req.end();
    });
}

(async () => {
    const res = await testPost('https://dailynew.bluerabike.com/wp-json/spl/v1/wc-filter/products', {
        filters: {},
        page: 1,
        per_page: 12
    });
    console.log('Filter POST response:', res.status, res.data);
})();
