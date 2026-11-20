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
R2=$(db "select id from users where username='reviewer2'")
echo "== review forms (OJS 3.3: create, items, activate, assign, reviewer fills, editor reads)"
FORM=/settings/index/workflow/reviewForms; ok "$(code admin $FORM)" 200 "review forms tab"; ok "$(has 'Create Review Form')" 1 "tab lists Create Review Form"
FORM=/content/reviewForm; code admin $FORM >/dev/null; post admin /content/reviewForm -d title= >/dev/null; ok "$(has 'A title is required for the review form')" 1 "form title required"
post admin /content/reviewForm -d 'title=Smoke Form' -d 'description=Please answer.' >/dev/null; RF=$(db "select max(id) from review_forms")
ok "$([ -n "$RF" ] && echo y)" y "review form created"
FORM=/content/reviewFormItem/$RF; code admin $FORM >/dev/null; post admin $FORM -d question= -d element_type=3 >/dev/null; ok "$(has 'A question is required for the form item')" 1 "item question required"
post admin $FORM -d 'question=Is the method sound?' -d element_type=3 -d required=1 >/dev/null
post admin $FORM -d 'question=Overall quality' -d element_type=5 -d 'options[]=Poor' -d 'options[]=Good' >/dev/null
ok "$(db "select count(*) from review_form_elements where review_form_id=$RF")" 2 "two form items saved"
ok "$(code admin /content/reviewFormPreview/$RF)" 200 "preview opens"; ok "$(has 'Is the method sound')" 1 "preview shows items"
FORM=/review/add/$S/$R2; code editor $FORM >/dev/null; ok "$(has 'Smoke Form')" 0 "inactive form is not offered"
FORM=/settings/index/workflow/reviewForms; code admin $FORM >/dev/null; post admin /content/reviewFormToggle/$RF >/dev/null; ok "$(db "select active from review_forms where id=$RF")" 1 "form activated"
FORM=/review/add/$S/$R2; code editor $FORM >/dev/null; ok "$(has 'None / Free Form Review')" 1 "assign page offers None / Free Form Review"
post editor /review/add/$S/$R2 -d method=anonymous -d review_form_id=$RF -d response_due=2030-01-10 -d review_due=2030-02-01 -d subject=R -d 'body=Link {$reviewUrl}' -d skip_email=1 >/dev/null
A2=$(db "select max(id) from review_assignments"); ok "$(db "select review_form_id from review_assignments where id=$A2")" "$RF" "form stored on the assignment"
login rev2 reviewer2 "$PASS"; FORM=/reviewer/submission/$A2; code rev2 $FORM >/dev/null; post rev2 /reviewer/accept/$A2 -d competing=no -d consent=1 >/dev/null
code rev2 /reviewer/submission/$A2/3 >/dev/null; ok "$(has 'Is the method sound')" 1 "reviewer sees the form items"; ok "$(has 'name="comments_author"')" 0 "free comment boxes are replaced"
FORM=/reviewer/submission/$A2/3; post rev2 /reviewer/saveReview/$A2 -d action=submit -d recommendation=accept -d rf_$(db "select min(id) from review_form_elements where review_form_id=$RF")= >/dev/null
code rev2 /reviewer/submission/$A2/3 >/dev/null; ok "$(has 'Please fill in required fields')" 1 "required item enforced"
E1=$(db "select min(id) from review_form_elements where review_form_id=$RF"); E2=$(db "select max(id) from review_form_elements where review_form_id=$RF")
post rev2 /reviewer/saveReview/$A2 -d action=submit -d recommendation=accept -d "rf_$E1=Yes, it is." -d "rf_$E2=1" >/dev/null
ok "$(db "select status from review_assignments where id=$A2")" review_submitted "review with form submitted"
ok "$(db "select response_value from review_form_responses where review_assignment_id=$A2 and review_form_element_id=$E2")" 1 "choice response stored"
code editor /review/details/$S/$A2 >/dev/null; ok "$(has 'Yes, it is.')" 1 "editor reads the response"; ok "$(has 'Good')" 1 "choice shown as its label"
FORM=/settings/index/workflow/reviewForms; code admin $FORM >/dev/null; post admin /content/reviewFormDelete/$RF >/dev/null; ok "$(db "select count(*) from review_forms where id=$RF")" 1 "form in use cannot be deleted"
post admin /content/reviewFormCopy/$RF >/dev/null; ok "$(db "select count(*) from review_forms where title like 'Copy of%'")" 1 "form copied"
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
FORM=/settings/index/website/doi; code admin $FORM >/dev/null; post admin /settings/save/website/doi -d doiPrefix=10.59543/jidmis -d 'doiPattern=v%vi%i.%a' -d doiAutoAssign=1 >/dev/null
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
FORM=/settings/index/website/theme; code admin $FORM >/dev/null; post admin /settings/save/website/theme -d themeColor=#1a7f5a >/dev/null
FORM=/settings/index/website/appearanceSetup; code admin $FORM >/dev/null; post admin /settings/save/website/appearanceSetup --data-urlencode "pageFooter=<p>Smoke footer</p>" >/dev/null
ok "$(curl -s "$BASE" | grep -c 'css/theme?v=')" 1 "recoloured theme linked"; ok "$(curl -s "$BASE/css/theme" | grep -ci '#1a7f5a' | sed 's/^[1-9][0-9]*$/1/')" 1 "theme css recoloured"; ok "$(curl -s "$BASE" | grep -c 'Smoke footer')" 1 "footer updated"
post admin /settings/save/website/theme -d themeColor=#d79f24 >/dev/null
FORM=/settings/index/website/appearanceSetup; code admin $FORM >/dev/null; post admin /settings/save/website/appearanceSetup --data-urlencode "pageFooter=${ORIG_FOOTER:-<p>Journal footer</p>}" >/dev/null
ok "$(curl -s "$BASE" | grep -c 'assets/css/theme.css')" 1 "original colour uses the static stylesheet again"
ok "$(code admin /users)" 200 "users"; ok "$(code admin /statistics)" 200 "statistics"; ok "$(code admin /admin/index/system)" 200 "system information"

