import puppeteer from 'puppeteer';
import { fileURLToPath } from 'url';
import path from 'path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const htmlPath = path.join(__dirname, 'index.html');
const outputPath = path.join(__dirname, 'fullpage-screenshot.png');

const browser = await puppeteer.launch({ headless: 'new' });
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900 });
await page.goto(`file://${htmlPath}`, { waitUntil: 'networkidle0', timeout: 15000 });

// Wait for animations / reveals
await page.evaluate(() => {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
});
await new Promise(r => setTimeout(r, 1000));

await page.screenshot({ path: outputPath, fullPage: true });
console.log('Screenshot saved to:', outputPath);
await browser.close();
