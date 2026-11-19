# 06 — Architecture & Shared-Hosting Deployment

## Folder layout (what gets uploaded to public_html)
```
public_html/
  index.php                 front controller (parses PATH_INFO)
  install.php               web installer (locks itself after a successful run)
  .htaccess                 optional pretty URLs + deny access to /app, /install
  config.sample.php → config.php (written by installer)
  assets/
    css/ public.css  backend.css
    js/  public.js   backend.js  (vanilla)
    img/ ...
    vendor/ (TinyMCE or Quill for rich text, SortableJS for drag-drop, Choices.js for tags)
  public/                   web-served user uploads: logos, cover images, block images
  app/
    bootstrap.php           autoload, config, DB, session, error handler
    Core/ Router.php Request.php Response.php DB.php View.php Auth.php Csrf.php
          Mailer.php Settings.php Validator.php Upload.php Sanitizer.php Lang.php
    Controllers/
      Public/ IndexController AboutController IssueController ArticleController
              SearchController AnnouncementController InformationController
              PageController(static) GatewayController(feeds) UserController LoginController
      Backend/ DashboardController SubmissionWizardController WorkflowController
              PublicationController ReviewerController FileController DiscussionController
              IssueManageController AnnouncementManageController SettingsController
              UsersController StatsController AdminController ProfileController
              NotificationController
    Models/ (thin repositories per table)
    Services/ WorkflowService (decisions/state transitions), ReviewService,
              EmailTemplateService, CitationService (APA/MLA/...), MetricsService,
              FeedService, PermissionService
    Views/
      layouts/ public.php backend.php
      public/...  backend/...  emails/...  partials/ (issue_toc, article_row, sidebar...)
    lang/en.php
  install/ schema.sql seed.sql seed_files/
  vendor/ (composer: phpmailer/phpmailer, ezyang/htmlpurifier)
files/   ← OUTSIDE public_html, e.g. /home/USER/journal_files (set in config)
```
Add `.htaccess` with `Deny from all` / `Require all denied` inside `app/`, `install/`
and `vendor/` in case the host serves them.

## Routing
`index.php/{journal}/{page}/{op}/{arg1}/{arg2}` maps to `Controller::op(args)`.
- `page` → controller via a route table (e.g. `about` → AboutController, `article` →
  ArticleController, `workflow` → WorkflowController).
- Unknown `page` → look for a static page whose path matches; otherwise 404.
- `$journal == 'index'` → site-level (admin, site home redirect).
- Helper `url($page, $op = null, $args = [], $query = [])` builds every link (no
  hard-coded URLs), and switches between `index.php/...` and pretty URLs via config.
- Back-end POSTs return JSON for modal/AJAX actions (`{status, content|redirect|errors}`)
  and use normal POST + redirect for full forms.

## Cross-cutting
- `Auth::require($roles, $journalId)` and `PermissionService::canAccessStage($user,
  $submission, $stageId)` are checked in every back-end controller method.
- HTML from rich-text editors goes through HTMLPurifier before it's saved.
- File downloads go through `FileController::download($id)`, which checks permission,
  then sends headers and uses `readfile()`. Galleys are public only once published.
- Errors: log to `files/logs/app.log`. Show a friendly 500 page when not in debug.
- Pagination helper, flash messages, old-input repopulation on validation errors.
- Timezone setting (default Africa/Lagos), dates formatted via settings.

## Installer (`install.php`)
1. Checks PHP version and extensions (pdo_mysql, mbstring, fileinfo, gd, zip optional, intl optional) and writable dirs.
2. Form: DB host/name/user/pass, files dir path (pre-filled with `dirname(__DIR__).'/journal_files'`),
   site title, admin username/email/password, ☐ load demo data.
3. Runs schema.sql (+ seed.sql), writes config.php, creates files dir structure, writes
   `install.lock`. If the lock exists, the installer refuses to run.

## Deploying to cPanel (put this in README.md)
1. Zip the project locally (including `vendor/`).
2. cPanel → File Manager → upload to `public_html` (or a subdomain folder) → Extract.
3. cPanel → MySQL Databases: create DB + user, add the user with ALL PRIVILEGES.
4. cPanel → Select PHP Version → 8.1; enable pdo_mysql, mbstring, fileinfo, gd, intl.
5. Create `/home/USER/journal_files` (outside public_html).
6. Visit `https://yourdomain/install.php` and complete the form.
7. (Optional) SMTP: create a mailbox in cPanel → put the host/port/user/pass in Admin →
   Site Settings → Email (stored in config.php).
8. Delete or rename `install.php` (or rely on the lock).
- If PATH_INFO URLs 404 on the host, set `'url_mode' => 'query'` in config, which
  falls back to `index.php?route=jirs/about`.
- If mod_rewrite is available, set `'pretty_urls' => true` for `/jirs/about`.

## Local development
Use `php -S localhost:8000` from the project root with a `router.php` that serves real
files and passes everything else to `index.php`, and set PATH_INFO. XAMPP/Laragon
work too.
