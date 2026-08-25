import https from 'node:https';

function fetchFull(url) {
    return new Promise(resolve => {
        https.get(url, { headers: { 'User-Agent': 'Mozilla/5.0' } }, res => {
            let body = '';
            res.on('data', chunk => { body += chunk; });
            res.on('end', () => resolve({ status: res.statusCode, body }));
        }).on('error', err => resolve({ status: 'ERROR', body: '' }));
    });
}

(async () => {
    const url = 'https://dailynew.bluerabike.com/product/xe-dap-dien-bluera-s6/';
    const res = await fetchFull(url);
    console.log(`Single Product [${res.status}] ${url}`);
    console.log(`Page size: ${(res.body.length / 1024).toFixed(1)} KB`);

    const checks = {
        'Has H1 title': /<h1[^>]*>.*?<\/h1>/is.test(res.body),
        'Has Price': /woocommerce-Price-amount/is.test(res.body),
        'Has Technical Specs (TSKT)': /tskt|thông số kỹ thuật|tskt_rows/i.test(res.body),
        'Has Loan Calculator': /loan|trả góp|tính trả góp/i.test(res.body),
        'Has Related Products': /sản phẩm tương tự|related products|data-fx-slider/i.test(res.body),
        'Has Review Form': /comment-form|đánh giá/i.test(res.body),
        'Has Buy Now / Add to cart': /thêm vào giỏ|mua ngay|add-to-cart/i.test(res.body),
        'Has Schema Product JSON-LD': /"@type"\s*:\s*"Product"/i.test(res.body),
        'Has GA4 / FB Pixel': /gtag|fbevents|fbq/i.test(res.body)
    };

    console.log('Feature checks on single product page:');
    console.table(checks);
})();
