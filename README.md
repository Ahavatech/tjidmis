# JIDMIS journal site: OJS 3.3 clone (custom PHP)

A school-project clone of https://jidmis.org/index.php/jidmis built as our own PHP 8.1 + MySQL app (no OJS install, no framework, PDO only).
It reproduces the OJS 3.3 markup, URLs (`/index.php/jidmis/article/view/182`), CSS, fonts and content of the reference site.
Local use only: not for public hosting under the real journal's identity.

## Run it locally (Docker, recommended)
```
docker compose up -d                          # our app (PHP 8.1 + Apache) and MariaDB
ADMIN_PASS='Your@Pass123' bash tools/dev_install.sh   # once; also resets the app database
```
Open http://localhost:8081/index.php/jidmis (admin account: `admin` and the password you gave). Data lives in Docker volumes and survives restarts.

The real OJS 3.3.0-22 can run beside it as a private reference for comparing behaviour (never the journal's identity on the public internet):
```
docker compose --profile reference up -d
ADMIN_PASS='Your@Pass123' bash tools/dev_install_ojs.sh   # once; creates the journal "jidmis"
```
OJS is at http://localhost:8082/jidmis. Our OAI endpoint (`/oai`) and sitemap (`/sitemap`) follow its output.

Without Docker: `php -S localhost:8000 router.php`, then open http://localhost:8000/install.php and enter a MySQL/MariaDB database and an admin account.

## Deploy to cPanel
1. Upload the project (Extract in `public_html`).
2. cPanel > MySQL Databases: create a DB and user (ALL PRIVILEGES).
3. cPanel > Select PHP Version: 8.1 with `pdo_mysql`, `mbstring`, `fileinfo`, `dom`, `gd`. Raise `upload_max_filesize` / `post_max_size` to at least 20M for manuscript uploads.
4. Create `/home/USER/journal_files` OUTSIDE `public_html` (writable); `public/` must be writable for profile photos.
5. Visit `/install.php`, finish the form, then delete `install.php` (it also locks itself with `install.lock`).
6. If PATH_INFO URLs 404, set `'url_mode' => 'query'` in `config.php`; with mod_rewrite use `'pretty_urls' => true`.

## What is where
- `app/` controllers, services, views (`Views/layouts/public.php` mirrors the OJS page template), `lang/en*.php` (all UI strings)
- `assets/css/theme.css` + `fontawesome.css` + `public/journals/1/styleSheet.css`: the reference site's CSS, loaded in the same order; `assets/js/lib/` is its jQuery/Bootstrap-dropdown/main.js
- `install/seed.sql`, `install/seed_demo.sql`: generated from the saved reference site by `python tools/import_reference.py`
- `tools/diff_page.py`: compares our rendered HTML with the saved original page, tag by tag
- `tools/crawl_reference.md`: how to fetch more article pages (about 30% have abstracts so far, the rest show title/authors/pages)

## Demo accounts (demo data only), password `Demo@12345`
editor, sectioneditor, reviewer1, reviewer2, author, reader, copyeditor, layouteditor, proofreader. The admin account is the one you create in the installer.
The login page matches the live site exactly and shows no credentials; the accounts are listed here and in `DEMO.md`.

## Status by phase
1 core + installer, 2 public site, 3 accounts, 4 dashboard + author submission wizard, 5 editorial workflow + peer review, 6 copyediting/production/publication/issues, 7 settings/users/statistics/admin: done.
8 polish and hardening: done (see the checklist below and `DEMO.md`).
9 stretch features: done. DOI pattern (Settings > Distribution > DOI: prefix, suffix pattern with %j %v %i %Y %a %p, optional auto-assign on publish) and manual APC tracking (Settings > Distribution > Payments, then a box on each submission's workflow page). No Crossref deposit and no payment gateway. Back-end screens checked at 390px (tables and tab bars scroll inside their cards). Remaining markup differences from the reference are deliberate (autocomplete and security attributes, the demo-accounts note, the PDF preview iframe source).

## Deploy and security checklist
1. Upload everything (including `vendor/`), run `install.php` once, then **delete `install.php`** (the installer also writes `install.lock`).
2. Keep `files_dir` outside the web root; make sure `public/.htaccess` is uploaded (it blocks script execution in uploads).
3. In `config.php`: `'debug' => false`, set `base_url` to your real https address (used in e-mail links).
4. Change or delete the demo accounts (`Demo@12345`).
5. Set the `smtp` block, then check it: `php tools/smtp_check.php you@example.org`.
6. Optional: `php tools/sanitizer_check.php` (XSS payloads), `php tools/audit_routes.php` (route list for review).
7. Regression: `BASE=http://host/index.php/jidmis ADMIN_PASS=... bash tests/smoke.sh` (needs curl; 134 checks, submit to publish).

## Also in place (after phase 9)
OAI-PMH endpoint and XML sitemap (same shapes as OJS), automated review reminders run from page requests like OJS's acron (Workflow > Review > Setup), the back-end menu is ordered like OJS (Submissions, Issues, Settings, Statistics, Tools, Administration) and Statistics has Articles, Editorial Activity, Users and Reports (CSV downloads).
Still different from OJS: the back-end look (own design), only the `oai_dc` metadata format, no Crossref deposit, English only.
