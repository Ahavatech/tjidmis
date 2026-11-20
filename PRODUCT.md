# Product

<!-- impeccable:product-schema 1 -->

## Platform
web

## Users
Readers and researchers browse published issues and articles. Authors submit
work; editors and reviewers manage peer review; journal managers administer
the publication.

## Product Purpose
Run the JIDMIS journal on OJS while reproducing the project's established
public-site appearance. Success means publishing and browsing real issues and
articles through OJS workflows on the assigned test domain.

## Positioning
This is the journal's OJS publishing site, not a second custom publishing
application.

## Operating Context
The deployment target is cPanel shared hosting and the local reference is OJS
3.3.0-22. The site is used for a school project studying search-engine and
Google Scholar discovery.

## Capabilities and Constraints
- Keep OJS submission, editorial, review, publication, article URLs, and
  indexing metadata working.
- Preserve the imported 26 issues, 76 articles, article metadata, and PDFs.
- The custom PHP clone is the visual reference and must not be the hosted app.
- Public content is hosted on the assigned test domain; Scholar inclusion is
  not guaranteed by the platform.

## Brand Commitments
The project owner authorized use of JIDMIS journal identity and content for the
school project. Use the assigned test domain, not the live journal domain.

## Evidence on Hand
- Existing public-site implementation: `app/Views/` and `assets/css/`.
- OJS migration package: `jidmis-ojs-import.zip`.
- OJS 3.3.0-22 running locally at port 8082.

## Product Principles
- OJS remains the source of truth for published journal content.
- Preserve the established public-site visuals without replacing OJS behavior.
- Keep pages responsive, accessible, and crawlable.
