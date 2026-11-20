#!/usr/bin/env bash
# One-time (or repeatable) install of the journal app into the local docker setup. Run after `docker compose up -d`.
#   ADMIN_PASS='Your@Pass123' bash tools/dev_install.sh        (DEMO=0 to skip demo accounts)
# It wipes the app database `jidmis` and config.php/install.lock first, so it can be used to reset the app. The real OJS database is not touched.
set -e
cd "$(dirname "$0")/.."; export MSYS_NO_PATHCONV=1
ADMIN_PASS=${ADMIN_PASS:-Admin@12345}; ADMIN_USER=${ADMIN_USER:-admin}; ADMIN_EMAIL=${ADMIN_EMAIL:-admin@example.com}; DEMO=${DEMO:-1}; URL=${URL:-http://localhost:8081}
docker compose exec -T db mariadb -uroot -proot -e "DROP DATABASE IF EXISTS jidmis; CREATE DATABASE jidmis CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON jidmis.* TO 'journal'@'%';"
rm -f config.php install.lock
docker compose exec -T app sh -c 'rm -rf /var/journal_files/* ; chown www-data:www-data /var/journal_files'
for i in $(seq 1 30); do curl -s -o /dev/null "$URL/install.php" && break; sleep 1; done
D=$(mktemp -d); command -v cygpath >/dev/null && D=$(cygpath -m "$D"); J="$D/jar"; TOK=$(curl -s -c "$J" -b "$J" "$URL/install.php" | grep -o 'tok" value="[^"]*"' | cut -d'"' -f3)
OUT=$(curl -s -c "$J" -b "$J" "$URL/install.php" -d tok="$TOK" -d db_host=db -d db_name=jidmis -d db_user=journal -d db_pass=journalpass -d files_dir=/var/journal_files \
  --data-urlencode "site_title=Journal of Intelligent Decision Making and Information Science" -d admin_user="$ADMIN_USER" -d admin_email="$ADMIN_EMAIL" --data-urlencode admin_pass="$ADMIN_PASS" $( [ "$DEMO" = 1 ] && echo -d demo=1 ))
rm -rf "$D"
echo "$OUT" | grep -io 'class="err">[^<]*\|Installation complete' | head -3
echo "Open $URL/index.php/jidmis   (admin: $ADMIN_USER). Delete install.php before any public hosting."
