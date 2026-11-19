# 08 — Recon Checklist (what the team captures from the live site)

Save everything into `/reference` before Phase 2. Claude matches visuals far more
accurately from screenshots and saved HTML/CSS than from descriptions.

## Public pages (logged out): full-page screenshot at 1440px AND 390px (mobile)
Use Chrome DevTools → Ctrl+Shift+P → "Capture full size screenshot". Device toolbar for mobile.
- [ ] Home `/index.php/jidmis`
- [ ] About `/about` (plus the About dropdown open)
- [ ] Editorial Board, APC, Privacy, Contact
- [ ] Ethics dropdown open + 2 ethics pages
- [ ] Current issue, Archives, one Issue view
- [ ] One Article landing page (plus the How to Cite dropdown open)
- [ ] PDF galley viewer page
- [ ] Submissions page
- [ ] Announcements list + one announcement
- [ ] Search results (search a common word)
- [ ] Register, Login, Lost password
- [ ] Mobile: hamburger menu open

## Styles
- [ ] Save one page as "Webpage, Complete" (Ctrl+S) and keep it in `/reference/html/`.
  Claude can read the class names, structure and CSS from it.
- [ ] Note the theme colour hex, fonts, logo size and footer HTML (DevTools → inspect).
- [ ] Use these files for **reference only**. Write our own CSS; don't ship their files.

## Logged-in, with a test author account (only the screens we can legitimately reach)
Register with a clearly test identity. **Don't finish a real submission** to their
journal: stop before the final "Finish Submission" confirmation, and never upload real
manuscripts. Screenshot:
- [ ] Post-registration "Registration complete" page
- [ ] Dashboard (empty "My Queue")
- [ ] New Submission wizard step 1 (Start) — checklist, section dropdown
- [ ] Step 2 (Upload) — the upload dialog and file type dropdown
- [ ] Step 3 (Metadata) — contributors grid + "Add Contributor" modal
- [ ] Step 4 (Confirmation) — screenshot, then **cancel/leave**
- [ ] Profile page, every tab
- [ ] Notifications/Tasks dropdown
Afterwards, delete the incomplete submission from your dashboard, if the option exists.

## What we can't see (and how we cover it)
Editor, reviewer, manager and admin screens are specified in docs/03 and docs/04 from
OJS 3.3's documented behaviour. For extra visual reference, the PKP documentation
(docs.pkp.sfu.ca → "Learning OJS 3.3") has screenshots of every editorial and settings
screen. Save the useful ones into `/reference/backend/` and tell Claude which screen
each one shows.

## File naming
`/reference/public/home-desktop.png`, `home-mobile.png`, `article-desktop.png`, ...
`/reference/backend/wizard-step1.png`, `profile-roles.png`, `pkp-doc-review-stage.png`, ...
