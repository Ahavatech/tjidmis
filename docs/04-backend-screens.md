# 04 — Back-end Screens (logged-in)

We can't see the live admin, so this spec follows how OJS 3.3 is organised.
Recreate the **structure and flows**. The visual style should be clean and consistent:
OJS 3.3's back end uses a dark-navy left sidebar, a white top bar and white content
cards on a light grey background. When an author account is created on the live
site, screenshots of the author-side screens go in `/reference/backend/`; match those.

## Back-end shell
```
┌──────────┬──────────────────────────────────────────────────────┐
│ Journal  │ Top bar: journal name ▾ (switch journal)  🔔 Tasks(3) │
│ logo     │                       username ▾ (Profile, Logout)    │
├──────────┤                                     "View Site" link  │
│ Submissions│──────────────────────────────────────────────────────│
│ Issues   │  Page content                                         │
│ Announcements│                                                    │
│ Payments(P2)│                                                     │
│ Settings ▸ Journal / Website / Workflow / Distribution / Access(P2)│
│ Users & Roles│                                                    │
│ Tools ▸ Import/Export(stub) / Statistics                         │
│ Administration (site admin only)                                 │
└──────────┴──────────────────────────────────────────────────────┘
```
Menu items appear only if the role allows them. Authors and reviewers see just "Submissions".

## 1. Dashboard `/submissions`
Tabs (by role):
- **My Queue**: submissions assigned to me (editor) or my own (author)
- **Unassigned** (editor/manager): no editor assigned yet
- **All Active** (editor/manager)
- **Archives**: published + declined
Each list has a search box, **Filters** (stage, section, overdue, incomplete) and pagination.
List item: ID · authors (short) · title · stage badge (Submission/Review/Copyediting/
Production) · status sub-text ("Awaiting response from reviewers", "Revisions requested",
"Review overdue") · counters for open discussions · "View" button. Expanding a row shows
quick details. Authors get a "New Submission" button.
Reviewers' assignments show in My Queue as "Review: {title}", with the due date and a
"Begin review" / "Continue" button.

## 2. Submission workflow page `/workflow/index/{id}/{stage}`
Header: ID / author / title + **Activity Log**, **Preview** (P2), **Submission Library** (P2).
Top-level tabs: **Workflow** | **Publication**.

**Workflow tab**: stage tabs (Submission, Review, Copyediting, Production), each with:
- Files grid for the stage (Submission Files / Review Files / Revisions / Draft Files /
  Copyedited / Production Ready / Galleys). Each file shows its name, type and upload date,
  plus actions (download, edit name/type, delete, history).
- Review stage: round tabs (Round 1, Round 2...), a Reviewers grid, an Author Revisions grid.
- Discussions grid with "Add discussion".
- Right column: decision buttons (docs/03) and a **Participants** panel (grouped by role,
  with an "Assign" link and per-participant menu: Edit, Remove, Notify, Login As).

