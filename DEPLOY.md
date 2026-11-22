# Deploying JIDMIS on cPanel (OJS 3.3.0-22)

Everything below runs on shared cPanel hosting. You need **SSH or cPanel's Terminal** (for the
import and plugin registration) and a domain with **HTTPS**. Replace `example.org`, `USER` and
the passwords with your own values.

## 0. Before you start

- **PHP:** in cPanel > *MultiPHP Manager* choose **PHP 7.4 – 8.1** for the domain (8.2 also ran locally,
  but 8.1 is the safest for 3.3.0-22). Required extensions: `mysqli`, `curl`, `mbstring`, `xml`/`dom`,
  `zip`, `gd` (or `imagick`), `intl`, `openssl`, `fileinfo`, `json`. Set `memory_limit` 256M+,
  `upload_max_filesize` / `post_max_size` 64M+ (*MultiPHP INI Editor*).
- **Database:** MySQL 5.7+ or MariaDB 10.4+ (utf8).
- **From this repo you need:** `ojs-theme/`, `ojs-public/`, `deploy/site-setup.sql`,
  `jidmis-ojs-import.zip`, `tools/ojs_post_import.php`. Do **not** upload `tools/e2e_ojs.sh`,
  `tools/ojs_demo_users.sh`, `tools/dev_install_ojs.sh`, `docker*` (they are for local testing and
  the demo script creates accounts with a known password).

## 1. Create the database

cPanel > *MySQL® Databases*: create a database (e.g. `USER_ojs`), a user with a strong password,
and add the user to the database with **ALL PRIVILEGES**. cPanel prefixes the names with your account
name; use the full names below.

## 2. Install OJS

1. Download `ojs-3.3.0-22.tar.gz` from <https://pkp.sfu.ca/software/ojs/download/archive/>.
2. Upload and extract it into the web root of the domain (`public_html`, or the subdomain's folder) so
   `index.php` and `config.inc.php` sit directly in it.
3. Create a files directory **outside** the web root: `/home/USER/ojs_files` (writable by PHP, mode 755).
4. Make these writable during install: `config.inc.php`, `cache/` (and sub-folders), `public/`.
5. Open `https://example.org/index.php/index/install` and fill in:
   - Admin username/password/email (use a strong password; the email is `editor@tjidmis.org`).
   - Locale `English`.
   - **Files directory:** `/home/USER/ojs_files`
   - Database driver `MySQLi`, host `localhost`, the DB name/user/password from step 1.
     Leave "Create new database" **unticked**.
   - OAI repository ID: `ojs2.example.org` (any unique id).
6. After a successful install, tighten permissions: `chmod 640 config.inc.php` (or 600).

## 3. Create the journal

*Administration > Hosted Journals > Create Journal*: path **`jidmis`**, title "Journal of Intelligent
Decision Making and Information Science", acronym JIDMIS, enable it. Note the journal's numeric ID (it is
`1` on a fresh install; `public/journals/1` below must match it).

## 4. Upload the theme, images and register the theme

```sh
# from your computer
scp -r ojs-theme/jidmis USER@example.org:public_html/plugins/themes/jidmis
scp -r ojs-public/* USER@example.org:public_html/public/        # merges site/ and journals/1/
```

(Or upload the same folders with cPanel *File Manager*.) Then on the server, from the OJS folder:

```sh
php lib/pkp/tools/installPluginVersion.php plugins/themes/jidmis/version.xml
```

If that command is rejected, run `php tools/upgrade.php upgrade` instead; it registers the theme too.
Make sure the `public/` folder and everything in it is writable by PHP.

## 5. Import the content

Upload `jidmis-ojs-import.zip` (61 MB: use SFTP, not the web uploader), unzip it so `import.xml` and the
`files/` folder stay together, then run **once**, on the empty journal:

```sh
php tools/importExport.php NativeImportExportPlugin import /home/USER/import/import.xml jidmis admin
```

`admin` is your admin username. The importer may print filter-validation or `tempnam` warnings; check the
results (26 issues, 76 articles) before doing anything else, and **never run it twice**.
Then restore the current-issue flag and DOIs:

