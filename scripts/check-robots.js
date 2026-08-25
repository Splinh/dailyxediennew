import fs from 'fs';

async function checkRobots() {
  const res = await fetch('https://dailynew.bluerabike.com/robots.txt');
  const text = await res.text();
  console.log('--- ROBOTS.TXT ---');
  console.log(text);
}

checkRobots();