**Publication tab** (editors; authors read-only until they're asked to edit):
sub-tabs **Title & Abstract** · **Contributors** · **Metadata** (keywords, subjects,
languages, references) · **References** · **Galleys** (add PDF/HTML galley: label + file) ·
**Permissions & Disclosure** (copyright holder, year, license URL) · **Issue** (assign
issue, section, pages, URL path, cover image, date published) · **Identifiers** (DOI).
Status line "Status: Unscheduled/Scheduled/Published" + buttons Preview / Schedule For
Publication / Publish / Unpublish.

## 3. Author screens
- New Submission wizard (docs/03)
- Their workflow page: they see stage tabs but only their own files, decision emails
  (as "Editor decision" notifications), reviewer comments shared with the author
  (anonymised), the **Upload revised file** action in Review, and discussions.

## 4. Reviewer screens `/reviewer/submission/{id}`
Four steps:
1. **Request**: title, abstract, review type, review files list (hidden until accepted),
   dates (respond by / review due), competing interests radio + text, ☐ consent →
   **Accept Review, Continue to Step #2** / **Decline Review Request**.
2. **Guidelines**: reviewer guidelines from settings.
3. **Download & Review**: download files, review form (default: "For author and editor"
   and "For editor only" rich-text boxes; custom review forms are P2), upload reviewer
   files, recommendation dropdown, **Submit Review** with confirm.
4. **Completion**: thank-you message.

## 5. Issues `/manageIssues`
Tabs **Future Issues** / **Back Issues**. "Create Issue" modal: Volume, Number, Year,
Title, ☐ show volume/number/year/title, Description, Cover image, URL path.
Issue edit: **Table of Contents** (articles grouped by section, drag to reorder,
remove) · **Issue Data** · **Identifiers**. Actions: Preview, Publish Issue (☐ notify
readers), Unpublish, Delete, "Make Current Issue" for back issues.

## 6. Announcements
List + create/edit (type, title, short description, full text, expiry date, ☐ send email).

## 7. Settings › Journal
Tabs: **Masthead** (journal name, initials, abbreviation, publisher, ISSN online/print,
Summary, Editorial Team (HTML), About the Journal (rich HTML)), **Contact** (principal
contact name/email/phone/affiliation, technical support contact, mailing address),
**Sections** (title, abbreviation, policy, word count, ☐ require abstracts,
☐ submissions by editors only, ☐ peer reviewed, assigned section editors, order).

## 8. Settings › Website
Tabs:
- **Appearance**: Theme (fixed), Theme colour picker, Logo, Favicon, Homepage image,
  Additional Content (home), Page Footer (rich HTML), Sidebar management (two lists:
  Sidebar / Unselected, drag between), Journal style sheet (CSS upload).
- **Setup**: **Information** (For Readers/Authors/Librarians text), **Languages** (stub),
  **Navigation** (menu builder: edit Primary Navigation Menu and User Navigation Menu;
  items can be built-in pages, static pages, custom URLs or custom pages; nested one
  level; drag to reorder), **Announcements** (enable + intro text), **Lists**
  (items per page), **Privacy Statement**, **Date & time formats**.
- **Plugins**: we don't build a plugin system. This tab is a toggle list for our
  built-in features: Static Pages, Custom Blocks, Web Feeds, Google Scholar meta,
  Usage stats, ORCID field.
- **Static Pages**: list + add/edit (path, title, rich HTML content).
- **Custom Blocks**: list + add/edit (name, title, rich HTML, ☐ show title).

## 9. Settings › Workflow
- **Submission**: Disable submissions toggle, Author Guidelines, Submission Preparation
  Checklist (add/edit/order items), Metadata toggles (keywords, references, ORCID...),
  Components (file types list).
- **Review**: Default review mode, Default response/review deadline (weeks), Reviewer
  guidelines, Competing interests text, Reminder settings, Reviewer file upload toggle.
- **Publisher Library** (P2).
- **Emails**: Signature, bounce address, list of email templates with edit/reset/disable.

## 10. Settings › Distribution
**License** (copyright holder: author/journal/custom, licence URL dropdown incl. CC BY 4.0,
copyright year basis, licence terms), **Indexing** (search description, custom tags),
**Payments** (P2: currency, APC fee, ☐ enable), **Access** (open access only for MVP).

## 11. Users & Roles
- **Users** grid: name, username, email, roles; search + role filter; "Add User" modal
  (profile fields + roles checkboxes + ☐ send welcome email); row menu: Edit User,
  Edit roles, Email, Disable/Enable, Remove (from journal), **Login As** (with a session
  banner "Logged in as X — Log out as X").
- **Roles** grid: built-in roles with permission level + stages they can access
  (editable checkboxes per stage).
- **Site Access Options**: ☐ allow user registration, ☐ require consent.

## 12. Tools › Statistics
Cards + simple charts: abstract views, PDF downloads, submissions received/accepted/
declined, acceptance rate, days to first decision. Filter by date range. Top articles table.

## 13. Administration (site admin) `/admin`
Hosted Journals (create/edit/delete/order; journal path), Site Settings (site title,
logo, intro, default journal redirect, min password length), System Information
(PHP/MySQL versions, config values), Clear Data Caches / Clear Template Cache,
Expire User Sessions, Email outbox (logged emails).

## 14. Profile `/user/profile`
Tabs: Identity · Contact · Roles (shows roles; reviewer opt-in + reviewing interests) ·
Public (photo, bio, homepage, ORCID) · Password · Notifications (per-event email on/off) ·
API key (stub).
