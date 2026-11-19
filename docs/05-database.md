# 05 — Database Schema (MySQL, utf8mb4, InnoDB)

Simplified from OJS's design; still multi-journal-capable. All tables have `id INT UNSIGNED
AUTO_INCREMENT PK` unless noted. Timestamps are `DATETIME`. Put foreign keys + indexes on all `*_id`.
Write it as `install/schema.sql` + `install/seed.sql`.

```sql
-- Site & journals
site_settings(name VARCHAR(100) PK, value MEDIUMTEXT)
journals(id, path VARCHAR(32) UNIQUE, seq INT, enabled TINYINT, created_at)
journal_settings(journal_id, name VARCHAR(100), value MEDIUMTEXT, PK(journal_id,name))
  -- name, abbreviation, about, description, editorialTeam, contact*, issn, eissn,
  -- doiPrefix, themeColor, logo, favicon, pageFooter, authorGuidelines,
  -- privacyStatement, copyrightNotice, licenseUrl, copyrightHolderType,
  -- reviewGuidelines, competingInterests, defaultReviewMode, numWeeksPerResponse,
  -- numWeeksPerReview, readerInfo, authorInfo, librarianInfo, sidebarBlocks(JSON),
  -- enabledFeatures(JSON), itemsPerPage, ...

-- Users
users(id, username UNIQUE, email UNIQUE, password_hash, given_name, family_name,
      affiliation, country CHAR(2), phone, mailing_address, orcid, url, bio, photo,
      reviewing_interests TEXT, disabled TINYINT, disabled_reason,
      must_change_password TINYINT, created_at, last_login_at)
user_groups(id, journal_id NULL, role_key ENUM('site_admin','manager','editor',
      'section_editor','reviewer','author','copyeditor','layout_editor',
      'proofreader','reader'), name, permit_self_registration TINYINT)
user_group_stages(user_group_id, stage_id TINYINT, PK(...))
user_user_groups(user_id, user_group_id, PK(...))
user_settings(user_id, journal_id, name, value)   -- consent, notify prefs, reviewer optin
password_resets(user_id, token_hash, expires_at)
sessions_remember(user_id, token_hash, expires_at)

-- Content (public)
sections(id, journal_id, title, abbrev, policy TEXT, seq, word_count INT NULL,
         abstracts_required, editor_restricted, meta_reviewed, hide_title)
section_editors(section_id, user_id)
issues(id, journal_id, volume INT NULL, number VARCHAR(20) NULL, year INT NULL,
       title VARCHAR(255) NULL, show_volume, show_number, show_year, show_title,
       description TEXT, cover_image, url_path, published TINYINT,
       date_published DATETIME NULL, is_current TINYINT, seq INT, created_at)
issue_section_order(issue_id, section_id, seq)
static_pages(id, journal_id, path UNIQUE per journal, title, content MEDIUMTEXT)
custom_blocks(id, journal_id, name, title, content MEDIUMTEXT, show_title)
navigation_menu_items(id, journal_id, menu ENUM('primary','user'), parent_id NULL,
       type ENUM('about','editorial_team','submissions','privacy','contact','current',
       'archives','announcements','search','static_page','custom_url','information_readers',
       'information_authors','information_librarians'),
       title, static_page_id NULL, url NULL, seq)
announcements(id, journal_id, type_id NULL, title, description_short TEXT,
       description MEDIUMTEXT, date_expire DATE NULL, date_posted DATETIME)

-- Submissions
submissions(id, journal_id, section_id, locale, stage_id TINYINT, status ENUM(
       'incomplete','queued','scheduled','published','declined'),
       submission_progress TINYINT, comments_to_editor TEXT,
       current_publication_id NULL, date_submitted, date_last_activity, created_by)
publications(id, submission_id, version INT, title, subtitle, prefix, abstract MEDIUMTEXT,
       keywords JSON, subjects JSON, references_text MEDIUMTEXT, issue_id NULL,
       section_id, pages VARCHAR(50), url_path, cover_image, doi VARCHAR(255),
       copyright_holder, copyright_year, license_url, status ENUM('unscheduled',
       'scheduled','published'), date_published DATE NULL, seq INT, primary_contact_id)
authors(id, publication_id, given_name, family_name, email, country, affiliation,
       orcid, url, bio, user_group ENUM('author','translator'),
       include_in_browse TINYINT, seq)
submission_files(id, submission_id, file_stage ENUM('submission','review_file',
       'review_attachment','review_revision','attachment','copyedit','final',
       'production_ready','proof','galley','discussion','note'),
       genre VARCHAR(50), original_name, stored_name, mime, size,
       uploader_user_id, review_round_id NULL, assoc_type NULL, assoc_id NULL,
       viewable TINYINT, created_at)
galleys(id, publication_id, label VARCHAR(30), submission_file_id NULL,
        remote_url NULL, seq)

-- Workflow
stage_assignments(id, submission_id, user_group_id, user_id, recommend_only TINYINT,
        can_change_metadata TINYINT, date_assigned)
review_rounds(id, submission_id, stage_id, round INT, status INT)
review_assignments(id, submission_id, review_round_id, reviewer_id,
        review_method ENUM('anonymous','double_anonymous','open'),
        status ENUM('awaiting_response','accepted','declined','review_submitted',
        'thanked','cancelled'), competing_interests TEXT,
        recommendation ENUM('accept','pending_revisions','resubmit_here',
        'resubmit_elsewhere','decline','see_comments') NULL,
        comments_author MEDIUMTEXT, comments_editor MEDIUMTEXT,
        date_assigned, date_notified, date_response_due, date_due,
        date_confirmed, date_completed, date_acknowledged, date_reminded, quality TINYINT NULL)
review_round_files(review_round_id, submission_file_id)
edit_decisions(id, submission_id, review_round_id NULL, stage_id, editor_id,
        decision VARCHAR(50), date_decided)
queries(id, submission_id, stage_id, subject, closed TINYINT, created_by, created_at)  -- discussions
query_participants(query_id, user_id)
query_notes(id, query_id, user_id, contents MEDIUMTEXT, created_at)
query_reads(query_id, user_id, last_read_at)

-- System
email_templates(id, journal_id, email_key, subject, body MEDIUMTEXT, enabled, is_custom)
email_log(id, journal_id, submission_id NULL, from_addr, to_addr, cc, subject,
        body MEDIUMTEXT, sent TINYINT, error TEXT, created_at)
notifications(id, user_id, journal_id, type VARCHAR(60), assoc_type, assoc_id,
        message TEXT, read_at NULL, created_at)
event_log(id, submission_id, user_id, event VARCHAR(80), message TEXT, created_at)
metrics(id, journal_id, submission_id, galley_id NULL, type ENUM('abstract_view',
        'galley_download','issue_view'), day DATE, count INT, UNIQUE(submission_id,galley_id,type,day))
```

## Notes
- The current issue is `issues.is_current=1` (only one per journal, enforced in code).
- Metrics: on each abstract view or download, do an `INSERT ... ON DUPLICATE KEY UPDATE
  count=count+1`. Skip bots (simple user-agent regex) and repeat hits from the same IP
  within 30s (session or IP cache).
- Keep settings in key/value tables so new settings don't need migrations. Load them
  once per request and cache in a static array.
- Store files at `{files_dir}/journals/{journalId}/submissions/{submissionId}/{stage}/{uuid}.{ext}`.
