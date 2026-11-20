# Demo walkthrough (about 8 minutes)

Site: `/index.php/jidmis`. Demo accounts (password `Demo@12345`): editor, sectioneditor, reviewer1, reviewer2, author, reader, copyeditor, layouteditor, proofreader. Admin uses the installer password.

1. **Public site** (not logged in): home, About, Current Issue, Archives, open an article and its PDF galley, Search, Announcements. Point out the markup and styling match the live journal.
2. **Author**: log in as `author` > New Submission. Walk the 4 steps (checklist, upload, metadata and contributors, confirm).
3. **Editor**: log in as `editor`. The new item is in *Unassigned*. Open it, assign a section editor, then *Send to Review* and add `reviewer1`.
4. **Reviewer**: log in as `reviewer1` > accept the request, submit a review with a recommendation.
5. **Editor**: read the review, record *Accept Submission*, then *Send to Production*.
6. **Production**: as `layouteditor` (or editor) add a PDF galley in the Publication tab.
7. **Issue**: Issues > create a future issue, schedule the article to it, then publish the issue.
8. **Public again**: the article now appears on the home page and issue page and its PDF downloads anonymously.
9. **Admin** (`admin`): Settings > Website > Appearance, change the theme colour and footer, reload the public site. Also show Users and Roles, Statistics and Site Administration.
10. **Security talking points**: CSRF on every POST, role checks on each route, uploads stored outside the web root and served through a permission-checked controller, sanitised rich text, login throttling.

Automated check of steps 2 to 9: `bash tests/smoke.sh`.
