# JIDMIS on Open Journal Systems

The application to host is PKP Open Journal Systems (OJS) 3.3.0-22, not the
custom PHP clone in this repository. OJS is the publishing platform used by the
reference journal and provides the submission, editorial, review, publication,
OAI-PMH, and Google Scholar metadata workflows.

The custom PHP source is being retained temporarily as a rollback/reference
copy until the shared-host OJS installation and its backup are confirmed. Do
not upload or expose that clone as the journal site.

## JIDMIS OJS theme

The installable OJS 3.3 theme is in [ojs-theme/jidmis](./ojs-theme/jidmis).
It inherits OJS's default theme behavior and applies the clone's project-owned
public styling, so submissions, issue pages, article pages, and OJS metadata
remain native.

For local use, start OJS with the `reference` profile; Docker mounts the theme
into the OJS theme directory. In OJS, open **Website > Appearance**, select
**JIDMIS**, and enable **Show journal description on homepage**. Keep the
homepage image setting off as a header background so it remains in the page
content.

For cPanel, upload `ojs-theme/jidmis` to
`plugins/themes/jidmis`, then activate it through the OJS Website settings.
Upload the theme's included stylesheet and font assets with the plugin
directory. Do not upload the custom PHP app as a second public site.

## Local OJS

Start the database and OJS services without starting the legacy PHP app:

```powershell
docker compose --profile reference up -d db ojs
```

Open `http://localhost:8082/jidmis`. The existing local OJS database already
contains the migrated content. Do not run `tools/dev_install_ojs.sh` against
that database; it is only for a fresh local installation.

## Imported content

The [OJS migration package](./jidmis-ojs-import.zip) contains the Native XML
export and its associated PDFs. It preserves 26 published issues, 76 published
articles, and 76 article PDFs. Twenty-five issues have three articles; the
remaining issue has one. The OJS import was checked against the issue archive,
current issue, article page, PDF download, and OAI-PMH Identify response.

Article author names and metadata are imported, but author/editor/reviewer
login accounts and unpublished workflow records are not. Create the required
OJS users and roles after installation.

## Deploying to cPanel

1. Use an official PKP OJS distribution, configure it for the assigned HTTPS
   test domain, and create its MySQL/MariaDB database and administrator account.
   Verify the host's PHP version and required extensions against the exact OJS
   release. OJS 3.3.0-22 is an older maintenance line; review current PKP
   security guidance before exposing it publicly.
2. Set OJS `files_dir` to a writable directory outside `public_html`. Configure
   the assigned test-domain SMTP account in OJS; do not reuse the live journal's
   mail credentials. Do not use `localhost` URLs in the deployed OJS config.
3. Import only into an empty OJS journal. Upload and extract
   `jidmis-ojs-import.zip` so `import.xml` and its sibling `files` directory
   remain together. From the OJS installation directory, run:

   ```sh
   php tools/importExport.php NativeImportExportPlugin import /path/to/import.xml jidmis admin
   ```

   Replace `admin` with the OJS administrator username. The import is not
   idempotent; do not run it a second time on an already populated journal.
   The local OJS 3.3 importer printed filter-validation/tempnam warnings even
   though records and files persisted. If these occur, verify the OJS counts
   and pages before deciding what to do; never retry the import blindly.
4. Copy [ojs_post_import.php](./tools/ojs_post_import.php) into OJS's `tools/`
   directory, then run:

   ```sh
   php tools/ojs_post_import.php /path/to/import.xml jidmis 10.59543
   ```

   This restores the current-issue flag and the 44 existing publication DOI
   identifiers. The source contains both `10.59543` and legacy `10.17762` DOI
   prefixes; the third argument configures the journal's active prefix without
   changing those existing identifiers.
5. Verify the current issue, archive, several article pages and PDF downloads,
   then back up the OJS database and `files_dir` before removing any legacy
   source or data.

## Search and indexing

The local OJS article page emits Google Scholar citation metadata for the title,
authors, date, abstract, PDF URL, and stored DOI. Keep article pages and PDFs
publicly crawlable, set the correct canonical HTTPS base URL, avoid password
protection or `noindex`, and add the test-domain property in Search Console.
OAI-PMH is available at `/jidmis/oai`. OJS metadata helps discovery but cannot
guarantee Google Scholar inclusion or ranking.

Do not point the test installation at `jidmis.org` or present it as the live
journal. Keep the assigned test domain and project authorization in place.
