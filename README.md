# JIDMIS on Open Journal Systems

This repository is the deployable layer for a JIDMIS journal site running on
**PKP Open Journal Systems (OJS) 3.3.0-22**. OJS provides submissions, review,
editorial workflow, publishing, OAI-PMH and Google Scholar metadata. This repo adds
the JIDMIS look, the migrated content and a few helper scripts.

## What is here

| Path | Purpose |
|---|---|
| `ojs-theme/jidmis/` | The OJS theme plugin (child of the default theme): CSS, fonts, sidebar, nav JS. |
| `ojs-public/` | Files that go into OJS's `public/` folder: journal logo, homepage banner, favicon, sidebar images. |
| `deploy/site-setup.sql` | Navigation menu, static (ethics/APC) pages, journal text settings and data fixes. |
| `jidmis-ojs-import.zip` | OJS Native XML export: 26 issues, 76 articles, 76 PDFs. |
| `tools/ojs_post_import.php` | Restores the current-issue flag and the existing article DOIs after the import. |
| `tools/e2e_ojs.sh`, `tools/ojs_demo_users.sh`, `tools/dev_install_ojs.sh` | Local testing only. Never run these on a public site. |
| `docker-compose.yml`, `docker/` | Local OJS + MariaDB for development. |

## Deploying to cPanel

1. Install an official OJS 3.3.0-22 distribution on your host (PHP and extensions per
   PKP's requirements), HTTPS enabled. Create the database and the admin account.
   Set `files_dir` outside `public_html` and configure your own SMTP. Review PKP's
   security guidance: 3.3.0-22 is an older maintenance line.
2. Create the journal with URL path `jidmis`.
3. Upload `ojs-theme/jidmis` to `plugins/themes/jidmis` and run, from the OJS folder:
   `php tools/upgrade.php upgrade` (registers the theme).
4. Upload the contents of `ojs-public/` into OJS's `public/` folder (merge
   `site/` and `journals/1/`; check the journal's id if it is not 1).
5. Import the content into the empty journal (not idempotent, run it once):
   ```sh
   php tools/importExport.php NativeImportExportPlugin import /path/to/import.xml jidmis admin
   ```
   Unzip `jidmis-ojs-import.zip` first so `import.xml` and its `files` folder stay together.
   The importer may print filter-validation / tempnam warnings; check the counts
   before deciding anything, and never retry blindly.
6. Copy `tools/ojs_post_import.php` into OJS's `tools/` and run
   `php tools/ojs_post_import.php /path/to/import.xml jidmis 10.59543`.
7. Apply the site setup, replacing the two placeholders with your real URLs
   (`__BASE__` = site root, `__JURL__` = journal URL, no trailing slashes):
   ```sh
   sed 's#__JURL__#https://example.org/index.php/jidmis#g; s#__BASE__#https://example.org#g' \
     deploy/site-setup.sql | mysql -u USER -p OJS_DATABASE
   ```
   This selects the JIDMIS theme, creates the menu and static pages, and fixes the
   imported galleys and file genres so PDF buttons appear.
8. Clear OJS's `cache/t_cache` and `cache/t_compile`, then verify the home page,
   current issue, archive, several article pages, PDF downloads and `/oai`.
9. Create the real user accounts (editors, reviewers, authors) in OJS. Author/editor/
   reviewer logins are not part of the import. Back up the database and `files_dir`.

Do not deploy this under the real journal's identity unless you own it; see the
branding note in `CLAUDE.md`.

## Local development

```sh
docker compose up -d                          # OJS at http://localhost:8082
ADMIN_PASS='Your@Pass123' bash tools/dev_install_ojs.sh   # first time only
bash tools/ojs_demo_users.sh create           # demo accounts, password Demo@12345
bash tools/e2e_ojs.sh                         # submit -> review -> accept -> publish -> checks
```

The e2e script creates a test article, walks the whole workflow, checks the public
pages and deletes it again (`--keep` leaves it). Copy `ojs-public/*` into the
container's `public/` folder and apply `deploy/site-setup.sql` (with
`__BASE__=http://localhost:8082`, `__JURL__=http://localhost:8082/jidmis`) to get the
same site locally.

## Search and indexing

Article pages emit Google Scholar citation metadata (title, authors, date, abstract,
PDF URL, DOI). Keep articles and PDFs publicly crawlable, set the correct HTTPS base
URL, avoid `noindex` and password protection, and add the site to Search Console.
OAI-PMH is at `/jidmis/oai`. Metadata helps discovery but cannot guarantee inclusion
or ranking.
