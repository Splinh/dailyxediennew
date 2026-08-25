import fs from 'fs';

const data = JSON.parse(fs.readFileSync('lh_report.json', 'utf8'));

console.log('=== LIGHTHOUSE CATEGORIES ===');
for (const [key, cat] of Object.entries(data.categories || {})) {
  console.log(`${cat.title} (${key}): ${cat.score !== null ? Math.round(cat.score * 100) : 'ERROR / NULL'}`);
}

console.log('\n=== RUNTIME ERROR ===');
console.log(data.runtimeError || 'None');

console.log('\n=== AUDITS BREAKDOWN BY CATEGORY ===');

for (const [catKey, cat] of Object.entries(data.categories || {})) {
  console.log(`\n--- CATEGORY: ${cat.title} (${catKey}) ---`);
  for (const auditRef of cat.auditRefs || []) {
    const audit = data.audits[auditRef.id];
    if (!audit) continue;
    if (audit.score !== null && audit.score < 1 && audit.scoreDisplayMode !== 'notApplicable' && audit.scoreDisplayMode !== 'informative') {
      console.log(`[FAILED ${Math.round((audit.score || 0) * 100)}] ${audit.id}: ${audit.title} (weight: ${auditRef.weight})`);
      if (audit.displayValue) console.log(`   Value: ${audit.displayValue}`);
      if (audit.explanation) console.log(`   Explanation: ${audit.explanation}`);
      if (audit.errorMessage) console.log(`   Error: ${audit.errorMessage}`);
      if (audit.details && audit.details.items && audit.details.items.length > 0) {
        console.log(`   Items (${audit.details.items.length}):`, JSON.stringify(audit.details.items.slice(0, 3), null, 2));
      }
    } else if (audit.scoreDisplayMode === 'error') {
      console.log(`[ERROR] ${audit.id}: ${audit.title} -> ${audit.errorMessage || audit.explanation}`);
    }
  }
}
