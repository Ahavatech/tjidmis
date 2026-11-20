import urllib.request,subprocess,os
from PIL import Image
T=os.environ['TEMP']; CH=r"C:\Program Files\Google\Chrome\Application\chrome.exe"
for n,u,b in [('clone','http://localhost:8081/index.php/jidmis','http://localhost:8081/'),('ojs','http://localhost:8082/jidmis','http://localhost:8082/')]:
    h=urllib.request.urlopen(u).read().decode()
    h=h.replace('<head>','<head><base href="%s">'%b,1).replace('</body>','<script>window.addEventListener("load",function(){setTimeout(function(){document.querySelector(".pkp_site_nav_toggle").click()},300)})</script></body>')
    f=os.path.join(T,'m_%s.html'%n); open(f,'w',encoding='utf-8').write(h)
    subprocess.run([CH,"--headless","--disable-gpu","--window-size=480,900","--virtual-time-budget=5000","--screenshot="+os.path.join(T,'m_%s.png'%n),"file:///"+f.replace(chr(92),'/')],capture_output=True)
a=Image.open(os.path.join(T,'m_clone.png'));b=Image.open(os.path.join(T,'m_ojs.png'));w=Image.new('RGB',(960,900));w.paste(a,(0,0));w.paste(b,(480,0));w.save(os.path.join(T,'m_side.png'))
