import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));
const items = d.audits['color-contrast']?.details?.items || [];

for (let i = 0; i < items.length; i++) {
  const item = items[i];
  console.log(`\n--- [${i+1}] ---`);
  console.log('Snippet:', item.node?.snippet);
  console.log('Selector:', item.node?.selector);
  console.log('NodeLabel:', item.node?.nodeLabel);
  console.log('Explanation:', item.node?.explanation?.replace(/\n/g, ' '));
}
