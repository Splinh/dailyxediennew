import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));
const items = d.audits['color-contrast']?.details?.items || [];

console.log('Total color contrast failures:', items.length);
for (let i = 0; i < items.length; i++) {
  const item = items[i];
  console.log(`\n[${i+1}] Node: ${item.node?.snippet}`);
  console.log(`    Selector: ${item.node?.selector}`);
  console.log(`    Explanation: ${item.node?.explanation?.replace(/\n/g, ' ')}`);
}
