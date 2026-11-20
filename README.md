# JIDMIS journal site: OJS 3.3 clone (custom PHP)

A school-project clone of https://jidmis.org/index.php/jidmis built as our own PHP 8.1 + MySQL app (no OJS install, no framework, PDO only).
It reproduces the OJS 3.3 markup, URLs (`/index.php/jidmis/article/view/182`), CSS, fonts and content of the reference site.
Not for public hosting under the real journal's identity.

## Run it locally
```
php -S localhost:8000 router.php
```
Open http://localhost:8000/install.php, enter a MySQL/MariaDB database and an admin account, tick "Load demo data" (issues, articles, demo users).
Then visit http://localhost:8000/index.php/jidmis

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
Set `'demo_mode' => false` in `config.php` to hide the credentials box on the login page.

## Status by phase
1 core + installer, 2 public site, 3 accounts, 4 dashboard + author submission wizard, 5 editorial workflow + peer review, 6 copyediting/production/publication/issues, 7 settings/users/statistics/admin: done.
8 polish and hardening: done (see the checklist below and `DEMO.md`).

## Deploy and security checklist
1. Upload everything (including `vendor/`), run `install.php` once, then **delete `install.php`** (the installer also writes `install.lock`).
2. Keep `files_dir` outside the web root; make sure `public/.htaccess` is uploaded (it blocks script execution in uploads).
3. In `config.php`: `'debug' => false`, `'demo_mode' => false`, set `base_url` to your real https address (used in e-mail links).
4. Change or delete the demo accounts (`Demo@12345`).
5. Set the `smtp` block, then check it: `php tools/smtp_check.php you@example.org`.
6. Optional: `php tools/sanitizer_check.php` (XSS payloads), `php tools/audit_routes.php` (route list for review).
7. Regression: `BASE=http://host/index.php/jidmis ADMIN_PASS=... bash tests/smoke.sh` (needs curl; 52 checks, submit to publish).
