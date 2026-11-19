SET NAMES utf8mb4;
-- DEMO SEED (optional). {{HASH}} is replaced by the installer with password_hash('Demo@12345').
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('editor','editor@example.com','{{HASH}}','Edith','Editor','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,3 FROM users WHERE username='editor';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='editor';
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('sectioneditor','sectioneditor@example.com','{{HASH}}','Sam','Sectioneditor','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,4 FROM users WHERE username='sectioneditor';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='sectioneditor';
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('reviewer1','reviewer1@example.com','{{HASH}}','Rita','Reviewer','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,5 FROM users WHERE username='reviewer1';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='reviewer1';
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('reviewer2','reviewer2@example.com','{{HASH}}','Remi','Reviewer','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,5 FROM users WHERE username='reviewer2';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='reviewer2';
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('author','author@example.com','{{HASH}}','Ada','Author','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,6 FROM users WHERE username='author';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='author';
INSERT INTO users (username,email,password_hash,given_name,family_name,affiliation,country,created_at) VALUES ('reader','reader@example.com','{{HASH}}','Rex','Reader','Example University','NG',NOW());
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='reader';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,10 FROM users WHERE username='reader';
INSERT IGNORE INTO user_user_groups (user_id,user_group_id) SELECT id,6 FROM users WHERE username IN ('editor','sectioneditor');
INSERT INTO section_editors (section_id,user_id) SELECT s.id,u.id FROM sections s, users u WHERE u.username='sectioneditor';
INSERT INTO issues (id,journal_id,volume,number,year,show_volume,show_number,show_year,published,date_published,is_current,seq,created_at) VALUES (1,1,1,'1',2025,1,1,1,1,'2025-03-15 09:00:00',0,2,NOW()),(2,1,1,'2',2025,1,1,1,1,'2025-06-15 09:00:00',1,1,NOW());
INSERT INTO issue_section_order (issue_id,section_id,seq) VALUES (1,1,1),(1,2,2),(2,1,1),(2,2,2);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 1,1,1,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (1,1,1,'Placeholder Study of Cross-Disciplinary Data Sharing','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["data sharing", "interdisciplinary", "placeholder"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',1,1,'1-12','10.0000/jirs.2025.001','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-03-15',1,NULL);
UPDATE submissions SET current_publication_id=1 WHERE id=1;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (1,'Ada','Author','ada@example.com','NG','Example University',1);
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (1,'Ben','Placeholder','ben@example.com','NG','Example University',2);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (1,1,'galley','Article Text','article-1.pdf','demo-article-1.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (1,1,'PDF',1,1);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 2,1,1,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (2,2,1,'A Placeholder Framework for Evaluating Research Methods','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["methods", "evaluation", "framework"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',1,1,'13-26','10.0000/jirs.2025.002','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-03-15',2,NULL);
UPDATE submissions SET current_publication_id=2 WHERE id=2;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (2,'Chidi','Example','chidi@example.com','NG','Example University',1);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (2,2,'galley','Article Text','article-2.pdf','demo-article-2.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (2,2,'PDF',2,1);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 3,1,2,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (3,3,1,'Placeholder Review of Collaborative Research Practice','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["review", "collaboration", "practice"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',1,2,'27-40','10.0000/jirs.2025.003','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-03-15',3,NULL);
UPDATE submissions SET current_publication_id=3 WHERE id=3;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (3,'Dara','Sample','dara@example.com','NG','Example University',1);
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (3,'Ada','Author','ada@example.com','NG','Example University',2);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (3,3,'galley','Article Text','article-3.pdf','demo-article-3.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (3,3,'PDF',3,1);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 4,1,1,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (4,4,1,'Placeholder Analysis of Open Access Publishing Trends','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["open access", "publishing", "trends"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',2,1,'1-14','10.0000/jirs.2025.004','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-06-15',4,NULL);
UPDATE submissions SET current_publication_id=4 WHERE id=4;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (4,'Efe','Demo','efe@example.com','NG','Example University',1);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (4,4,'galley','Article Text','article-4.pdf','demo-article-4.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (4,4,'PDF',4,1);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 5,1,1,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (5,5,1,'Placeholder Model for Community-Based Research Design','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["community", "design", "model"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',2,1,'15-30','10.0000/jirs.2025.005','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-06-15',5,NULL);
UPDATE submissions SET current_publication_id=5 WHERE id=5;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (5,'Funke','Test','funke@example.com','NG','Example University',1);
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (5,'Gbenga','Sample','gbenga@example.com','NG','Example University',2);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (5,5,'galley','Article Text','article-5.pdf','demo-article-5.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (5,5,'PDF',5,1);
INSERT INTO submissions (id,journal_id,section_id,stage_id,status,submission_progress,date_submitted,date_last_activity,created_by) SELECT 6,1,2,5,'published',0,'2025-01-10 10:00:00',NOW(),id FROM users WHERE username='author';
INSERT INTO publications (id,submission_id,version,title,abstract,keywords,references_text,issue_id,section_id,pages,doi,copyright_holder,copyright_year,license_url,status,date_published,seq,primary_contact_id) VALUES (6,6,1,'Placeholder Review of Statistical Reporting Standards','<p>Placeholder abstract for demonstration purposes only. It describes a fictional study in a few sentences.</p>','["statistics", "reporting", "standards"]','Placeholder Reference One. (2024). Example title. Example Journal.
Placeholder Reference Two. (2023). Another example. Example Press.',2,2,'31-48','10.0000/jirs.2025.006','The Authors',2025,'https://creativecommons.org/licenses/by/4.0/','published','2025-06-15',6,NULL);
UPDATE submissions SET current_publication_id=6 WHERE id=6;
INSERT INTO authors (publication_id,given_name,family_name,email,country,affiliation,seq) VALUES (6,'Halima','Example','halima@example.com','NG','Example University',1);
INSERT INTO submission_files (id,submission_id,file_stage,genre,original_name,stored_name,mime,size,viewable,created_at) VALUES (6,6,'galley','Article Text','article-6.pdf','demo-article-6.pdf','application/pdf',0,1,NOW());
INSERT INTO galleys (id,publication_id,label,submission_file_id,seq) VALUES (6,6,'PDF',6,1);
