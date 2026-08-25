import fs from 'fs';

async function run() {
  console.log('Fetching PSI for https://dailynew.bluerabike.com/ ...');
  const url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://dailynew.bluerabike.com/&strategy=mobile&category=PERFORMANCE&category=ACCESSIBILITY&category=BEST_PRACTICES&category=SEO';
  
  try {
    const res = await fetch(url);
    const data = await res.json();
    
    if (data.error) {
      console.error('PSI Error:', JSON.stringify(data.error, null, 2));
      return;
    }
    
    const lighthouse = data.lighthouseResult;
    if (!lighthouse) {
      console.log('No lighthouse result:', data);
      return;
    }

    console.log('Lighthouse version:', lighthouse.lighthouseVersion);
    console.log('Runtime error:', lighthouse.runtimeError);
    
    console.log('--- CATEGORIES ---');
    for (const [catKey, cat] of Object.entries(lighthouse.categories || {})) {
      console.log(`${cat.title} (${catKey}): ${cat.score !== null ? Math.round(cat.score * 100) : 'ERROR / NULL'}`);
    }

    console.log('\n--- FAILED AUDITS (Score < 1 or Error) ---');
    for (const [auditKey, audit] of Object.entries(lighthouse.audits || {})) {
      if (audit.score !== null && audit.score < 0.9 && audit.scoreDisplayMode !== 'notApplicable' && audit.scoreDisplayMode !== 'informative') {
        console.log(`[${audit.scoreDisplayMode}] ${audit.id} (${audit.score}): ${audit.title} -> ${audit.displayValue || ''}`);
        if (audit.explanation) console.log(`   Explanation: ${audit.explanation}`);
        if (audit.errorMessage) console.log(`   Error: ${audit.errorMessage}`);
      } else if (audit.scoreDisplayMode === 'error') {
        console.log(`[ERROR] ${audit.id}: ${audit.title} -> ${audit.errorMessage || audit.explanation}`);
      }
    }

    fs.writeFileSync('psi_summary.json', JSON.stringify({
      runtimeError: lighthouse.runtimeError,
      categories: lighthouse.categories,
      audits: Object.fromEntries(
        Object.entries(lighthouse.audits || {}).filter(([k, v]) => v.score !== null && v.score < 1)
      )
    }, null, 2));
    console.log('Saved to psi_summary.json');
  } catch (err) {
    console.error('Error fetching:', err);
  }
}

run();
