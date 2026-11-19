# 07 — Build Plan (phases + prompts to give Claude)

Give Claude the whole folder first, then send each phase prompt in turn. After each
phase, test it with the checklist before moving on. Commit to git after every phase.

---
### Phase 0 — Kickoff
> Read CLAUDE.md and every file in /docs. Then do the "Scraping the reference site"
> step in CLAUDE.md: fetch the live pages and produce /reference/html, /reference/css,
> /reference/inventory.json and /reference/SCRAPE_REPORT.md. Don't write app code yet.
> Reply with: (1) your understanding in 10 bullets, (2) any conflicts or gaps you see,
> (3) the final folder structure, (4) the questions you need answered.

### Phase 1 — Core + installer + DB
> Build Phase 1: app/bootstrap, Core classes (Router with PATH_INFO + query fallback,
> DB (PDO), View, Auth, Csrf, Settings, Lang, Upload, Sanitizer), install.php,
> install/schema.sql, install/seed.sql (journal, settings, demo users and roles,
> sections, 2 issues, 6 articles with placeholder PDF galleys, static pages, custom
> blocks, nav menu, announcements, email templates). Include README deploy steps.
**Test:** the installer runs on a fresh DB; `/index.php/jirs` returns 200 with a stub layout.

### Phase 2 — Public site
> Build Phase 2 per docs/02: the public layout (header, nav with dropdowns from the DB,
> breadcrumb, sidebar blocks from settings, footer), Home, About and its sub-pages,
> static pages, Current, Archives, Issue TOC, Article landing (with citation meta tags and
> How to Cite), galley view/download, Announcements, Information pages, Search, feeds.
> Match the screenshots in /reference/public. Make it responsive.
**Test:** compare every page side by side with the reference screenshots at 1440px and 390px.

### Phase 3 — Accounts
> Build Phase 3: Register (exact fields from docs/02), Login (username or email,
> remember me), logout, lost/reset password, profile tabs, and the back-end shell layout
> with role-aware sidebar, notifications bell and "Login As".
**Test:** register a new user → they get Reader+Author; the reviewer opt-in adds Reviewer.

### Phase 4 — Author submission
> Build Phase 4: the dashboard lists/tabs/filters and the 5-step author submission wizard
> (docs/03), saving after each step, SUBMISSION_ACK + SUBMISSION_NOTIFY_EDITORS emails
> (logged to email_log when there's no SMTP), and the author's view of their submission.
**Test:** the author submits; the editor sees it in Unassigned; the email shows in the outbox.

### Phase 5 — Editorial workflow + review
> Build Phase 5: the workflow page with stage tabs, file grids, participants, decision
> modals with editable emails, review rounds, add reviewer (with search), reviewer
> 4-step flow, review status tracking, author revisions upload, new rounds, discussions,
> activity log, notifications. Enforce permissions with PermissionService.
**Test:** walk the whole flow: send to review → 2 reviewers → revisions → round 2 →
accept. Log in as each role and check nobody sees what they shouldn't
(anonymous reviewer identity, other authors' submissions).

### Phase 6 — Copyediting, production, publication, issues
> Build Phase 6: the copyediting and production stages, the Publication tab (all
> sub-tabs), galleys, Schedule For Publication, Issues management (future/back, TOC
> ordering, publish issue, make current), and publishing so articles appear on the
> public site.
**Test:** an accepted article ends up in a published issue, with its PDF downloadable publicly.

### Phase 7 — Settings & admin
> Build Phase 7: every Settings screen in docs/04 §7–10 (including the navigation menu
> builder, sidebar manager, static pages, custom blocks, theme colour, logo, footer,
> email templates, checklist), Users & Roles, Statistics, Site Administration, email outbox.
**Test:** change the theme colour, logo, a menu item and a sidebar order, and confirm the
public site updates.

### Phase 8 — Polish & hardening
> Phase 8: run through a security checklist (CSRF, authz on every route, XSS through
> rich text, upload validation, SQL injection), fix PHP notices, add 404/403/500 pages,
> and tune the mobile layout, empty states, loading states and flash messages. Write
> DEMO.md with the judge walkthrough script.

---
## Tips for working with Claude on this
- Paste screenshots straight into the chat when asking for a page to be matched.
- When something's wrong, give the URL, what you expected, what happened and any error
  from `files/logs/app.log`.
- Keep phases small. If Claude's context gets long, start a new chat with CLAUDE.md +
  docs + "We're done with phases 1–N; now do phase N+1".
