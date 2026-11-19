// Run in the console of a tab on https://jidmis.org/index.php/jidmis (after the bot check has cleared) with tools/receiver.py running.
// Saves every article page listed in the issue pages to reference/articles/{id}.html, one request per second.
(async () => {
  const B = 'https://jidmis.org/index.php/jidmis', sleep = ms => new Promise(r => setTimeout(r, ms));
  const send = (path, body) => fetch('http://127.0.0.1:8765', { method: 'POST', body: JSON.stringify({ path, body }) });
  const ids = new Set();
  // issue ids come from the archive pages
  for (const p of ['/issue/archive', '/issue/archive/2']) {
    const t = await (await fetch(B + p)).text(); await sleep(1100);
    for (const m of t.matchAll(/issue\/view\/(\d+)/g)) ids.add(m[1]);
  }
  const articles = new Set();
  for (const i of ids) {
    const t = await (await fetch(B + '/issue/view/' + i)).text(); await send('reference/issues/' + i + '.html', t); await sleep(1100);
    for (const m of t.matchAll(/article\/view\/(\d+)"/g)) articles.add(m[1]);
  }
  for (const id of articles) {
    const t = await (await fetch(B + '/article/view/' + id)).text();
    if (!/One moment, please/.test(t)) await send('reference/articles/' + id + '.html', t);
    await sleep(1100);
  }
  console.log('done', articles.size);
})();
