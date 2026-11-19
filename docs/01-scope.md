# 01 — Scope

## Goal
Deliver a working, deployable OJS-3.3-style journal on shared hosting within the
hackathon window. It should look like the reference site to a visitor and give
judges a full **submit → review → decide → publish** demo across role accounts.

## Priorities
**P0 = must ship (demo breaks without it) · P1 = should ship · P2 = stretch**

### Public site
| Feature | Priority |
|---|---|
| Header with logo, primary nav (dropdowns), search link, Register/Login | P0 |
| Home page: journal description, announcements, current issue TOC | P0 |
| About the Journal (aims & scope, frequency, peer review, open access, archiving, self-archiving, indexing) | P0 |
| Editorial Board, APC, Privacy Statement, Contact pages | P0 |
| Ethics dropdown with 12 policy pages (CMS-managed static pages) | P0 |
| Current issue + Archives list + Issue TOC page | P0 |
| Article landing page (abstract, authors, affiliations, keywords, DOI, references, galley buttons, how-to-cite, license) | P0 |
| PDF galley viewer/download | P0 |
| Submissions info page (checklist, guidelines, copyright, privacy) | P0 |
| Sidebar blocks (custom HTML blocks, Make a Submission, Information, Web Feed) | P0 |
| Footer (journal name, eISSN, DOI, CC BY 4.0 notice, platform badge) | P0 |
| Register / Login / Forgot password / Reset | P0 |
| Announcements list + detail | P1 |
| Search (title, abstract, keywords, author) with filters | P1 |
| Article view/download counts | P1 |
| RSS/Atom feeds for current issue | P1 |
| Google Scholar meta tags (citation_*) on article pages | P1 |
| Information pages: For Readers / For Authors / For Librarians | P1 |
| Multilingual UI | out of scope |

### Back end
| Feature | Priority |
|---|---|
| Role-based dashboard ("Submissions" with My Queue / Unassigned / All Active / Archives tabs) | P0 |
| Author submission wizard (5 steps) | P0 |
| Editor: assign editor, send to review, decisions | P0 |
| Reviewer: invite, accept/decline, review form, upload, recommendation | P0 |
| Author: respond to revisions, upload revised files | P0 |
| Copyediting + Production stages (simple: discussions + file upload + galleys) | P0 |
| Issues: create, assign articles, order TOC, publish/unpublish issue | P0 |
| Journal settings: masthead, about text, contact, sections, checklist, guidelines, privacy, licence | P0 |
| Website settings: logo, theme colour, footer HTML, nav menu builder, static pages, custom sidebar blocks, announcements | P0 |
| Users & Roles: list, search, create, edit, assign roles, disable, login-as | P0 |
| Email notifications (PHPMailer SMTP) + editable email templates | P1 |
| Discussions (threaded messages per stage) | P1 |
| Statistics page (views/downloads/submissions by month) | P1 |
| Site Admin: site settings, journal list, system info, clear cache | P1 |
| Quick Submit (manager adds an already-published article directly) | P1 |
| APC payment tracking (manual: mark paid) | P2 |
| DOI auto-generation pattern (no Crossref deposit) | P2 |
| Import/export | out of scope |

## Demo accounts (seeded)
| Username | Role |
|---|---|
| admin | Site Administrator + Journal Manager |
| editor | Editor |
| sectioneditor | Section Editor |
| reviewer1, reviewer2 | Reviewer |
| author | Author |
| reader | Reader |
Password for all: `Demo@12345` (shown on the login page only when `DEMO_MODE=true`).

## Definition of done
- Deploys to cPanel by uploading files, creating a DB and running `/install.php`.
- A judge can register as an author, submit, and see the submission appear in the
  editor's queue. The full lifecycle can be walked to a published article in a live issue.
- Public pages visually match the reference screenshots at desktop and mobile widths.
- No PHP warnings with `display_errors=On`; all forms have CSRF protection; there is
  no SQL built with string concatenation.
