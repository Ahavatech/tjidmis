-- JIRS journal platform schema. MySQL 5.7+ / MariaDB 10.4+, utf8mb4, InnoDB.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE site_settings (name VARCHAR(100) PRIMARY KEY, value MEDIUMTEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE journals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(32) NOT NULL UNIQUE, seq INT NOT NULL DEFAULT 0,
  enabled TINYINT NOT NULL DEFAULT 1, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE journal_settings (
  journal_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, value MEDIUMTEXT,
  PRIMARY KEY (journal_id, name), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL, given_name VARCHAR(120) NOT NULL, family_name VARCHAR(120) NOT NULL DEFAULT '',
  affiliation VARCHAR(255) NOT NULL DEFAULT '', country CHAR(2) NULL, phone VARCHAR(40) NULL, mailing_address TEXT NULL,
  orcid VARCHAR(40) NULL, url VARCHAR(255) NULL, bio TEXT NULL, photo VARCHAR(255) NULL, reviewing_interests TEXT NULL,
  disabled TINYINT NOT NULL DEFAULT 0, disabled_reason VARCHAR(255) NULL, must_change_password TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL, last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_groups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NULL,
  role_key ENUM('site_admin','manager','editor','section_editor','reviewer','author','copyeditor','layout_editor','proofreader','reader') NOT NULL,
  name VARCHAR(100) NOT NULL, permit_self_registration TINYINT NOT NULL DEFAULT 0,
  KEY (journal_id), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_group_stages (
  user_group_id INT UNSIGNED NOT NULL, stage_id TINYINT NOT NULL, PRIMARY KEY (user_group_id, stage_id),
  FOREIGN KEY (user_group_id) REFERENCES user_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_user_groups (
  user_id INT UNSIGNED NOT NULL, user_group_id INT UNSIGNED NOT NULL, PRIMARY KEY (user_id, user_group_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY (user_group_id) REFERENCES user_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_settings (
  user_id INT UNSIGNED NOT NULL, journal_id INT UNSIGNED NOT NULL DEFAULT 0, name VARCHAR(100) NOT NULL, value MEDIUMTEXT,
  PRIMARY KEY (user_id, journal_id, name), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, PRIMARY KEY (token_hash), KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sessions_remember (
  user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, expires_at DATETIME NOT NULL, PRIMARY KEY (token_hash), KEY (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sections (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, abbrev VARCHAR(30) NOT NULL DEFAULT '',
  policy TEXT NULL, seq INT NOT NULL DEFAULT 0, word_count INT NULL, abstracts_required TINYINT NOT NULL DEFAULT 1,
  editor_restricted TINYINT NOT NULL DEFAULT 0, meta_reviewed TINYINT NOT NULL DEFAULT 1, hide_title TINYINT NOT NULL DEFAULT 0,
  KEY (journal_id), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE section_editors (
  section_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, PRIMARY KEY (section_id, user_id),
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE issues (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, volume INT NULL, number VARCHAR(20) NULL, year INT NULL,
  title VARCHAR(255) NULL, show_volume TINYINT NOT NULL DEFAULT 1, show_number TINYINT NOT NULL DEFAULT 1, show_year TINYINT NOT NULL DEFAULT 1,
  show_title TINYINT NOT NULL DEFAULT 0, description TEXT NULL, cover_image VARCHAR(255) NULL, url_path VARCHAR(64) NULL,
  published TINYINT NOT NULL DEFAULT 0, date_published DATETIME NULL, is_current TINYINT NOT NULL DEFAULT 0, seq INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL, KEY (journal_id), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE issue_section_order (
  issue_id INT UNSIGNED NOT NULL, section_id INT UNSIGNED NOT NULL, seq INT NOT NULL DEFAULT 0, PRIMARY KEY (issue_id, section_id),
  FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE, FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE static_pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, path VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT, UNIQUE KEY (journal_id, path), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE custom_blocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, title VARCHAR(255) NOT NULL,
  content MEDIUMTEXT, show_title TINYINT NOT NULL DEFAULT 0, UNIQUE KEY (journal_id, name),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE navigation_menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, menu ENUM('primary','user') NOT NULL DEFAULT 'primary',
  parent_id INT UNSIGNED NULL,
  type ENUM('about','editorial_team','submissions','privacy','contact','current','archives','announcements','search','static_page','custom_url',
            'information_readers','information_authors','information_librarians') NOT NULL,
  title VARCHAR(255) NOT NULL, static_page_id INT UNSIGNED NULL, url VARCHAR(255) NULL, seq INT NOT NULL DEFAULT 0,
  KEY (journal_id), KEY (parent_id), KEY (static_page_id),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES navigation_menu_items(id) ON DELETE CASCADE,
  FOREIGN KEY (static_page_id) REFERENCES static_pages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, type_id INT UNSIGNED NULL, title VARCHAR(255) NOT NULL,
  description_short TEXT NULL, description MEDIUMTEXT NULL, date_expire DATE NULL, date_posted DATETIME NOT NULL,
  KEY (journal_id), FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, parent_id INT UNSIGNED NULL, title VARCHAR(255) NOT NULL, path VARCHAR(100) NOT NULL,
  description TEXT NULL, seq INT NOT NULL DEFAULT 0, UNIQUE KEY (journal_id, path),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE, FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE submissions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, section_id INT UNSIGNED NULL, locale VARCHAR(10) NOT NULL DEFAULT 'en',
  stage_id TINYINT NOT NULL DEFAULT 1, status ENUM('incomplete','queued','scheduled','published','declined') NOT NULL DEFAULT 'incomplete',
  submission_progress TINYINT NOT NULL DEFAULT 1, comments_to_editor TEXT NULL, current_publication_id INT UNSIGNED NULL,
  date_submitted DATETIME NULL, date_last_activity DATETIME NULL, created_by INT UNSIGNED NULL,
  KEY (journal_id), KEY (section_id), KEY (created_by), KEY (status),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE, FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE apc_payments (
  submission_id INT UNSIGNED NOT NULL PRIMARY KEY, status ENUM('pending','paid','waived') NOT NULL DEFAULT 'pending', amount DECIMAL(10,2) NULL, currency VARCHAR(3) NULL,
  reference VARCHAR(190) NULL, note VARCHAR(255) NULL, paid_at DATETIME NULL, updated_by INT UNSIGNED NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE publications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, version INT NOT NULL DEFAULT 1, title VARCHAR(500) NOT NULL DEFAULT '',
  subtitle VARCHAR(500) NULL, prefix VARCHAR(50) NULL, abstract MEDIUMTEXT NULL, keywords JSON NULL, subjects JSON NULL, references_text MEDIUMTEXT NULL,
  coverage TEXT NULL, rights TEXT NULL, `source` VARCHAR(500) NULL, dc_type VARCHAR(255) NULL, dc_languages VARCHAR(255) NULL, disciplines JSON NULL, agencies JSON NULL,
  issue_id INT UNSIGNED NULL, section_id INT UNSIGNED NULL, pages VARCHAR(50) NULL, url_path VARCHAR(64) NULL, cover_image VARCHAR(255) NULL,
  doi VARCHAR(255) NULL, copyright_holder TEXT NULL, copyright_year INT NULL, license_url VARCHAR(255) NULL,
  status ENUM('unscheduled','scheduled','published') NOT NULL DEFAULT 'unscheduled', date_published DATE NULL, seq INT NOT NULL DEFAULT 0,
  primary_contact_id INT UNSIGNED NULL, KEY (submission_id), KEY (issue_id), KEY (section_id), FULLTEXT KEY ft_pub (title, abstract),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
  FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE SET NULL, FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE submissions ADD FOREIGN KEY (current_publication_id) REFERENCES publications(id) ON DELETE SET NULL;

CREATE TABLE publication_categories (
  publication_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NOT NULL, PRIMARY KEY (publication_id, category_id),
  FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE, FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE authors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, publication_id INT UNSIGNED NOT NULL, given_name VARCHAR(1000) NOT NULL, family_name VARCHAR(120) NOT NULL DEFAULT '',
  email VARCHAR(190) NULL, country CHAR(2) NULL, affiliation VARCHAR(255) NULL, orcid VARCHAR(40) NULL, url VARCHAR(255) NULL, bio TEXT NULL,
  user_group ENUM('author','translator') NOT NULL DEFAULT 'author', include_in_browse TINYINT NOT NULL DEFAULT 1, seq INT NOT NULL DEFAULT 0,
  KEY (publication_id), FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE submission_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL,
  file_stage ENUM('submission','review_file','review_attachment','review_revision','attachment','copyedit','final','production_ready','proof','galley','discussion','note') NOT NULL,
  genre VARCHAR(50) NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(255) NOT NULL, mime VARCHAR(120) NOT NULL, size INT UNSIGNED NOT NULL DEFAULT 0,
  uploader_user_id INT UNSIGNED NULL, review_round_id INT UNSIGNED NULL, assoc_type INT NULL, assoc_id INT UNSIGNED NULL, viewable TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL, KEY (submission_id), KEY (uploader_user_id), KEY (review_round_id),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (uploader_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE library_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(100) NOT NULL,
  file_type TINYINT NOT NULL DEFAULT 5, public_access TINYINT NOT NULL DEFAULT 0, mime VARCHAR(100) NULL, size INT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL,
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE galleys (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, publication_id INT UNSIGNED NOT NULL, label VARCHAR(30) NOT NULL, submission_file_id INT UNSIGNED NULL,
  remote_url VARCHAR(500) NULL, seq INT NOT NULL DEFAULT 0, KEY (publication_id), KEY (submission_file_id),
  FOREIGN KEY (publication_id) REFERENCES publications(id) ON DELETE CASCADE, FOREIGN KEY (submission_file_id) REFERENCES submission_files(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stage_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, user_group_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  recommend_only TINYINT NOT NULL DEFAULT 0, can_change_metadata TINYINT NOT NULL DEFAULT 0, date_assigned DATETIME NOT NULL,
  UNIQUE KEY (submission_id, user_group_id, user_id), KEY (user_id),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (user_group_id) REFERENCES user_groups(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE review_rounds (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, stage_id TINYINT NOT NULL, round INT NOT NULL DEFAULT 1, status INT NOT NULL DEFAULT 0,
  KEY (submission_id), FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE submission_files ADD FOREIGN KEY (review_round_id) REFERENCES review_rounds(id) ON DELETE SET NULL;

CREATE TABLE review_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, review_round_id INT UNSIGNED NOT NULL, reviewer_id INT UNSIGNED NOT NULL,
  review_method ENUM('anonymous','double_anonymous','open') NOT NULL DEFAULT 'anonymous',
  status ENUM('awaiting_response','accepted','declined','review_submitted','thanked','cancelled') NOT NULL DEFAULT 'awaiting_response',
  competing_interests TEXT NULL, recommendation ENUM('accept','pending_revisions','resubmit_here','resubmit_elsewhere','decline','see_comments') NULL,
  comments_author MEDIUMTEXT NULL, comments_editor MEDIUMTEXT NULL, date_assigned DATETIME NOT NULL, date_notified DATETIME NULL, date_response_due DATETIME NULL,
  date_due DATETIME NULL, date_confirmed DATETIME NULL, date_completed DATETIME NULL, date_acknowledged DATETIME NULL, date_reminded DATETIME NULL, quality TINYINT NULL,
  KEY (submission_id), KEY (review_round_id), KEY (reviewer_id),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (review_round_id) REFERENCES review_rounds(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE review_round_files (
  review_round_id INT UNSIGNED NOT NULL, submission_file_id INT UNSIGNED NOT NULL, PRIMARY KEY (review_round_id, submission_file_id),
  FOREIGN KEY (review_round_id) REFERENCES review_rounds(id) ON DELETE CASCADE, FOREIGN KEY (submission_file_id) REFERENCES submission_files(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE edit_decisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, review_round_id INT UNSIGNED NULL, stage_id TINYINT NOT NULL, editor_id INT UNSIGNED NOT NULL,
  decision VARCHAR(50) NOT NULL, date_decided DATETIME NOT NULL, KEY (submission_id), KEY (review_round_id), KEY (editor_id),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (review_round_id) REFERENCES review_rounds(id) ON DELETE SET NULL,
  FOREIGN KEY (editor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE queries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, stage_id TINYINT NOT NULL, subject VARCHAR(255) NOT NULL, closed TINYINT NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL, created_at DATETIME NOT NULL, KEY (submission_id), KEY (created_by),
  FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE query_participants (
  query_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, PRIMARY KEY (query_id, user_id),
  FOREIGN KEY (query_id) REFERENCES queries(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE query_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, query_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL, contents MEDIUMTEXT NOT NULL, created_at DATETIME NOT NULL,
  KEY (query_id), KEY (user_id), FOREIGN KEY (query_id) REFERENCES queries(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE query_reads (
  query_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, last_read_at DATETIME NOT NULL, PRIMARY KEY (query_id, user_id),
  FOREIGN KEY (query_id) REFERENCES queries(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_templates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, email_key VARCHAR(80) NOT NULL, subject VARCHAR(255) NOT NULL, body MEDIUMTEXT NOT NULL,
  enabled TINYINT NOT NULL DEFAULT 1, is_custom TINYINT NOT NULL DEFAULT 0, UNIQUE KEY (journal_id, email_key),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NULL, submission_id INT UNSIGNED NULL, from_addr VARCHAR(255) NULL, to_addr VARCHAR(255) NOT NULL,
  cc VARCHAR(255) NULL, subject VARCHAR(255) NOT NULL, body MEDIUMTEXT, sent TINYINT NOT NULL DEFAULT 0, error TEXT NULL, created_at DATETIME NOT NULL,
  KEY (journal_id), KEY (submission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, journal_id INT UNSIGNED NULL, type VARCHAR(60) NOT NULL, assoc_type VARCHAR(40) NULL,
  assoc_id INT UNSIGNED NULL, message TEXT NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL, KEY (user_id), KEY (journal_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE event_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, submission_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL, event VARCHAR(80) NOT NULL, message TEXT NULL, created_at DATETIME NOT NULL,
  KEY (submission_id), KEY (user_id), FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE metrics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, journal_id INT UNSIGNED NOT NULL, submission_id INT UNSIGNED NOT NULL, galley_id INT UNSIGNED NOT NULL DEFAULT 0,
  type ENUM('abstract_view','galley_download','issue_view') NOT NULL, day DATE NOT NULL, count INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_metric (submission_id, galley_id, type, day), KEY (journal_id),
  FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE, FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS=1;
