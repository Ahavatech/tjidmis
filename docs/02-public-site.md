# 02 — Public Site

Reference: https://jidmis.org/index.php/jidmis (OJS 3.3, default-theme based).
Screenshots and saved HTML from the team go in `/reference/public/`.

## Global layout
```
[skip links: main content | main navigation | site footer]   (visually hidden until focus)
┌──────────────────────────────────────────────────────────────┐
│ HEADER (theme colour bg)                                      │
│  Logo/journal name (links home)          Register | Login     │
│  Primary nav: About▾ Current Archives Submissions Ethics▾     │
│               Announcements                      🔍 Search    │
└──────────────────────────────────────────────────────────────┘
│ Breadcrumb: Home / <Page>                                     │
├──────────────────────────────────────┬───────────────────────┤
│ MAIN CONTENT (~70%)                   │ SIDEBAR (~30%)        │
│                                       │ ordered blocks        │
├──────────────────────────────────────┴───────────────────────┤
│ FOOTER: page-footer HTML from settings + platform badge       │
└──────────────────────────────────────────────────────────────┘
```
- Responsive: below 992px the sidebar drops under the main content and the nav
  collapses into a hamburger. Dropdowns open on hover (desktop) or tap (mobile).
- Logged-in users see a user menu in place of Register/Login (Dashboard, View Profile,
  Logout). Users with back-end roles also see a "Dashboard" link.
- Theme colour, logo, favicon and footer HTML all come from settings.

## Primary navigation (editable in the admin menu builder; seeded as below)
- **About ▾**: About the Journal · Editorial Board · Article Processing Charge · Privacy Statement · Contact
- **Current**
- **Archives**
- **Submissions**
- **Ethics ▾**: 12 static pages (seed: Publication Ethics Statement, Conflict of Interests,
  Copyright and Licensing, Plagiarism Policy, Peer Review Policy, Authorship Policy,
  Informed Consent, Data Sharing Policy, Corrections & Retractions, Complaints & Appeals,
  Editorial Independence, Use of AI Tools)
- **Announcements**

## Sidebar blocks (seeded order; reorderable in admin)
1. Custom block "About the Journal": short summary
2. Custom block "Metrics": badge images (placeholder)
3. Custom block "Journal Information": table with Frequency, Acceptance rate, Review
   process (single-blind), Language, Open access, eISSN, DOI prefix
4. Custom block "APC Information" + **Make a Submission** button
5. Custom block "Paper Template": links to template.docx and copyright-form.docx
6. Custom block "Indexing": logo grid
7. Information block: For Readers · For Authors · For Librarians
8. Web Feed block: Atom · RSS2 · RSS1 icons

Custom blocks are rich-text HTML stored in the DB (sanitised on save with an allow-list).

## URL map (all under `/index.php/{journal}`)
| URL | Page |
|---|---|
| `/` or `/index` | Home |
| `/about` | About the Journal |
| `/about/editorialTeam` | Editorial team (renders the Editorial Board static page) |
| `/about/submissions` | Submissions info |
| `/about/privacy` | Privacy statement |
| `/about/contact` | Contact |
| `/{staticPath}` e.g. `/apc`, `/publication-ethics` | Static page |
| `/issue/current` | Current issue TOC |
| `/issue/archive` (+ `/issue/archive/2` for paging) | Archives |
| `/issue/view/{issueId}` | Issue TOC |
| `/article/view/{articleId}` | Article landing page |
| `/article/view/{articleId}/{galleyId}` | Galley viewer (PDF in iframe, with "Download" bar) |
| `/article/download/{articleId}/{galleyId}` | Force download |
| `/announcement` and `/announcement/view/{id}` | Announcements |
| `/search/search?query=...` | Search |
| `/information/readers` · `/authors` · `/librarians` | Info pages |
| `/gateway/plugin/WebFeedGatewayPlugin/atom` (also `rss2`, `rss`) | Feeds |
| `/user/register` · `/login` · `/login/signOut` · `/login/lostPassword` | Auth |
| `/submissions` | Back-end dashboard (logged in) |

