import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

console.log('=== METRICS ===');
const metrics = [
  'first-contentful-paint',
  'speed-index',
  'largest-contentful-paint',
  'total-blocking-time',
  'cumulative-layout-shift',
  'interaction-to-next-paint'
];
for (const m of metrics) {
  const a = d.audits[m];
  if (a) console.log(`${m}: ${a.displayValue} (score: ${a.score})`);
}

console.log('\n=== OPPORTUNITIES & DIAGNOSTICS ===');
for (const [id, a] of Object.entries(d.audits)) {
  if (a.details && (a.details.type === 'opportunity' || a.details.overallSavingsMs > 0 || a.details.overallSavingsBytes > 0)) {
    console.log(`\n[${id}] ${a.title} -> ${a.displayValue || ''}`);
    if (a.explanation) console.log(`   Explanation: ${a.explanation}`);
    if (a.details.items) {
      console.log(`   Items (${a.details.items.length}):`, a.details.items.slice(0, 3));
    }
  }
}
