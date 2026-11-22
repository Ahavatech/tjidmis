#!/usr/bin/env bash
# One-time install of the real OJS 3.3.0-22 reference and creation of the journal "jidmis".
#   docker compose up -d ; ADMIN_PASS='Your@Pass123' bash tools/dev_install_ojs.sh
set -e
cd "$(dirname "$0")/.."; export MSYS_NO_PATHCONV=1
ADMIN_PASS=${ADMIN_PASS:-Admin@12345}; URL=${URL:-http://localhost:8082}
D=$(mktemp -d); command -v cygpath >/dev/null && D=$(cygpath -m "$D"); for i in $(seq 1 60); do [ "$(curl -s -o "$D/null" -w "%{http_code}" "$URL/")" != 000 ] && break; sleep 1; done
J="$D/jar"
curl -s -L -c "$J" -b "$J" -o "$D/null" "$URL/index.php/index/install"
curl -s -L -c "$J" -b "$J" -o $D/ojs_install_out.html "$URL/index.php/index/install/install" -d installing=0 -d adminUsername=admin --data-urlencode adminPassword="$ADMIN_PASS" --data-urlencode adminPassword2="$ADMIN_PASS" \
  -d adminEmail=editor@tjidmis.org -d locale=en_US -d "additionalLocales[]=en_US" -d clientCharset=utf-8 -d connectionCharset=utf8 -d databaseCharset=utf8 -d filesDir=/var/www/files \
  -d databaseDriver=mysqli -d databaseHost=db -d databaseUsername=journal -d databasePassword=journalpass -d databaseName=ojs -d oaiRepositoryId=ojs2.jidmis.org
grep -q "completed successfully" $D/ojs_install_out.html && echo "OJS installed" || { echo "OJS install did not report success (already installed?)"; }
curl -s -c "$J" -b "$J" -o $D/ojs_login.html "$URL/index.php/index/login"; CS=$(grep -o 'name="csrfToken" value="[^"]*"' $D/ojs_login.html | head -1 | cut -d'"' -f4)
curl -s -c "$J" -b "$J" -o "$D/null" "$URL/index.php/index/login/signIn" -d csrfToken="$CS" -d username=admin --data-urlencode password="$ADMIN_PASS"
curl -s -c "$J" -b "$J" -o $D/ojs_ctx.html "$URL/index.php/index/admin/contexts"; CS=$(grep -o 'csrfToken":"[^"]*"' $D/ojs_ctx.html | head -1 | cut -d'"' -f3)
curl -s -c "$J" -b "$J" -H "Content-Type: application/json" -H "X-Csrf-Token: $CS" -X POST "$URL/index.php/index/api/v1/contexts" -o $D/ojs_ctx_out.json \
  --data '{"name":{"en_US":"Journal of Intelligent Decision Making and Information Science"},"acronym":{"en_US":"JIDMIS"},"urlPath":"jidmis","primaryLocale":"en_US","supportedLocales":["en_US"],"supportedFormLocales":["en_US"],"supportedSubmissionLocales":["en_US"],"contactName":"Editor in Chief","contactEmail":"editor@tjidmis.org","supportEmail":"editor@tjidmis.org","enabled":true}'
CODE=$(curl -s -o "$D/null" -w "%{http_code}" "$URL/jidmis"); rm -rf "$D"
echo "OJS journal page: $CODE  ($URL/jidmis, admin / your password)"
