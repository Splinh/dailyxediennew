import fs from 'fs';

async function fetchHome() {
  const res = await fetch('https://dailynew.bluerabike.com/', {
    headers: {
      'User-Agent': 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.36'
    }
  });
  const html = await res.text();
  fs.writeFileSync('home_mobile.html', html);
  console.log('Saved home_mobile.html, size:', html.length);
}

fetchHome();
