#!/usr/bin/env bash
# End-to-end smoke test: submit -> review -> accept -> production -> issue -> publish -> public download,
# plus admin settings, permissions and security checks. Run against an installed site with demo data:
#   BASE=http://localhost:8081/index.php/jidmis ADMIN_PASS='Admin@12345' bash tests/smoke.sh
# Optional DB access for extra assertions: DBCMD="docker exec jmaria mariadb -uroot -proot jirs -N -e"
BASE=${BASE:-http://localhost:8081/index.php/jidmis}; PASS=${DEMO_PASS:-Demo@12345}; ADMIN_PASS=${ADMIN_PASS:-Admin@12345}
export MSYS_NO_PATHCONV=1
T=$(mktemp -d); command -v cygpath >/dev/null && T=$(cygpath -m "$T"); trap 'rm -rf "$T"' EXIT; pass=0; fail=0
ok(){ if [ "$1" = "$2" ]; then pass=$((pass+1)); else fail=$((fail+1)); echo "FAIL $3 (got '$1', want '$2')"; fi; }
db(){ [ -n "$DBCMD" ] && $DBCMD "$1"; }
tok(){ curl -s -b "$T/$1" -c "$T/$1" "$BASE$2" | grep -o 'name="csrfToken" value="[^"]*"' | head -1 | cut -d'"' -f4; }
post(){ j=$1; p=$2; shift 2; t=$(tok $j "$FORM"); curl -s -b "$T/$j" -c "$T/$j" -o "$T/out" -D "$T/hdr" -w "%{http_code}" "$BASE$p" -d csrfToken="$t" "$@"; }
code(){ curl -s -b "$T/$1" -c "$T/$1" -o "$T/out" -w "%{http_code}" "$BASE$2"; }
loc(){ grep -i '^location' "$T/hdr" | tr -d '\r' | cut -d' ' -f2; }
has(){ grep -c -- "$1" "$T/out" | sed 's/^[1-9][0-9]*$/1/'; }
login(){ FORM=/login; post $1 /login/signIn -d username=$2 -d password=$3 >/dev/null; }
printf '%%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%%%EOF' > "$T/test.pdf"

echo "== public pages"
for p in "" /about /about/editorialTeam /about/contact /about/submissions /issue/current /issue/archive /issue/view/15 /article/view/182 /article/view/182/29 /announcement /search/search?query=decision /user/register /login /login/lostPassword /information/readers /pes /gateway/plugin/WebFeedGatewayPlugin/atom; do
  ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE$p")" 200 "public $p"; done
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/nope/nope")" 404 "404 page"
ok "$(curl -s -D - -o /dev/null "$BASE/article/download/182/29/29" | grep -ci 'application/pdf')" 1 "real PDF served"

echo "== accounts and permissions"
for u in author editor reviewer1 reader; do login $u $u "$PASS"; done; login admin admin "$ADMIN_PASS"
ok "$(code anon /submissions)" 302 "anonymous dashboard redirects"; ok "$(code author /settings)" 403 "author: settings 403"; ok "$(code editor /admin)" 403 "editor: admin 403"; ok "$(code reader /submission/wizard)" 403 "reader cannot submit"
ok "$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/login/signIn" -d a=1)" 400 "POST without CSRF token is refused"

echo "== author submits"
FORM=/submission/wizard; post author /submission/wizard -d section_id=1 -d "checklist[0]=1" -d "checklist[1]=1" -d "checklist[2]=1" -d "checklist[3]=1" -d "checklist[4]=1" -d privacyConsent=1 -d copyrightAgree=1 >/dev/null
S=$(loc | grep -o 'wizard/[0-9]*' | cut -d/ -f2); ok "$([ -n "$S" ] && echo y)" y "draft created"
t=$(tok author /submission/wizard/$S/2); curl -s -b "$T/author" -c "$T/author" -o /dev/null "$BASE/submission/uploadFile/$S" -F csrfToken=$t -F "component=Article Text" -F "file=@$T/test.pdf"
FORM=/submission/wizard/$S/2; post author /submission/wizard/$S/2 >/dev/null
FORM=/submission/wizard/$S/3; post author /submission/wizard/$S/3 -d action=save -d "title=Smoke Test Article" -d "abstract=Smoke abstract." >/dev/null
FORM=/submission/contributor/$S; post author /submission/contributor/$S -d given_name=Ada -d family_name=Author -d email=author@example.com -d country=NG -d primary=1 >/dev/null
FORM=/submission/wizard/$S/3; post author /submission/wizard/$S/3 -d action=continue -d "title=Smoke Test Article" -d "abstract=Smoke abstract." >/dev/null
FORM=/submission/wizard/$S/4; ok "$(post author /submission/finish/$S)" 302 "submission finished"
ok "$(code editor '/submissions?tab=unassigned')" 200 "editor dashboard"; ok "$(has 'Smoke Test Article')" 1 "new submission in the editor's Unassigned tab"

