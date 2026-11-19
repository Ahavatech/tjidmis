#!/usr/bin/env python3
"""Prepares the reference CSS for local use: rewrites font URLs to assets/fonts, drops legacy font formats.
    python tools/build_css.py"""
import re, os
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
R = os.path.join(ROOT, 'reference', 'css')

def fonts(css, prefix):
    def block(m):
        b = m.group(0)
        fam = re.search(r"font-family:\s*['\"]?([^;'\"]+)", b).group(1)
        base = None
        for u in re.findall(r'url\(([^)]+)\)', b):
            u = u.strip('\'"')
            mm = re.search(r'/([^/]+?)\.(?:eot|woff2?|ttf|svg)', u) or re.search(r'^([^?#]+?)\.(?:eot|woff2?|ttf|svg)', u)
            if mm: base = mm.group(1); break
        if not base: return b
        src = f"src:url('{prefix}{base}.woff2') format('woff2'),url('{prefix}{base}.woff') format('woff')"
        b = re.sub(r'src:[^;}]*;?', '', b)
        return b[:-1].rstrip(';') + ';' + src + '}'
    return re.sub(r'@font-face\{.*?\}', block, css, flags=re.S)

theme = fonts(open(os.path.join(R, 'stylesheet.css'), encoding='utf-8').read(), '../fonts/')
open(os.path.join(ROOT, 'assets', 'css', 'theme.css'), 'w', encoding='utf-8').write(theme)
fa = open(os.path.join(R, 'fontawesome.css'), encoding='utf-8').read()
fa = re.sub(r'@font-face\s*\{.*?\}', "@font-face{font-family:'FontAwesome';src:url('../fonts/fontawesome-webfont.woff2') format('woff2'),url('../fonts/fontawesome-webfont.woff') format('woff'),url('../fonts/fontawesome-webfont.ttf') format('truetype');font-weight:normal;font-style:normal}", fa, count=1, flags=re.S)
open(os.path.join(ROOT, 'assets', 'css', 'fontawesome.css'), 'w', encoding='utf-8').write(fa)
j = re.sub(r"@import url\('[^']*'\);\s*", '', open(os.path.join(R, 'journal-styleSheet.css'), encoding='utf-8').read())
os.makedirs(os.path.join(ROOT, 'public', 'journals', '1'), exist_ok=True)
open(os.path.join(ROOT, 'public', 'journals', '1', 'styleSheet.css'), 'w', encoding='utf-8').write(j)
print('theme.css', len(theme), 'fontawesome.css', len(fa), 'journal css', len(j))
