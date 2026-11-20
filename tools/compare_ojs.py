"""Screenshot clone (8081) vs OJS (8082) pages and report pixel diff. Dev only. Usage: python tools/compare_ojs.py [name...]"""
import subprocess, re, sys, os, urllib.request, tempfile
from PIL import Image, ImageChops
CH = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
A = "http://localhost:8081/index.php/jidmis"; B = "http://localhost:8082/jidmis"
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "reference", "compare"); os.makedirs(OUT, exist_ok=True)
def get(u): return urllib.request.urlopen(u).read().decode("utf-8", "replace")
def first(base, path, pat):
    m = re.search(pat, get(base + path)); return re.sub(r"^.*?/(issue|article)/", r"\1/", m.group(0)) if m else None
pages = {"home":"", "about":"/about", "submissions":"/about/submissions", "editorial":"/about/editorialTeam", "contact":"/about/contact",
 "privacy":"/about/privacy", "current":"/issue/current", "archive":"/issue/archive", "announcements":"/announcement",
 "search":"/search", "register":"/user/register", "login":"/login", "lostpw":"/login/lostPassword", "pes":"/pes", "apc":"/APCharges", "readers":"/information/readers", "authors":"/information/authors", "librarians":"/information/librarians", "searchq":"/search/search?query=decision"}
import json
for s in ["COI","copyright","selfarchiving","archiving","plagiarism","unethical","retraction","complaints","genai","indexing","disclaimer"]: pass
pp = {k:(v,v) for k,v in pages.items()}
def issues(base):
    h=get(base+"/issue/archive"); return {re.sub(r"\s+"," ",m.group(2)).strip():m.group(1) for m in re.finditer(r'href="[^"]*?/(issue/view/\d+)"[^>]*>\s*([^<]+?)\s*<',h)}
try:
    ia,ib=issues(A),issues(B); common=[k for k in ia if k in ib][:3]
    for i,k in enumerate(common): pp[f"issue{i}"]=("/"+ia[k],"/"+ib[k])
except Exception as e: print("issue pairing failed",e)
try:
    pp["issue"] = tuple("/"+first(b,"/issue/archive",r'issue/view/\d+') for b in (A,B))
    pp["article"] = tuple("/"+first(b,"/issue/current",r'article/view/\d+') for b in (A,B))
except Exception as e: print("link discovery failed", e)
W=int(os.environ.get("W","1280"))
def shot(url, f, w=None):
    w=w or W
    subprocess.run([CH,"--headless","--disable-gpu","--hide-scrollbars",f"--window-size={w},7000","--virtual-time-budget=4000",f"--screenshot={f}",url],capture_output=True)
for k,(pa,pb) in pp.items():
    if len(sys.argv)>1 and k not in sys.argv[1:]: continue
    fa, fb = (os.path.join(OUT,f"{k}{'_m' if W<800 else ''}_{s}.png") for s in ("clone","ojs"))
    shot(A+pa, fa); shot(B+pb, fb)
    a, b = Image.open(fa).convert("RGB"), Image.open(fb).convert("RGB")
    def trim(im):
        bg=Image.new("RGB",im.size,im.getpixel((2,im.height-2))); bb=ImageChops.difference(im,bg).getbbox(); return im.crop((0,0,im.width,min(im.height,(bb[3]+40) if bb else im.height)))
    a,b=trim(a),trim(b); h = max(a.height,b.height); a2=Image.new("RGB",(W,h),"white");b2=a2.copy();a2.paste(a);b2.paste(b); a,b=a2,b2
    d = ImageChops.difference(a, b).convert("L").point(lambda p:255 if p>24 else 0)
    print(f"{k:14s} h {a.height}/{b.height} diff {100*sum(1 for p in d.getdata() if p)/(W*h):5.1f}%   {A+pa} | {B+pb}")
