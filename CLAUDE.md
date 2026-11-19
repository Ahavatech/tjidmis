# Project: OJS-style Journal Platform (custom PHP build)

## What we are building
A custom PHP + MySQL web app that reproduces the look, URLs and behaviour of an
**Open Journal Systems (OJS) 3.3** journal site, using https://jidmis.org/index.php/jidmis
as the visual and structural reference. We are NOT installing OJS. We are writing
our own code for a hackathon.

It covers:
1. The **public journal site**: home, about, current issue, archives, article pages,
   submissions info, ethics pages, announcements, search, register and login.
2. The **logged-in back end**: the author submission wizard, the editor/reviewer
   workflow, the journal manager settings and the site admin.

## Branding rule (important)
Match the layout, structure, menus, colours and behaviour. The journal name, logo,
ISSN, DOI prefix, editorial board and articles must be **our own placeholders**,
stored in settings and editable from the admin. Never hard-code the reference
journal's name, logos, ISSN/DOI or article content.
Seed name: "Journal of Interdisciplinary Research Sciences (JIRS)". Change it in settings.

## Scraping the reference site (do this in Phase 0, before writing code)
Get everything structural straight from https://jidmis.org/index.php/jidmis. Don't
rely on descriptions alone.
- **Fetch** with your web-fetch tool, or `curl -sL -A "Mozilla/5.0"`, at most one
  request per second. Save raw HTML to `/reference/html/{page}.html`. Fetch the linked
  CSS too, saving it to `/reference/css/`, for study only.
- **Pages to fetch**: home, `/about`, every About and Ethics dropdown target, `/issue/current`,
  `/issue/archive`, 2 issue pages, 2 article pages, 1 galley page, `/about/submissions`,
  `/announcement`, `/search/search?query=decision`, `/user/register`, `/login`,
  `/login/lostPassword`, `/information/readers|authors|librarians`.
- **Extract into `/reference/inventory.json`**:
  - the full nav menu tree (labels, order, nesting) and every static-page path
  - sidebar block order, plus each block's heading and kind of content
  - the HTML structure and class names of the header, nav, breadcrumb, issue TOC,
    article page, sidebar and footer
  - every form field (label, name, type, required, options)
  - About-page section headings and the submission checklist structure
  - colours (theme hex, link, button, text), fonts, font sizes, spacing and breakpoints
    from the CSS
  - the meta tags used on article pages
- **Rebuild, don't copy**: Recreate the HTML structure and styling in our own templates
  and CSS so the result looks and behaves the same. Don't ship their CSS, JS or images.
- **Never take** the journal's name, abbreviation, logos, ISSN/eISSN, DOI prefix,
  editorial-board names, indexing or metric badges, article titles, abstracts, PDFs or
  policy wording. For headings and labels, use the same titles ("Peer Review Process",
  "Plagiarism Policy"). For body text, write short original placeholder content.
- Write a `/reference/SCRAPE_REPORT.md` listing what was captured, anything that
  couldn't be fetched, and how each item maps to our templates.

## Hard constraints
- **Hosting: cPanel shared hosting.** No Docker, no Node server, no root, no cron
  guarantees, no long-running processes.
- **PHP 8.1, MySQL 5.7+/MariaDB 10.4+**, PDO only.
- **No framework.** Write a small PHP front controller with simple MVC. Composer is
  allowed for a few libraries (PHPMailer). Commit `vendor/` so the app uploads as plain files.
- **URLs must mirror OJS:** `/index.php/{journalPath}/{page}/{op}/{args...}`
  (e.g. `/index.php/jirs/article/view/12`). Use PATH_INFO. Also provide an optional
  `.htaccess` for pretty URLs.
- **Uploaded files** go to a directory OUTSIDE the web root, set in `config.php`
  (`files_dir`). Serve them only through a PHP controller that checks permissions.
- **Security:** CSRF tokens on every POST, `password_hash`/`password_verify`,
  prepared statements everywhere, output escaping (`e()` helper), and role checks
  on every back-end route. Session cookies are HttpOnly + SameSite=Lax. Validate
  uploads by extension and MIME, and rename stored files.
- **No JS framework.** Use vanilla JS plus the occasional small library from a CDN.
  Server-rendered PHP templates. The UI must work without JS where practical.

## Documents (read in this order)
1. `docs/01-scope.md`: goals, MVP vs stretch, definition of done
2. `docs/02-public-site.md`: every public page, layout, menus, sidebar, URL map
3. `docs/03-roles-workflow.md`: roles, permissions, submission lifecycle, emails
4. `docs/04-backend-screens.md`: every logged-in screen (author/editor/reviewer/manager/admin)
5. `docs/05-database.md`: schema
6. `docs/06-architecture-deploy.md`: folder layout, routing, installer, shared-hosting deploy
7. `docs/07-build-plan.md`: phased build order and the prompt for each phase
8. `docs/08-recon-checklist.md`: what the team captures from the live site (screenshots go in `/reference`)

## Working rules for Claude
- Build phase by phase (docs/07). At the end of each phase, list what was done, how
  to test it and any open questions. Then stop.
- If `/reference` has screenshots or saved HTML for a page, match them closely
  (spacing, fonts, colours, block order). Where the docs and screenshots disagree,
  screenshots win for **visuals** and docs win for **behaviour**.
- The back-end screens are specified from knowledge of how OJS 3.3 works, not
  from the live site. Follow docs/04 and keep the UI clean and consistent.
- Keep every user-facing string in `lang/en.php`. Don't hard-code strings in templates.
- Seed data: one journal, the role accounts listed in docs/01, 2 issues and 6 articles
  with placeholder PDFs.