The site root `/index.php` redirects to the default journal.

## Page specs

### Home
Journal description (from settings) → Announcements (latest 3, title + date + summary +
"Read more") → "Current Issue" heading → issue title "Vol. X No. Y (YYYY)" + published
date + cover image if present → issue TOC (same component as the Issue page) →
"View All Issues" button.

### Issue TOC component (used on Home, Current, Issue view)
Grouped by **section** (e.g. "Articles", "Review Articles"), with each section heading
shown once. Each article row:
- Title (link to article page)
- Authors (comma-separated, under the title)
- Page range (right-aligned, if set)
- Galley buttons (e.g. `PDF`, outlined button with icon)
- Optional: DOI line and views/downloads count

### Archives
Breadcrumb "Home / Archives", H1 "Archives", plain list of issue links in the format
**"Vol. {v} No. {n} (YYYY)"** (title suffix if present), newest first. Cover thumbnail
and description only if set. 25 per page with pagination.
Order: by published date desc, with a manual order override available in the admin.

### Article landing page
Breadcrumb Home / Archives / Vol X No Y / Articles. Main column:
- Title, subtitle
- Authors (name, ORCID icon if set, affiliation under each)
- DOI (linked https://doi.org/...)
- Keywords
- Abstract
- References (list)
Right column inside main (the OJS 3.3 "entry_details" layout):
- Cover image (if set)
- Galley buttons (PDF)
- Published date
- How to Cite (formatted APA by default; a dropdown switches to MLA/Chicago/Harvard/Vancouver)
- Issue link, Section
- License block (CC BY 4.0 badge + text)
- Metrics (views/downloads)
Head meta: `citation_title`, `citation_author`, `citation_publication_date`,
`citation_journal_title`, `citation_issn`, `citation_volume`, `citation_issue`,
`citation_firstpage`, `citation_lastpage`, `citation_doi`, `citation_pdf_url`,
`citation_keywords`, plus Dublin Core `DC.*`.

### Submissions info
Sections in order: "Make a Submission" (login/register prompt + button) · Submission
Preparation Checklist (numbered items from settings) · Author Guidelines (HTML from
settings, with template downloads) · per-section policies ("Articles: section default
policy") · Copyright Notice · Privacy Statement.

### Register (match OJS 3.3 exactly)
Note "Required fields are marked with an asterisk: *".
- **Profile**: Given Name*, Family Name, Affiliation*, Country* (full country dropdown)
- **Login**: Email*, Username*, Password*, Repeat password*
- **Privacy**: ☐ consent to data collection per privacy statement (link) — required to submit;
  ☐ notify me of new publications and announcements; ☐ "Yes, I would like to be contacted
  with requests to review submissions to this journal" → reveals a **Reviewing interests**
  tag input
- Buttons: Register · Login link
Every new account gets the Reader + Author roles. Ticking the reviewer box adds Reviewer.
After registering, show "Registration complete" with links: View Submissions · Make a new
Submission · Edit My Profile · Continue Browsing.

### Login
Username or Email, Password, "Forgot your password?", ☐ Keep me logged in, Login button,
Register link.

### Search
Search box + "Advanced filters" (Published after/before, Authors). Results show the article
TOC rows with the issue name. Use MySQL FULLTEXT on title/abstract/keywords; author is a LIKE match.

## Styling notes
- Base the CSS on the OJS 3.3 default theme: Noto Sans body and headings, ~14px base,
  generous whitespace, the header in the theme colour, nav text white, dropdowns white
  with dark text.
- Buttons: outlined in the theme colour, filled on hover. Sidebar blocks are separated
  by spacing and have small uppercase headings.
- Use CSS variables (`--theme-color`) so the admin colour picker restyles everything.
- Take exact colours and sizes from `/reference/public/*.png` and saved HTML/CSS when
  they are available.