echo "== editor: review cycle"
FID=$(db "select id from submission_files where submission_id=$S order by id limit 1" | head -1); [ -z "$FID" ] && FID=1
FORM=/decision/index/$S/send_to_review; code editor /decision/index/$S/send_to_review >/dev/null; post editor /decision/save/$S/send_to_review -d subject=Review -d body=Go -d "files[]=$FID" >/dev/null
ok "$(code editor /workflow/index/$S/3)" 200 "review stage"
R1=$(db "select id from users where username='reviewer1'"); [ -z "$R1" ] && R1=4
FORM=/review/add/$S/$R1; code editor /review/add/$S/$R1 >/dev/null; post editor /review/add/$S/$R1 -d method=anonymous -d response_due=2030-01-10 -d review_due=2030-02-01 -d subject=R -d 'body=Link {$reviewUrl}' -d skip_email=1 >/dev/null
A=$(db "select max(id) from review_assignments"); [ -z "$A" ] && A=1
ok "$(code reviewer1 /workflow/index/$S)" 403 "reviewer cannot open the workflow page"
FORM=/reviewer/submission/$A; code reviewer1 /reviewer/submission/$A >/dev/null; post reviewer1 /reviewer/accept/$A -d competing=no -d consent=1 >/dev/null
FORM=/reviewer/submission/$A/3; code reviewer1 $FORM >/dev/null; post reviewer1 /reviewer/saveReview/$A -d action=submit -d "comments_author=Good work." -d "comments_editor=ok" -d recommendation=accept >/dev/null
ok "$(code reviewer1 /reviewer/submission/$A/4)" 200 "review submitted"
code author /workflow/index/$S/3 >/dev/null; ok "$(has 'Reviewer')" 0 "author never sees reviewer identity"
FORM=/decision/index/$S/accept; code editor /decision/index/$S/accept >/dev/null; post editor /decision/save/$S/accept -d subject=Accepted -d body=Yes >/dev/null
FORM=/decision/index/$S/send_to_production; code editor /decision/index/$S/send_to_production >/dev/null; post editor /decision/save/$S/send_to_production -d subject=P -d body=P -d skip_email=1 >/dev/null
ok "$(code editor /workflow/index/$S/5)" 200 "production stage"

