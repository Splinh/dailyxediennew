import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

console.log('=== LCP ELEMENT ===');
console.log(JSON.stringify(d.audits['largest-contentful-paint-element']?.details, null, 2));

console.log('\n=== RENDER BLOCKING ===');
console.log(JSON.stringify(d.audits['render-blocking-resources']?.details, null, 2));

console.log('\n=== CRITICAL REQUEST CHAINS ===');
console.log(JSON.stringify(d.audits['critical-request-chains']?.details, null, 2));

console.log('\n=== DOM SIZE ===');
console.log(JSON.stringify(d.audits['dom-size'], null, 2));

console.log('\n=== MAIN THREAD WORK ===');
console.log(JSON.stringify(d.audits['mainthread-work-breakdown']?.details, null, 2));

console.log('\n=== BOOTUP TIME ===');
console.log(JSON.stringify(d.audits['bootup-time']?.details, null, 2));
