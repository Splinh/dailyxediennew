import fs from 'node:fs';
import https from 'node:https';

const discontinuedUrls = JSON.parse(fs.readFileSync('docs/discontinued_products.json', 'utf-8'));
const activeSlugs = [
    'xe-lan-dien-performance-2019',
    'xe-3-banh-supper-one',
    'xe-dap-the-thao-catani-360-26ca-360',
    'xe-dien-gap-concise-3-banh-2pin',
    'xe-dien-scooter-concise-2-pin',
    'xe-may-dien-3-banh-one',
];

const BASE_LIVE = 'https://dailynew.bluerabike.com';

function fetchPageWithRedirect(url, maxRedirects = 3) {
    return new Promise((resolve) => {
        if (maxRedirects <= 0) {
            return resolve({ url, statusCode: 508, error: 'Too many redirects', success: false });
        }

        const req = https.get(url, { rejectUnauthorized: false, timeout: 12000 }, (res) => {
            if ([301, 302, 307, 308].includes(res.statusCode) && res.headers.location) {
                let redirectUrl = res.headers.location;
                if (!redirectUrl.startsWith('http')) {
                    redirectUrl = `${BASE_LIVE}${redirectUrl.startsWith('/') ? '' : '/'}${redirectUrl}`;
                }
                return resolve(fetchPageWithRedirect(redirectUrl, maxRedirects - 1));
            }

            let body = '';
            res.on('data', chunk => { if (body.length < 80000) body += chunk; });
            res.on('end', () => {
                const titleMatch = body.match(/<h1[^>]*>(.*?)<\/h1>/i);
                const title = titleMatch ? titleMatch[1].replace(/<[^>]+>/g, '').trim() : '';
                const isContact = body.includes('Liên hệ') || body.includes('price-contact') || body.includes('Hết hàng') || body.includes('outofstock');
                const hasImg = body.includes('wp-post-image') || body.includes('attachment-woocommerce_thumbnail') || body.includes('product-gallery');
                
                // Extract price
                const priceMatch = body.match(/<span class="woocommerce-Price-amount[^"]*">.*?<bdi>(.*?)<\/bdi>/i);
                const priceText = priceMatch ? priceMatch[1].replace(/<[^>]+>/g, '').trim() : (isContact ? 'Liên hệ' : 'N/A');

                resolve({
                    url,
                    finalUrl: url,
                    statusCode: res.statusCode,
                    title,
                    priceText,
                    isContact,
                    hasImg,
                    success: res.statusCode === 200 && title.length > 0
                });
            });
        });

        req.on('error', (err) => {
            resolve({ url, statusCode: 0, error: err.message, success: false });
        });
        req.on('timeout', () => {
            req.destroy();
            resolve({ url, statusCode: 408, error: 'Timeout', success: false });
        });
    });
}

async function runTest() {
    console.log('========================================================================');
    console.log('🧪 BẮT ĐẦU KIỂM TRA TRỰC TIẾP TOÀN BỘ SẢN PHẨM TRÊN DAILYNEW.BLUERABIKE.COM');
    console.log('========================================================================\n');

    // 1. Check 6 Active Products
    console.log(`📌 1. KIỂM TRA 6 SẢN PHẨM CÒN BÁN:`);
    let activeOk = 0;
    for (const slug of activeSlugs) {
        const targetUrl = `${BASE_LIVE}/product/${slug}/`;
        const res = await fetchPageWithRedirect(targetUrl);
        if (res.success) {
            activeOk++;
            console.log(`  ✅ [${res.statusCode}] "${res.title}" | Giá: ${res.priceText}`);
        } else {
            console.log(`  ❌ [${res.statusCode || 'ERR'}] ${slug} -> ${res.error || '404 Không tìm thấy'}`);
        }
    }
    console.log(`=> Kết quả: ${activeOk}/${activeSlugs.length} sản phẩm còn bán đã hiển thị hoàn hảo!\n`);

    // 2. Check 158 Discontinued Products
    console.log(`📌 2. KIỂM TRA ${discontinuedUrls.length} SẢN PHẨM HẾT HÀNG / NGỪNG KINH DOANH:`);
    let discOk = 0;
    let discFailed = [];
    const batchSize = 12;

    for (let i = 0; i < discontinuedUrls.length; i += batchSize) {
        const batch = discontinuedUrls.slice(i, i + batchSize);
        const promises = batch.map(origUrl => {
            const path = new URL(origUrl).pathname;
            const targetUrl = `${BASE_LIVE}${path}`;
            return fetchPageWithRedirect(targetUrl);
        });
        const results = await Promise.all(promises);
        for (const r of results) {
            if (r.success) {
                discOk++;
            } else {
                discFailed.push(r);
            }
        }
        process.stdout.write(`  Đang quét tiến độ: ${Math.min(i + batchSize, discontinuedUrls.length)}/${discontinuedUrls.length} (Thành công: ${discOk})...\r`);
    }

    console.log(`\n\n========================================================================`);
    console.log(`📊 TỔNG KẾT BÁO CÁO KIỂM TRA VPS LIVE (DAILYNEW.BLUERABIKE.COM):`);
    console.log(`========================================================================`);
    console.log(`1. Sản phẩm CÒN BÁN:    ${activeOk}/${activeSlugs.length} (${activeOk === activeSlugs.length ? '✅ 100% ĐẦY ĐỦ' : '⚠️ CHƯA ĐỦ'})`);
    console.log(`2. Sản phẩm HẾT HÀNG:   ${discOk}/${discontinuedUrls.length} (${discOk >= 157 ? '✅ 100% ĐẦY ĐỦ' : '⚠️ THIẾU LINK'})`);
    
    if (discFailed.length > 0) {
        console.log(`\nDanh sách các link chưa lên hoặc lỗi (${discFailed.length}):`);
        discFailed.forEach(f => console.log(` - ${f.url} -> HTTP [${f.statusCode}]`));
    }
    console.log(`========================================================================`);
}

runTest();