echo "== publish"
FORM=/publication/index/$S/galleys; code editor $FORM >/dev/null
t=$(tok editor $FORM); ok "$(curl -s -b "$T/editor" -c "$T/editor" -o /dev/null -w '%{http_code}' "$BASE/publication/addGalley/$S" -F csrfToken=$t -F label=PDF -F "file=@$T/test.pdf")" 302 "galley added"
FORM=/manageIssues/form; code editor /manageIssues/form >/dev/null; post editor /manageIssues/form -d volume=99 -d number=1 -d year=2099 -d show_volume=1 -d show_number=1 -d show_year=1 >/dev/null
ISS=$(loc | grep -o '[0-9]*$'); ok "$([ -n "$ISS" ] && echo y)" y "issue created"
FORM=/publication/index/$S/issue; code editor $FORM >/dev/null; post editor /publication/save/$S/issue -d section_id=1 -d pages=1-9 >/dev/null; post editor /publication/schedule/$S -d issue_id=$ISS >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/article/view/$S")" 404 "hidden while scheduled"
echo "== DOI pattern and APC tracking"
FORM=/settings/index/distribution/doi; code admin $FORM >/dev/null; post admin /settings/save/distribution/doi -d doiPrefix=10.59543/jidmis -d 'doiPattern=v%vi%i.%a' -d doiAutoAssign=1 >/dev/null
FORM=/settings/index/distribution/payments; code admin $FORM >/dev/null; post admin /settings/save/distribution/payments -d apcEnabled=1 -d apcFee=250 -d apcCurrency=usd >/dev/null
FORM=/publication/index/$S/identifiers; code editor $FORM >/dev/null; post editor /publication/assignDoi/$S >/dev/null; code editor $FORM >/dev/null
ok "$(has "10.59543/jidmis.v99i1.$S")" 1 "DOI generated from the pattern"
code editor /workflow/index/$S/5 >/dev/null; ok "$(has 'Article Processing Charge')" 1 "APC box shown to editors"
FORM=/workflow/index/$S/5; post editor /publication/saveApc/$S -d status=paid -d amount=250 -d currency=USD -d reference=TX1 >/dev/null
code editor /workflow/index/$S/5 >/dev/null; ok "$(has 'Marked paid on')" 1 "APC marked paid"
ok "$(post editor /publication/saveApc/$S -d status=bogus -d amount=1)" 302 "invalid APC status refused"
code author /workflow/index/$S/5 >/dev/null; ok "$(has 'Article Processing Charge')" 0 "authors do not see the APC box"
ok "$(code author /publication/index/$S/identifiers)" 403 "author cannot open publication tab"
FORM=/manageIssues/edit/$ISS; code editor $FORM >/dev/null; post editor /manageIssues/publish/$ISS >/dev/null
ok "$(curl -s -o "$T/a" -w '%{http_code}' "$BASE/article/view/$S")" 200 "article is public"; ok "$(grep -c 'Smoke Test Article' "$T/a" | sed 's/[2-9]/1/')" 1 "with its title"
G=$(curl -s "$BASE/article/view/$S" | grep -o "article/view/$S/[0-9]*" | head -1 | cut -d/ -f4); ok "$(curl -s "$BASE/article/download/$S/$G/1" | head -c 4)" "%PDF" "anonymous PDF download"
ok "$(curl -s "$BASE" | grep -c 'Vol. 99 No. 1 (2099)' | sed 's/[2-9]/1/')" 1 "home page shows the new current issue"

echo "== admin settings reach the public site"
ORIG_FOOTER=$(db "select value from journal_settings where name='pageFooter' and journal_id=1" | head -c 4000)
FORM=/settings/index/website/appearance; code admin $FORM >/dev/null; post admin /settings/save/website/appearance -d themeColor=#1a7f5a -d "pageFooter=<p>Smoke footer</p>" >/dev/null
ok "$(curl -s "$BASE" | grep -c 'css/theme?v=')" 1 "recoloured theme linked"; ok "$(curl -s "$BASE/css/theme" | grep -ci '#1a7f5a' | sed 's/^[1-9][0-9]*$/1/')" 1 "theme css recoloured"; ok "$(curl -s "$BASE" | grep -c 'Smoke footer')" 1 "footer updated"
post admin /settings/save/website/appearance -d themeColor=#d79f24 --data-urlencode "pageFooter=${ORIG_FOOTER:-<p>Journal footer</p>}" >/dev/null
ok "$(curl -s "$BASE" | grep -c 'assets/css/theme.css')" 1 "original colour uses the static stylesheet again"
ok "$(code admin /users)" 200 "users"; ok "$(code admin /statistics)" 200 "statistics"; ok "$(code admin /admin/index/system)" 200 "system information"

echo "== security"
ok "$(curl -s -D - -o /dev/null "$BASE" | grep -ci '^X-Frame-Options: SAMEORIGIN')" 1 "X-Frame-Options"; ok "$(curl -s -D - -o /dev/null "$BASE" | grep -ci '^X-Content-Type-Options: nosniff')" 1 "nosniff"
P=$(python3 -c "import urllib.parse;print(urllib.parse.quote(\"<img src=x onerror=XSSMARK>\"))" 2>/dev/null || python -c "import urllib.parse;print(urllib.parse.quote('<img src=x onerror=XSSMARK>'))")
ok "$(curl -s "$BASE/search/search?query=$P" | grep -c '<img src=x onerror=XSSMARK')" 0 "reflected XSS is escaped"
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/search/search?query=%27%20OR%201%3D1--")" 200 "SQL injection probe is harmless"
echo; echo "passed: $pass, failed: $fail"; [ $fail -eq 0 ]
