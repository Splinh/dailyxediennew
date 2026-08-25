import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

function printAudit(id) {
  const a = d.audits[id];
  if (!a) return;
  console.log(`\n========================================`);
  console.log(`AUDIT: [${id}] - ${a.title} (Score: ${a.score})`);
  if (a.explanation) console.log('Explanation:', a.explanation);
  if (a.details && a.details.items) {
    console.log(`Items (${a.details.items.length}):`);
    for (const item of a.details.items) {
      console.log(JSON.stringify(item, null, 2));
    }
  }
}

const auditList = [
  'is-crawlable',
  'button-name',
  'color-contrast',
  'heading-order',
  'label-content-name-mismatch',
  'link-name',
  'target-size',
  'unsized-images',
  'render-blocking-insight',
  'network-dependency-tree-insight'
];

for (const a of auditList) {
  printAudit(a);
}