echo "== security"
ok "$(curl -s -D - -o /dev/null "$BASE" | grep -ci '^X-Frame-Options: SAMEORIGIN')" 1 "X-Frame-Options"; ok "$(curl -s -D - -o /dev/null "$BASE" | grep -ci '^X-Content-Type-Options: nosniff')" 1 "nosniff"
P=$(python3 -c "import urllib.parse;print(urllib.parse.quote(\"<img src=x onerror=XSSMARK>\"))" 2>/dev/null || python -c "import urllib.parse;print(urllib.parse.quote('<img src=x onerror=XSSMARK>'))")
ok "$(curl -s "$BASE/search/search?query=$P" | grep -c '<img src=x onerror=XSSMARK')" 0 "reflected XSS is escaped"
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/search/search?query=%27%20OR%201%3D1--")" 200 "SQL injection probe is harmless"
echo "== site access options (Users & Roles)"
FORM=/users/access; post admin /users/access -d restrictArticleAccess=1 >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/article/view/182/29")" 302 "galley needs login when content is restricted"
ok "$(code admin /article/view/182/29)" 200 "logged-in users still reach it"
post admin /users/access -d restrictSiteAccess=1 >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/about")" 302 "site restricted: visitors are sent to login"
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/login")" 200 "login page stays reachable"
post admin /users/access -d disableUserReg=0 >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/about")" 200 "all options off again"
echo "== categories, publisher library, languages, OAI switch"
FORM=/content/category; post admin /content/category -d title=Medicine -d path=medicine >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/catalog/category/medicine")" 200 "category page"
ok "$(post admin /content/category -d title=Again -d path=medicine >/dev/null; has 'already exists')" 1 "duplicate category path refused"
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/libraryFiles/downloadPublic/1")" 200 "seeded public library file downloads"
FORM=/content/library; t=$(tok admin $FORM); ok "$(curl -s -b "$T/admin" -c "$T/admin" -o /dev/null -w '%{http_code}' "$BASE/content/library" -F csrfToken=$t -F "name=Smoke file" -F file_type=4 -F file=@"$T/test.pdf")" 302 "library file uploaded"
LID=$(db "select max(id) from library_files" 2>/dev/null | head -1); [ -z "$LID" ] && LID=3
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/libraryFiles/downloadPublic/$LID")" 404 "private library file is not public"
ok "$(code admin /content/libraryDownload/$LID)" 200 "manager can download it"
ok "$(code admin /settings/index/website/languages)" 200 "languages tab"
FORM=/settings/index/distribution/access; post admin /settings/save/distribution/access >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/oai?verb=Identify")" 404 "OAI switched off"
post admin /settings/save/distribution/access -d enableOai=1 >/dev/null
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/oai?verb=Identify")" 200 "OAI switched on"
echo "== metadata options (Workflow > Submission > Metadata)"
FORM=/settings/index/workflow/metadata; code admin $FORM >/dev/null
ok "$(has 'be_metafield')" 1 "metadata settings form"
post admin /settings/save/workflow/metadata -d coverage_on=1 -d coverage_mode=request -d disciplines_on=1 -d disciplines_mode=enable -d keywords_on=1 -d keywords_mode=request >/dev/null
code admin /publication/index/182/metadata >/dev/null; ok "$(has 'name="meta_disciplines"')" 1 "enabled field appears on the Publication tab"
ok "$(grep -c 'publication/index/182/references' "$T/out")" 0 "References tab hidden when references are off"
post admin /settings/save/workflow/metadata -d keywords_on=1 -d keywords_mode=request -d citations_on=1 -d citations_mode=request >/dev/null
code admin /publication/index/182/metadata >/dev/null; ok "$(has 'name="meta_disciplines"')" 0 "switched off again"
echo "== competing interests and publisher ID (Workflow > Submission > Metadata)"
FORM=/settings/index/workflow/metadata; post admin /settings/save/workflow/metadata -d keywords_on=1 -d keywords_mode=request -d citations_on=1 -d citations_mode=request -d requireAuthorCompetingInterests=1 -d pubid_publication=1 -d pubid_issue=1 >/dev/null
FORM=/submission/wizard; post author /submission/wizard -d section_id=1 -d "checklist[0]=1" -d "checklist[1]=1" -d "checklist[2]=1" -d "checklist[3]=1" -d "checklist[4]=1" -d privacyConsent=1 -d copyrightAgree=1 >/dev/null
CS2=$(loc | grep -o 'wizard/[0-9]*' | cut -d/ -f2)
FORM=/submission/contributor/$CS2; post author /submission/contributor/$CS2 -d given_name=Cy -d family_name=Co -d email=cy@example.com -d country=NG >/dev/null
ok "$(has 'This field is required')" 1 "contributor without a competing interests statement is refused"
post author /submission/contributor/$CS2 -d given_name=Cy -d family_name=Co -d email=cy@example.com -d country=NG --data-urlencode "competing_interests=None declared" >/dev/null
ok "$(loc | grep -c 'wizard')" 1 "contributor with the statement is saved"
code editor /publication/index/$S/identifiers >/dev/null; ok "$(has 'name="publisher_id"')" 1 "Publisher ID field on the Identifiers tab"
FORM=/manageIssues/form; post editor /manageIssues/form -d volume=98 -d number=1 -d year=2098 -d publisher_id=ISS-1 >/dev/null
ok "$(post editor /manageIssues/form -d volume=98 -d number=2 -d year=2098 -d publisher_id=ISS-1)" 200 "duplicate Publisher ID is refused"
FORM=/settings/index/workflow/metadata; post admin /settings/save/workflow/metadata -d keywords_on=1 -d keywords_mode=request -d citations_on=1 -d citations_mode=request >/dev/null
echo "== indexing feeds and reports (same shapes as OJS 3.3)"
ok "$(curl -s "$BASE/oai?verb=Identify" | grep -c '<repositoryName>')" 1 "OAI Identify"
ok "$(curl -s "$BASE/oai?verb=ListSets" | grep -c 'setSpec')" 2 "OAI sets (journal and Articles)"
ok "$(curl -s "$BASE/oai?verb=GetRecord&identifier=oai:ojs2.jidmis.org:article/182&metadataPrefix=oai_dc" | grep -c '<dc:title')" 1 "OAI record in Dublin Core"
ok "$(curl -s "$BASE/oai?verb=Nope" | grep -c 'code="badVerb"')" 1 "OAI bad verb error"
ok "$(curl -s "$BASE/sitemap" | grep -c 'article/view/182')" 1 "sitemap lists articles"
ok "$(code admin /statistics/editorial)" 200 "statistics: editorial activity"
ok "$(curl -s -b "$T/admin" "$BASE/statistics/report/users" | head -1 | grep -c 'Username')" 1 "users report (CSV)"
echo "== registration (OJS 3.3 validation messages and success page)"
FORM=/user/register; post regj /user/register -d givenName=A -d country=NG -d email=editor@example.com -d username=Editor -d password=abc -d password2=abd >/dev/null
ok "$(has 'Errors occurred processing this form')" 1 "error list heading"
ok "$(has 'The password must be at least 8 characters')" 1 "short password message"
ok "$(has 'The selected email address is already in use')" 1 "duplicate email message"
ok "$(has 'You must agree to the terms of the privacy statement')" 1 "consent message"
post regj /user/register -d givenName=Smoke -d country=NG -d email=smoke-reg@example.com -d username=smokereg -d password=Abcdefg1 -d password2=Abcdefg1 -d privacyConsent=1 >/dev/null
ok "$(loc | grep -c 'user/register')" 1 "success redirects to /user/register"
code regj /user/register >/dev/null
echo "== text is translated (a missing language key shows up as a raw key)"
ok "$(curl -s "$BASE/user/register" | grep -c 'Privacy Options')" 1 "register page strings present"
ok "$(curl -s "$BASE/login/lostPassword" | grep -c 'Registered user')" 1 "lost password strings present"
if [ -n "$DBCMD" ]; then
echo "== password reset (OJS 3.3 flow: e-mailed link opens a form to choose a new password)"
FORM=/login/lostPassword; post anon /login/requestResetPassword -d email=reader@example.com >/dev/null
ok "$(has 'A confirmation has been sent')" 1 "same confirmation page"
RLINK=$(db "select body from email_log order by id desc limit 1" | grep -o 'resetPassword/reader?confirm=[A-Za-z0-9]*' | head -1)
HASH=${RLINK#*confirm=}
ok "$([ -n "$RLINK" ] && echo y)" y "reset link e-mailed"
ok "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/login/resetPassword/nosuchuser?confirm=x")" 302 "unknown username redirects"
code anon "/login/resetPassword/reader?confirm=bogus" >/dev/null; ok "$(has 'expired or is not valid')" 1 "bad hash shows the invalid-link message"
code anon "/login/$RLINK" >/dev/null; ok "$(has 'Enter new password')" 1 "valid link opens the new password form"
FORM="/login/$RLINK"; post anon /login/updateResetPassword -d username=reader -d hash=$HASH -d password=NewPass@123 -d password2=different >/dev/null
ok "$(has 'passwords do not match')" 1 "mismatch refused"
post anon /login/updateResetPassword -d username=reader -d hash=$HASH -d password=NewPass@123 -d password2=NewPass@123 >/dev/null
ok "$(has 'Password has been updated successfully')" 1 "password updated"
code anon "/login/$RLINK" >/dev/null; ok "$(has 'expired or is not valid')" 1 "link works only once"
login rdr reader NewPass@123; ok "$(code rdr /submissions)" 200 "new password logs in"
fi
echo; echo "passed: $pass, failed: $fail"; [ $fail -eq 0 ]
