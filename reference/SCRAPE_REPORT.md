# Scrape Report (Phase 0)

Source: https://jidmis.org/index.php/jidmis, captured 2026-11-19. The site shows a JS bot-check
interstitial to curl, so pages were fetched from a real in-app browser session after the check
cleared on its own (no CAPTCHA solving), one request per ~1.1 s, same-origin `fetch`.
For study only. Nothing here ships. Rebuild in our own templates and CSS.

## Captured: `/reference/html` (37 files, all real content)
home, about, editorialTeam, APCharges, privacy, contact, issue-current, issue-archive,
issue-38, issue-39, submissions, announcement, search (`?query=decision`), register, login,
lostPassword, info-readers/authors/librarians, article-182/183/209, galley-182-29,
galley-183-30, aboutPublishingSystem, and all 12 Ethics static pages
(pes, COI, CopyrightandLicensing, selfarchive, ArchivingDigitalVersion, pp, Unethicalbehaviour,
RetractionandCorrectionpolicies, Procedures, GEN, indexing_refund_disclaimer_policy, Disclaimer).

## Captured: `/reference/css`
stylesheet.css (compiled OJS default theme, 84 KB), journal-styleSheet.css (journal custom CSS),
fontawesome.css.

## Extracted: `/reference/inventory.json`
nav tree (About 5 children, Ethics 12 children), user nav, search link, 10 sidebar blocks,
structure/class names (header, nav, home, issue TOC, sidebar, footer), per-page headings,
breadcrumbs, forms (field names/types/required/options), article meta tags (`citation_*`, `DC.*`),
CSS colours, fonts, sizes, breakpoints, button rules.

## Not captured / gaps
- **Screenshots** (`/reference/public`) not taken. Take 1440px and 390px full-page shots; they win for visuals.
- **Back-end screens** are not on the public site (per CLAUDE.md, built from docs/04).
- Raw `<img>` assets, PDFs, logos: deliberately not downloaded (branding rule).
- Submission checklist: only 2 `<li>` detected by the regex; verify against `submissions.html`.
- The CSS was customised by the journal (gold `#d79f24` header, `Montserrat`/`Inter`). The stock OJS
  default theme uses Noto Sans and a different palette. Use the neutral structure; our theme colour is a setting.
- Reference site's journal name, ISSN, DOI, logos, editors and articles must NOT be reused.

## Mapping to our templates
| Reference | Our template |
|---|---|
| `header.pkp_structure_head`, nav | `app/Views/layouts/public.php` (header) |
| `pkp_structure_sidebar` blocks | `app/Views/partials/sidebar.php` + blocks table |
| `page_index_journal` | `app/Views/public/home.php` |
| `obj_issue_toc` | `app/Views/partials/issue_toc.php` |
| article page + `citation_*` meta | `app/Views/public/article.php` |
| register/login/lostPassword/search forms | `app/Views/public/{register,login,lostPassword,search}.php` |
| footer | `app/Views/layouts/public.php` (footer) |
