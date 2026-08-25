import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

const audits = [
  'is-crawlable',
  'button-name',
  'color-contrast',
  'heading-order',
  'label-content-name-mismatch',
  'link-name',
  'target-size',
  'unsized-images',
  'render-blocking-insight'
];

for (const id of audits) {
  const a = d.audits[id];
  console.log(`\n================== [${id}] ==================`);
  console.log('Title:', a?.title, '| Score:', a?.score);
  if (a?.displayValue) console.log('DisplayValue:', a.displayValue);
  if (a?.explanation) console.log('Explanation:', a.explanation);
  if (a?.errorMessage) console.log('ErrorMessage:', a.errorMessage);
  if (a?.details?.items) {
    console.log(`Items count: ${a.details.items.length}`);
    for (let i = 0; i < a.details.items.length; i++) {
      const item = a.details.items[i];
      console.log(`-- Item ${i + 1}:`);
      if (item.node) {
        console.log(`   Selector: ${item.node.selector}`);
        console.log(`   Snippet: ${item.node.snippet}`);
        console.log(`   NodeLabel: ${item.node.nodeLabel}`);
        console.log(`   Explanation: ${item.node.explanation}`);
      } else if (item.url) {
        console.log(`   URL: ${item.url} (wastedMs: ${item.wastedMs}, totalBytes: ${item.totalBytes})`);
      } else {
        console.log(`   Raw:`, JSON.stringify(item));
      }
      if (item.subItems) {
        console.log(`   SubItems:`, JSON.stringify(item.subItems));
      }
    }
  }
}
