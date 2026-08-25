import fs from 'fs';

const d = JSON.parse(fs.readFileSync('lh_report.json'));

for (const [id, a] of Object.entries(d.audits)) {
  if (a.score !== null && a.score < 1 && a.scoreDisplayMode !== 'notApplicable' && a.scoreDisplayMode !== 'informative') {
    console.log(`- [${id}] (Score: ${a.score}): ${a.title}`);
    if (a.explanation) console.log(`    Explanation: ${a.explanation}`);
    if (a.errorMessage) console.log(`    ErrorMessage: ${a.errorMessage}`);
  }
}
