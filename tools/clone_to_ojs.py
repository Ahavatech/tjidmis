"""Copy nav menu, static pages and journal text settings from the PHP clone DB into the local OJS DB. Dev only."""
import subprocess, json, sys
def q(db, sql):
    out = subprocess.run(["docker","compose","exec","-T","db","mysql","-uroot","-proot","-B","-N","--default-character-set=utf8mb4",db,"-e",sql],capture_output=True,text=True,encoding="utf-8",check=True).stdout
    return [l.split("\t") for l in out.split("\n") if l]
def run(sql):
    subprocess.run(["docker","compose","exec","-T","db","mysql","-uroot","-proot","--default-character-set=utf8mb4","ojs"],input=sql,text=True,encoding="utf-8",check=True)
def esc(s): return "CONVERT(0x"+s.encode("utf-8").hex()+" USING utf8mb4)" if s else "''"
JURL="http://localhost:8082/jidmis"; PUB="http://localhost:8082/public"
def exp(h): return (h or "").replace("__JURL__",JURL).replace("__PUBLIC__",PUB).replace("__ASSETS__","http://localhost:8082/plugins/themes/jidmis/assets").replace("__SUBMIT_URL__",JURL+"/about/submissions").replace("\n","\n")
def HX(rows,i=1):
    for r in rows:
        r[i]=bytes.fromhex(r[i]).decode('utf-8') if r[i] else ''
    return rows
s=[]
# journal text settings
for name,val in HX(q("jidmis","select name,hex(value) from journal_settings where name in ('description','additionalHomeContent','pageFooter','about','editorialTeam','privacyStatement','authorGuidelines','copyrightNotice','licenseTerms','readerInfo','authorInfo','librarianInfo')")):
    s.append(f"delete from journal_settings where journal_id=1 and setting_name={esc(name)};insert into journal_settings values (1,'en_US',{esc(name)},{esc(exp(val))},'string');")
# static pages
s.append("delete from static_page_settings;delete from static_pages;")
sp={}
for i,(sid,path,title,content) in enumerate(HX(q("jidmis","select id,path,title,hex(content) from static_pages"),-1),1):
    sp[sid]=path
    s.append(f"insert into static_pages values ({i},{esc(path)},1);insert into static_page_settings values ({i},'en_US','title',{esc(title)},'string'),({i},'en_US','content',{esc(exp(content))},'string');")
s.append("delete from plugin_settings where plugin_name='staticpagesplugin';insert into plugin_settings values ('staticpagesplugin',1,'enabled','1','bool');")
# nav
T={'about':'NMI_TYPE_ABOUT','editorial_team':'NMI_TYPE_EDITORIAL_TEAM','privacy':'NMI_TYPE_PRIVACY','contact':'NMI_TYPE_CONTACT','current':'NMI_TYPE_CURRENT','archives':'NMI_TYPE_ARCHIVES','submissions':'NMI_TYPE_SUBMISSIONS','announcements':'NMI_TYPE_ANNOUNCEMENTS'}
s.append("delete from navigation_menu_item_assignments where navigation_menu_id=3;delete from navigation_menu_item_settings where navigation_menu_item_id in (select navigation_menu_item_id from navigation_menu_items where context_id=1 and navigation_menu_item_id in (15,16,17,18,19,20,21,22,23) or navigation_menu_item_id>=100);delete from navigation_menu_items where navigation_menu_item_id in (15,16,17,18,19,20,21,22,23) or navigation_menu_item_id>=100;")
for (id_,parent,typ,title,spid,seq) in q("jidmis","select id,ifnull(parent_id,0),type,title,ifnull(static_page_id,0),seq from navigation_menu_items where menu='primary' order by parent_id is not null, parent_id, seq"):
    nid=100+int(id_); par=0 if parent=="0" else 100+int(parent)
    if typ=='static_page': t,path='NMI_TYPE_CUSTOM',sp[spid]
    elif typ=='custom_url': t,path='NMI_TYPE_REMOTE_URL',None
    else: t,path=T[typ],None
    s.append(f"insert into navigation_menu_items values ({nid},1,{esc(path) if path else 'NULL'},'{t}');insert into navigation_menu_item_settings values ({nid},'en_US','title',{esc(title)},'string');")
    if t=='NMI_TYPE_REMOTE_URL': s.append(f"insert into navigation_menu_item_settings values ({nid},'en_US','remoteUrl','#','string');")
    s.append(f"insert into navigation_menu_item_assignments (navigation_menu_id,navigation_menu_item_id,parent_id,seq) values (3,{nid},{par},{seq});")
run("\n".join(s)); print("ok")
