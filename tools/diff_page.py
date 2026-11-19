#!/usr/bin/env python3
"""Compare our rendered page with the saved original, tag by tag.
    python tools/diff_page.py <reference file name without .html> <path under /index.php/jidmis> [max_lines]
e.g. python tools/diff_page.py about /about        (needs the app on http://localhost:8081)"""
import sys, re, difflib, urllib.request, os, html as H
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
BASE = 'http://localhost:8081/index.php/jidmis'

def norm(h):
    h = h[h.find('<body'):]
    h = re.sub(r'<script.*?</script>', '', h, flags=re.S)
    h = re.sub(r'<!--.*?-->', '', h, flags=re.S)
    h = h.replace('https://jidmis.org/index.php/jidmis', 'J').replace('/index.php/jidmis', 'J').replace('https://jidmis.org', '')
    h = re.sub(r'\?v=[\w.]+', '', h)
    h = h.replace('/lib/pkp/templates/images/', '/assets/img/feeds/').replace('/templates/images/ojs_brand.png', '/assets/img/ojs_brand.png')
    h = re.sub(r'csrfToken" value="[^"]*"', 'csrfToken" value="X"', h)
    h = re.sub(r'(\s)(?:aria-labelledby)=(\w[\w-]*)', r'\1aria-labelledby="\2"', h)
    toks = re.findall(r'<[^>]+>|[^<]+', h)
    out = []
    for t in toks:
        if t.startswith('<'):
            t = re.sub(r'\s+', ' ', t).replace(' />', '>').replace('/>', '>').strip()
            m = re.match(r'<(\w+)((?: [\w:-]+(?:="[^"]*")?)*)>$', t)
            if m:
                attrs = sorted(a for a in re.findall(r'[\w:-]+(?:="[^"]*")?', m.group(2)) if a not in ('alt=""',))
                t = '<' + m.group(1) + (' ' + ' '.join(attrs) if attrs else '') + '>'
            t = re.sub(r' (class|id|href|src)="\s*([^"]*?)\s*"', lambda m: ' %s="%s"' % (m.group(1), re.sub(r'\s+', ' ', m.group(2))), t)
            out.append(t)
        else:
            s = re.sub(r'\s+', ' ', H.unescape(t).replace(' ', ' ')).strip()
            if s: out.append(s)
    return out

ref = open(os.path.join(ROOT, 'reference', sys.argv[1].split('/')[0] if False else 'html', sys.argv[1] + '.html') if not os.path.exists(sys.argv[1]) else sys.argv[1], encoding='utf-8').read()
req = urllib.request.Request(BASE + sys.argv[2], headers={'User-Agent': 'Mozilla/5.0 diff'})
ours = urllib.request.urlopen(req).read().decode('utf-8')
a, b = norm(ref), norm(ours)
mx = int(sys.argv[3]) if len(sys.argv) > 3 else 60
d = [l for l in difflib.unified_diff(a, b, 'reference', 'ours', n=1, lineterm='') if not l.startswith(('---', '+++'))]
print(f'{sys.argv[1]}: {len([l for l in d if l[0] in "+-"])} differing tokens')
for l in d[:mx]: print(l[:200])
