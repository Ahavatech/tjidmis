#!/usr/bin/env bash
# Create or remove the demo accounts in the LOCAL OJS 3.3 database (docker compose, db service, database "ojs").
#   bash tools/ojs_demo_users.sh create     (idempotent; password Demo@12345, override with PW=...)
#   bash tools/ojs_demo_users.sh remove     (removes every demo account except admin)
# The admin account is only given the Journal manager role and the demo password on "create".
set -eu
cd "$(dirname "$0")/.."; export MSYS_NO_PATHCONV=1
PW=${PW:-Demo@12345}
DB(){ docker compose exec -T db mysql -uroot -proot -N ojs -e "$1" </dev/null | tr -d '\r'; }
# username:role (matched against the group's English name)
USERS="editor:Journal editor
sectioneditor:Section editor
reviewer1:Reviewer
reviewer2:Reviewer
author:Author
reader:Reader
copyeditor:Copyeditor
layouteditor:Layout Editor
proofreader:Proofreader"
NAMES="editor sectioneditor reviewer1 reviewer2 author reader copyeditor layouteditor proofreader"
gid(){ DB "select g.user_group_id from user_groups g join user_group_settings s on s.user_group_id=g.user_group_id and s.setting_name='name' and s.locale='en_US' where g.context_id=1 and s.setting_value='$1' limit 1"; }

case "${1:-}" in
create)
  H=$(docker compose exec -T ojs php -r 'echo password_hash($argv[1],PASSWORD_BCRYPT);' "$PW" | tr -d '\r')
  [ -n "$H" ] || { echo "could not hash password"; exit 1; }
  DB "update users set password='$H' where username='admin'"
  m=$(gid "Journal manager"); [ -n "$m" ] && DB "insert ignore into user_user_groups (user_group_id,user_id) select $m,user_id from users where username='admin'"
  while IFS=: read -r u role; do
    g=$(gid "$role"); [ -n "$g" ] || { echo "no group named '$role'"; exit 1; }
    DB "insert into users (username,password,email,country,date_registered,date_last_login,disabled) select '$u','$H','$u@example.com','US',now(),now(),0 from dual where not exists (select 1 from users where username='$u')"
    DB "update users set password='$H' where username='$u'"
    id=$(DB "select user_id from users where username='$u'")
    DB "insert ignore into user_settings (user_id,locale,setting_name,assoc_type,assoc_id,setting_value,setting_type) values ($id,'en_US','givenName',0,0,'$u','string'),($id,'en_US','familyName',0,0,'Demo','string')"
    DB "insert ignore into user_user_groups (user_group_id,user_id) values ($g,$id)"
    echo "  ok   $u ($role)"
  done <<< "$USERS";;
remove)
  for u in $NAMES; do
    id=$(DB "select user_id from users where username='$u'"); [ -n "$id" ] || continue
    DB "delete from user_settings where user_id=$id; delete from user_user_groups where user_id=$id; delete from sessions where user_id=$id; delete from users where user_id=$id"
    echo "  removed $u"
  done;;
*) echo "usage: bash tools/ojs_demo_users.sh create|remove"; exit 1;;
esac