```sh
cp /path/to/repo/tools/ojs_post_import.php tools/
php tools/ojs_post_import.php /home/USER/import/import.xml jidmis 10.59543
```

## 6. Apply the site setup (menu, pages, settings, theme)

`deploy/site-setup.sql` creates the navigation menu, the ethics/APC pages, the journal text settings,
selects the JIDMIS theme, and fixes imported galleys/file types so the PDF buttons show. Fill in your URLs
(`__JURL__` = journal URL, `__BASE__` = site root, no trailing slashes). **If you use pretty URLs (step 7)
the journal URL is `https://example.org/jidmis`, otherwise `https://example.org/index.php/jidmis`.**

Git Bash / Linux / macOS:

```sh
sed 's#__JURL__#https://example.org/jidmis#g; s#__BASE__#https://example.org#g' deploy/site-setup.sql > setup.sql
```

Windows PowerShell:

```powershell
(Get-Content deploy/site-setup.sql -Raw -Encoding UTF8).Replace('__JURL__','https://example.org/jidmis').Replace('__BASE__','https://example.org') | Set-Content setup.sql -Encoding UTF8
```

Import `setup.sql` with cPanel *phpMyAdmin* (select your database > *Import*), or:
`mysql -u USER_dbuser -p USER_ojs < setup.sql`. If the journal ID is not 1 the `journal_id=1` rows in the file
must be edited first (search/replace `,1,` carefully, or ask for help).

## 7. Configure `config.inc.php`

Edit these keys (keep the rest as installed):

```ini
[general]
base_url = "https://example.org"        ; or https://example.org/jidmis if you serve only this journal there
restful_urls = On                       ; pretty URLs, requires the .htaccess below
allowed_hosts = '["example.org","www.example.org"]'
time_zone = "Africa/Lagos"              ; your zone, https://www.php.net/timezones
show_upgrade_warning = Off
enable_beacon = Off                     ; do not report to PKP

[security]
force_ssl = On                          ; HTTPS only
salt = "PUT-A-LONG-RANDOM-STRING-HERE"  ; the installer sets one; do not leave the default text
api_key_secret = "ANOTHER-LONG-RANDOM-STRING"
session_cookie_name = OJSSID

[debug]
show_stacktrace = Off
display_errors = Off
```

