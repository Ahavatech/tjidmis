#!/usr/bin/env bash
# End-to-end workflow check against the LOCAL OJS 3.3 (docker compose --profile reference up -d).
# author submits -> editor sends to review -> reviewer reviews -> editor accepts -> production -> publish.
# Usage: bash tools/e2e_ojs.sh [--keep]     (default: deletes the test submission at the end)
# Needs the demo accounts (bash tools/ojs_demo_users.sh create; password Demo@12345), curl and docker compose.
# Section, Author group and current issue are looked up in the database, so only B and PW may need changing.
set -u
cd "$(dirname "$0")/.."; export MSYS_NO_PATHCONV=1
B=${B:-http://localhost:8082/jidmis}; PW=${PW:-Demo@12345}
T=$(mktemp -d); command -v cygpath >/dev/null && T=$(cygpath -m "$T")
X='X-Requested-With: XMLHttpRequest'; KEEP=0; [ "${1:-}" = "--keep" ] && KEEP=1
DB(){ docker compose exec -T db mysql -uroot -proot -N ojs -e "$1" | tr -d '\r'; }
ok(){ echo "  ok   $1"; }; die(){ echo "  FAIL $1"; exit 1; }

login(){ local J=$T/$1; rm -f $J; curl -s -c $J -b $J -o $T/L.html $B/login
  local cs=$(grep -o 'name="csrfToken" value="[^"]*"' $T/L.html | head -1 | cut -d'"' -f4)
  curl -s -c $J -b $J -o /dev/null $B/login/signIn -d csrfToken=$cs -d username=$1 --data-urlencode password="$PW"
  curl -s -b $J $B/submissions | grep -o 'csrfToken":"[^"]*' | head -1 | cut -d'"' -f3 > $J.tok
  [ -s $J.tok ] || die "login as $1"; }
# post <user> <url> [curl args]   (ajax form post with that user's CSRF token)
post(){ local u=$1 url=$2; shift 2; curl -s -b $T/$u -H "$X" "$url" -d csrfToken=$(cat $T/$u.tok) "$@"; }
api(){ local u=$1; shift; curl -s -b $T/$u -H "X-Csrf-Token: $(cat $T/$u.tok)" "$@"; }
expect(){ grep -q "$2" <<<"$1" || { echo "$1" | cut -c1-300; die "$3"; }; }
CALL="$B/\$\$\$call\$\$\$"

printf '%%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%%%EOF\n' > $T/test.pdf

echo "Logging in"; for u in author editor reviewer1 admin; do login $u; done; ok "4 accounts"
REVIEWER=$(DB "select user_id from users where username='reviewer1'")
ISSUE=$(DB "select issue_id from issues where current=1 limit 1")
SECTION=$(DB "select min(section_id) from sections")
AUTHOR_GROUP=$(DB "select min(user_group_id) from user_groups where role_id=65536 and context_id=1")
[ -n "$ISSUE" ] && [ -n "$SECTION" ] && [ -n "$AUTHOR_GROUP" ] || die "need a current issue, a section and an Author group in OJS"

echo "1. Author submits (wizard steps 1-4)"
r=$(post author $B/submission/saveStep/1 -d locale=en_US -d sectionId=$SECTION -d userGroupId=$AUTHOR_GROUP -d checklist-0=1 -d checklist-1=1 -d checklist-2=1 -d checklist-3=1 -d checklist-4=1 -d copyrightNoticeAgree=1 -d privacyConsent=1 -d commentsToEditor=e2e)
S=$(echo "$r" | grep -o 'submissionId=[0-9]*' | head -1 | cut -d= -f2); [ -n "$S" ] || die "step 1: $r"; ok "submission $S created"
r=$(api author -X POST $B/api/v1/submissions/$S/files -F fileStage=2 -F genreId=1 -F "name[en_US]=manuscript.pdf" -F "file=@$T/test.pdf;type=application/pdf"); expect "$r" '"id"' "file upload"
post author $B/submission/saveStep/2 -d submissionId=$S -o /dev/null
post author $B/submission/saveStep/3 -d submissionId=$S -d submissionVersion=1 --data-urlencode "title[en_US]=E2E Test: Federated Learning for Decision Support" --data-urlencode "abstract[en_US]=<p>Automated end-to-end workflow test article.</p>" -o /dev/null
r=$(post author $B/submission/saveStep/4 -d submissionId=$S); expect "$r" 'setStep' "step 4"
[ "$(DB "select submission_progress from submissions where submission_id=$S")" = 0 ] && ok "submitted" || die "not submitted"
P=$(DB "select publication_id from publications where submission_id=$S")

echo "2. Editor sends to review"
r=$(post editor "$CALL/modals/editor-decision/editor-decision/save-external-review" -d submissionId=$S -d stageId=1 -d decision=8 -d skipEmail=1 -d "selectedFiles[]=$(DB "select max(submission_file_id) from submission_files where submission_id=$S")" -d personalMessage=ok); expect "$r" 'status":true' "send to review"
[ "$(DB "select stage_id from submissions where submission_id=$S")" = 3 ] && ok "external review" || die "stage not 3"

echo "3. Editor assigns reviewer1"
post editor "$CALL/grid/users/reviewer/reviewer-grid/update-reviewer" -d reviewerId=$REVIEWER -d template=REVIEW_REQUEST -d skipEmail=1 -d responseDueDate=2027-01-10 -d reviewDueDate=2027-01-20 -d reviewMethod=2 -d selectionType=1 -d submissionId=$S -d stageId=3 -d reviewRoundId=$(DB "select max(review_round_id) from review_rounds where submission_id=$S") -d personalMessage=Please -o /dev/null
[ -n "$(DB "select review_id from review_assignments where submission_id=$S")" ] && ok "review assigned" || die "no assignment"

echo "4. Reviewer accepts and submits review"
R="$B/reviewer/saveStep/$S"
post reviewer1 "$R?step=1" -d privacyConsent=1 -d declineReview=0 -o /dev/null
post reviewer1 "$R?step=2" -o /dev/null
post reviewer1 "$R?step=3" --data-urlencode "comments=<p>Sound. Accept.</p>" -d recommendation=2 -o /dev/null
[ -n "$(DB "select date_completed from review_assignments where submission_id=$S and date_completed is not null")" ] && ok "review completed" || die "review not completed"

echo "5. Editor accepts, then sends to production"
post editor "$CALL/modals/editor-decision/editor-decision/save-promote?decision=1&submissionId=$S&stageId=3&reviewRoundId=1" -d submissionId=$S -d stageId=3 -d decision=1 -d reviewRoundId=$(DB "select max(review_round_id) from review_rounds where submission_id=$S") -d skipEmail=1 -d personalMessage=Accepted -o /dev/null
[ "$(DB "select stage_id from submissions where submission_id=$S")" = 4 ] && ok "copyediting" || die "not in copyediting"
post editor "$CALL/modals/editor-decision/editor-decision/save-promote?decision=7&submissionId=$S&stageId=4" -d submissionId=$S -d stageId=4 -d decision=7 -d skipEmail=1 -d personalMessage=Go -o /dev/null
[ "$(DB "select stage_id from submissions where submission_id=$S")" = 5 ] && ok "production" || die "not in production"

echo "6. Galley, issue, publish"
post editor "$CALL/grid/article-galleys/article-galley-grid/update-galley?submissionId=$S&publicationId=$P&representationId=" -d label=PDF -d galleyLocale=en_US -o /dev/null
GAL=$(DB "select max(galley_id) from publication_galleys where publication_id=$P"); [ -n "$GAL" ] && ok "galley $GAL" || die "no galley"
# Same endpoint the galley "Upload file" button uses; OJS links the file to the galley itself.
r=$(curl -s -b $T/editor -H "$X" -H "X-Csrf-Token: $(cat $T/editor.tok)" "$CALL/wizard/file-upload/file-upload-wizard/upload-file?submissionId=$S&stageId=5&fileStage=10&assocType=521&assocId=$GAL" -F genreId=1 -F "uploadedFile=@$T/test.pdf;type=application/pdf;filename=article.pdf"); expect "$r" 'status":true' "galley file upload: $r"
[ -n "$(DB "select submission_file_id from publication_galleys where galley_id=$GAL and submission_file_id is not null")" ] && ok "galley linked to its PDF" || die "galley not linked to file"
api editor -X PUT $B/api/v1/submissions/$S/publications/$P -H "Content-Type: application/json" -d "{\"issueId\":$ISSUE,\"pages\":\"601-612\"}" -o /dev/null
api editor -X PUT $B/api/v1/submissions/$S/publications/$P/publish -H "Content-Type: application/json" -d "{\"issueId\":$ISSUE}" -o /dev/null
[ "$(DB "select status from publications where publication_id=$P")" = 3 ] && ok "published" || die "not published"

echo "7. Public checks"
docker compose exec -T ojs sh -lc 'rm -rf cache/t_cache/*' >/dev/null
[ "$(curl -s -o /dev/null -w '%{http_code}' $B/article/view/$S)" = 200 ] && ok "article page 200" || die "article page"
curl -s $B/ | grep -q "E2E Test" && ok "listed on home page" || die "not on home page"
pdf=$(curl -sL -o /dev/null -w '%{http_code} %{content_type}' $B/article/download/$S/$GAL); [ "$pdf" = "200 application/pdf" ] && ok "PDF downloads" || die "PDF: $pdf"
curl -s "$B/oai?verb=ListRecords&metadataPrefix=oai_dc" | grep -q "E2E Test" && ok "in OAI-PMH" || die "not in OAI"

if [ $KEEP = 1 ]; then echo "Kept submission $S"; else
  api admin -X DELETE $B/api/v1/submissions/$S -o /dev/null; docker compose exec -T ojs sh -lc 'rm -rf cache/t_cache/*' >/dev/null
  [ "$(DB "select count(*) from submissions where submission_id=$S")" = 0 ] && ok "cleaned up submission $S" || die "cleanup"; fi
rm -rf "$T"; echo "E2E PASSED"
