import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

console.log('=== OVERALL SCORES ===');
for (const [k, v] of Object.entries(d.categories)) {
  console.log(`${k}: ${v.score !== null ? Math.round(v.score * 100) : 'NULL'}`);
}

console.log('\n=== DETAILED FAILED AUDITS (Score < 1) ===');
for (const [id, a] of Object.entries(d.audits)) {
  if (a.score !== null && a.score < 1 && a.scoreDisplayMode !== 'notApplicable' && a.scoreDisplayMode !== 'informative') {
    console.log(`\n-----------------------------------------`);
    console.log(`[${id}] (${Math.round((a.score || 0) * 100)}%) - ${a.title}`);
    if (a.displayValue) console.log(`   Value: ${a.displayValue}`);
    if (a.explanation) console.log(`   Explanation: ${a.explanation}`);
    if (a.errorMessage) console.log(`   ErrorMessage: ${a.errorMessage}`);
    if (a.details && a.details.items && a.details.items.length > 0) {
      console.log(`   Items count: ${a.details.items.length}`);
      console.log(`   Items details:`, JSON.stringify(a.details.items.slice(0, 5), null, 2));
    }
  }
}