Pretty URLs: create `public_html/.htaccess` (or append):

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,L]
</IfModule>
```

Then clear the caches: delete the contents of `cache/t_cache`, `cache/t_compile`, `cache/opcache`
(keep the folders). Re-do after any config/theme change.

## 8. Email

OJS sends editorial mail (review requests, decisions, password resets, registration). On shared hosting use
**authenticated SMTP from a mailbox on your own domain**; PHP `mail()` often lands in spam.

1. cPanel > *Email Accounts*: create e.g. `editor@tjidmis.org` (strong password). Note the mail server
   shown under *Connect Devices* (usually `mail.example.org`).
2. cPanel > *Email Deliverability*: make sure **SPF and DKIM** are valid for the domain (click *Repair* if
   not). Add a DMARC record (`_dmarc` TXT, e.g. `v=DMARC1; p=none; rua=mailto:editor@tjidmis.org`).
3. In `config.inc.php`, `[email]` section:

```ini
[email]
smtp = On
smtp_server = mail.example.org
smtp_port = 465
smtp_auth = ssl                          ; port 587 -> tls
smtp_username = editor@tjidmis.org
smtp_password = "the-mailbox-password"
allow_envelope_sender = On
default_envelope_sender = editor@tjidmis.org
force_default_envelope_sender = On       ; avoids SPF/DMARC failures
; If users register with other domains (gmail etc.) also set:
force_dmarc_compliant_from = On
dmarc_compliant_from_displayname = '%n via %s'
```

   Keep `config.inc.php` at mode 640/600 since it now holds the password. If your host blocks outbound
   SMTP on 465/587, use the host's documented relay or ask support to unblock.
   *Gmail/Workspace:* use an App Password (2-step verification on) with `smtp.gmail.com`, port 465, `ssl`, or
   the XOAUTH2 settings shown in the file's comments.
4. `deploy/site-setup.sql` already sets `editor@tjidmis.org` as the principal contact, the technical support contact,
   the envelope sender (journal level) and the site contact. Check it in OJS (as admin/manager):
   - *Settings > Journal > Contact*: principal contact email (this is the **From/Reply-To** of
     system mail) and the technical support contact; set the real names there.
   - *Settings > Workflow > Emails*: review and edit each template (review request, decision,
     registration) and the **email signature**; leave "Bcc" empty.
   - *Settings > Website > Setup*: nothing else is needed for mail.
5. **Test it:** log out, use *Login > Forgot your password* with a real account, and check it arrives (look
   in spam, and cPanel > *Track Delivery* if not). Then submit a test article and confirm the editor gets the
   notification. Check *Administration > System Information* and the PHP error log if nothing is sent.

## 9. Users, registration and roles

- **Real accounts:** *Settings > Users & Roles > Users > Add User* for editors, section editors,
  reviewers. Author/editor/reviewer logins were **not** imported. Never run `ojs_demo_users.sh` on
  the live site.
- **Self registration:** *Users & Roles > Roles*: edit **Author** and **Reviewer** and tick *Allow
  registration* so visitors can register (Register/Login links already show in the header).
- **Journal manager:** give your own account the Journal manager and Editor roles.
- **Sections** (*Settings > Workflow > Submission > Sections*): confirm "Articles" exists and who edits it.
- **Submission checklist / guidelines / privacy statement / license:** *Settings > Workflow* and
  *Settings > Distribution*; the imported text is already there, review it.
- **DOI:** *Settings > Distribution > DOIs* should show prefix `10.59543`; existing DOIs were restored by
  the post-import script.
- **Theme:** *Website > Appearance > Theme* must show **JIDMIS** (set by the site-setup SQL).
- **Scheduled tasks:** the default (Acron plugin) runs on page loads, which is fine for shared hosting. For
  reliable reminders use cron: add `0 * * * * /usr/local/bin/php /home/USER/public_html/tools/runScheduledTasks.php`
  and set `scheduled_tasks = On` in `[general]`.

## 10. Verify

1. Home page: banner, About section, sidebar, nav (About and Ethics dropdowns), footer.
2. *Current*, *Archives*, a few article pages, PDF download, and `/oai`.
3. Mobile width: menu opens (gold) and dropdowns are readable.
4. Log in as admin; open Submissions and Settings; add a test submission end-to-end and delete it afterward.
5. Email test (step 8.5).
6. Google Scholar tags: view source of an article page and look for `citation_title`, `citation_pdf_url`.

## 11. After going live

- Back up the **database and `ojs_files`** (cPanel > *Backup* / a scheduled mysqldump + files copy) and
  test a restore.
- Add the site to Google Search Console, submit `https://example.org/jidmis/oai` and the sitemap if you want.
- OJS 3.3.0-22 is an older maintenance release; watch PKP security advisories and plan upgrades.
- Revoke any personal access tokens that were ever stored in a git remote URL, and never commit
  `config.inc.php`.

## Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| Blank page or HTTP 500 | PHP version/extension missing; enable error logging (`[debug]`) briefly and read the PHP error log. |
| Page is unstyled or `css?name=stylesheet` returns 500 | Theme folder incomplete: re-upload all of `plugins/themes/jidmis` (including `styles/`, `css/`, `js/`, `fonts/`) and clear `cache/t_compile`. |
| Theme missing in Appearance | Run `installPluginVersion.php` (step 4) and re-check the folder name `jidmis`. |
| Sidebar images broken | `ojs-public/site` not uploaded to `public/site`, or wrong permissions. |
| No PDF buttons | Site-setup SQL (step 6) not applied. |
| Menu has no ethics pages | Site-setup SQL applied with wrong journal id, or cache not cleared. |
| URLs without `index.php` give 404 | `.htaccess` rewrite missing or `AllowOverride` disabled; use `restful_urls = Off` and the `index.php` URLs instead (then use `https://example.org/index.php/jidmis` as `__JURL__`). |
| Emails not arriving | SMTP creds/port; SPF/DKIM; `force_default_envelope_sender = On`; check spam and *Track Delivery*. |
