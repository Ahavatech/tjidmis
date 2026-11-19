SET NAMES utf8mb4;
-- BASE SEED: journal, roles, settings, menus, static pages, blocks, email templates. All values are JIRS placeholders.

INSERT INTO journals (id,path,seq,enabled,created_at) VALUES (1,'jirs',1,1,NOW());
INSERT INTO site_settings (name,value) VALUES ('title','JIRS Journal Platform'),('redirect_journal','jirs'),('contact_email','admin@example.com');
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (1,NULL,'site_admin','Site Administrator',0);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (2,1,'manager','Journal Manager',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (2,1);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (2,3);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (2,4);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (2,5);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (3,1,'editor','Journal Editor',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (3,1);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (3,3);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (3,4);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (3,5);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (4,1,'section_editor','Section Editor',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (4,1);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (4,3);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (4,4);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (4,5);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (5,1,'reviewer','Reviewer',1);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (5,3);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (6,1,'author','Author',1);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (6,1);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (7,1,'copyeditor','Copyeditor',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (7,4);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (8,1,'layout_editor','Layout Editor',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (8,5);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (9,1,'proofreader','Proofreader',0);
INSERT INTO user_group_stages (user_group_id,stage_id) VALUES (9,5);
INSERT INTO user_groups (id,journal_id,role_key,name,permit_self_registration) VALUES (10,1,'reader','Reader',1);
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'name','Journal of Interdisciplinary Research Sciences');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'abbreviation','JIRS');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'issn','0000-0000');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'eissn','0000-0000');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'doiPrefix','10.0000/jirs');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'themeColor','#d79f24');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'logo','');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'favicon','');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'homepageImage','');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'description','<p>The <em>Journal of Interdisciplinary Research Sciences</em> is a peer-reviewed, open access journal that publishes original research across disciplines. This text is placeholder content and can be edited under Settings.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'about','<p>Placeholder text describing the aims and scope, frequency, peer review, open access, archiving and indexing policies of the journal.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'editorialTeam','<p>Placeholder editorial board. Add the real editors under Settings.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'contactName','Editorial Office');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'contactEmail','editor@example.com');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'contactPhone','+000 000 000 0000');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'contactMailingAddress','Editorial Office, Example Street, Example City');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'pageFooter','<p>Journal of Interdisciplinary Research Sciences<br>Journal abbreviation: JIRS<br>eISSN (online): 0000-0000<br>Journal DOI: <span class="value">10.0000/jirs</span><br>Published under the terms of the <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener">Creative Commons CC-BY 4.0</a> license</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'authorGuidelines','<p>Placeholder author guidelines. Describe formatting, length and file requirements here.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'submissionChecklist','["The submission has not been previously published, nor is it before another journal for consideration.", "The submission file is in Microsoft Word, OpenDocument or PDF format.", "All URLs in the text have been provided in full where possible.", "The text follows the stylistic and bibliographic requirements in the Author Guidelines."]');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'privacyStatement','<p>Placeholder privacy statement. The names and email addresses entered in this journal site will be used exclusively for the stated purposes of this journal.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'copyrightNotice','<p>Placeholder copyright notice. Authors retain copyright and grant the journal the right of first publication under a CC BY 4.0 licence.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'licenseUrl','https://creativecommons.org/licenses/by/4.0/');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'copyrightHolderType','author');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'reviewGuidelines','<p>Placeholder reviewer guidelines.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'competingInterests','<p>Placeholder competing interests statement.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'defaultReviewMode','anonymous');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'numWeeksPerResponse','3');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'numWeeksPerReview','4');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'readerInfo','<p>Placeholder information for readers.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'authorInfo','<p>Placeholder information for authors.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'librarianInfo','<p>Placeholder information for librarians.</p>');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'sidebarBlocks','["custom:about-jirs", "custom:metrics", "custom:journal-information", "custom:apc-information", "custom:paper-template", "custom:indexing", "custom:other-information", "information", "web_feed"]');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'enabledFeatures','{"announcements": true, "search": true, "metrics": true, "feeds": true}');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'itemsPerPage','25');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'numAnnouncementsHomepage','3');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'themeFont','Noto Serif');
INSERT INTO journal_settings (journal_id,name,value) VALUES (1,'headingFont','Inter');
INSERT INTO sections (id,journal_id,title,abbrev,policy,seq) VALUES (1,1,'Articles','ART','Standard peer-reviewed research articles.',1),(2,1,'Review Articles','REV','Invited and submitted review articles.',2);
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'APCharges','Article Processing Charge','<p>Placeholder content for Article Processing Charge. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'editorialboard','Editorial Board','<p>Placeholder content for Editorial Board. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'pes','Publication Ethics Statement','<p>Placeholder content for Publication Ethics Statement. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'COI','Conflict of Interests','<p>Placeholder content for Conflict of Interests. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'CopyrightandLicensing','Copyright and Licensing','<p>Placeholder content for Copyright and Licensing. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'selfarchive','Self-archiving Policy','<p>Placeholder content for Self-archiving Policy. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'ArchivingDigitalVersion','Archiving Digital Version','<p>Placeholder content for Archiving Digital Version. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'pp','Plagiarism Policy','<p>Placeholder content for Plagiarism Policy. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'Unethicalbehaviour','Unethical behaviour','<p>Placeholder content for Unethical behaviour. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'RetractionandCorrectionpolicies','Retraction and Correction policies','<p>Placeholder content for Retraction and Correction policies. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'Procedures','Procedures for Dealing with Complaints and Appeals','<p>Placeholder content for Procedures for Dealing with Complaints and Appeals. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'GEN','Use of Generative AI and AI-Assisted Tools','<p>Placeholder content for Use of Generative AI and AI-Assisted Tools. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'indexing_refund_disclaimer_policy','Indexing & Refund Disclaimer Policy','<p>Placeholder content for Indexing &amp; Refund Disclaimer Policy. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO static_pages (journal_id,path,title,content) VALUES (1,'Disclaimer','Disclaimer','<p>Placeholder content for Disclaimer. Replace this text under Settings, Website, Static Pages.</p>');
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'about-jirs','About JIRS','<p>A short placeholder summary of the journal.</p>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'metrics','Metrics','<p class="image_placeholder">[Metric badge image placeholder]</p>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'journal-information','Journal Information','<table class="journal_info"><tbody><tr><td>Frequency</td><td>Quarterly</td></tr><tr><td>Acceptance rate</td><td>00%</td></tr><tr><td>Review process</td><td>Single-blind peer review</td></tr><tr><td>Language</td><td>English</td></tr><tr><td>Open access</td><td>Yes</td></tr><tr><td>eISSN</td><td>0000-0000</td></tr><tr><td>DOI prefix</td><td>10.0000/jirs</td></tr></tbody></table>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'apc-information','APC Information','<p>Placeholder APC information.</p><p><a class="cmp_button" href="__SUBMIT_URL__">Make a Submission</a></p>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'paper-template','Paper Template','<p><a href="#">Download paper template (placeholder)</a></p><p><a href="#">Download copyright form (placeholder)</a></p>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'indexing','Indexing','<h3>The Journal is indexed or abstracted in:</h3><p class="image_placeholder">[Indexing logos placeholder]</p>',0);
INSERT INTO custom_blocks (journal_id,name,title,content,show_title) VALUES (1,'other-information','Other information','<h3>Other information:</h3><p class="image_placeholder">[Plagiarism check / licence badges placeholder]</p>',0);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'custom_url','About',NULL,1);
SET @about=LAST_INSERT_ID();
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@about,'about','About the Journal',NULL,1);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@about,'editorial_team','Editorial Board',NULL,2);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@about,'static_page','Article Processing Charge',(SELECT id FROM static_pages WHERE journal_id=1 AND path='APCharges'),3);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@about,'privacy','Privacy Statement',NULL,4);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@about,'contact','Contact',NULL,5);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'current','Current',NULL,2);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'archives','Archives',NULL,3);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'submissions','Submissions',NULL,4);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'custom_url','Ethics',NULL,5);
SET @eth=LAST_INSERT_ID();
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Publication Ethics Statement',(SELECT id FROM static_pages WHERE journal_id=1 AND path='pes'),1);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Conflict of Interests',(SELECT id FROM static_pages WHERE journal_id=1 AND path='COI'),2);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Copyright and Licensing',(SELECT id FROM static_pages WHERE journal_id=1 AND path='CopyrightandLicensing'),3);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Self-archiving Policy',(SELECT id FROM static_pages WHERE journal_id=1 AND path='selfarchive'),4);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Archiving Digital Version',(SELECT id FROM static_pages WHERE journal_id=1 AND path='ArchivingDigitalVersion'),5);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Plagiarism Policy',(SELECT id FROM static_pages WHERE journal_id=1 AND path='pp'),6);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Unethical behaviour',(SELECT id FROM static_pages WHERE journal_id=1 AND path='Unethicalbehaviour'),7);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Retraction and Correction policies',(SELECT id FROM static_pages WHERE journal_id=1 AND path='RetractionandCorrectionpolicies'),8);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Procedures for Dealing with Complaints and Appeals',(SELECT id FROM static_pages WHERE journal_id=1 AND path='Procedures'),9);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Use of Generative AI and AI-Assisted Tools',(SELECT id FROM static_pages WHERE journal_id=1 AND path='GEN'),10);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Indexing & Refund Disclaimer Policy',(SELECT id FROM static_pages WHERE journal_id=1 AND path='indexing_refund_disclaimer_policy'),11);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',@eth,'static_page','Disclaimer',(SELECT id FROM static_pages WHERE journal_id=1 AND path='Disclaimer'),12);
INSERT INTO navigation_menu_items (journal_id,menu,parent_id,type,title,static_page_id,seq) VALUES (1,'primary',NULL,'announcements','Announcements',NULL,6);
INSERT INTO navigation_menu_items (journal_id,menu,type,title,seq) VALUES (1,'user','custom_url','Register',1),(1,'user','custom_url','Login',2);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'USER_REGISTER','Registration as a user at {$journalName}','Dear {$recipientName},

Thank you for registering with {$journalName}. Your username is {$username}.

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'PASSWORD_RESET','Password reset requested','Dear {$recipientName},

Use this link to choose a new password: {$passwordResetUrl}

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'SUBMISSION_ACK','Submission Acknowledgement','Dear {$authorName},

Thank you for submitting "{$submissionTitle}" to {$journalName}. You can track it at {$submissionUrl}.

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'SUBMISSION_NOTIFY_EDITORS','New submission received','A new submission, "{$submissionTitle}", has been received and awaits assignment: {$submissionUrl}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'REVIEW_REQUEST','Request to review a manuscript','Dear {$reviewerName},

We invite you to review "{$submissionTitle}". Please respond by {$responseDueDate}: {$reviewUrl}

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'REVIEW_ACK','Thank you for your review','Dear {$reviewerName},

Thank you for reviewing "{$submissionTitle}".

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'EDITOR_DECISION_ACCEPT','Editor decision: accepted','Dear {$authorName},

We are pleased to accept "{$submissionTitle}" for publication.

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'EDITOR_DECISION_REVISIONS','Editor decision: revisions required','Dear {$authorName},

Revisions are required for "{$submissionTitle}". Reviewer comments follow.

{$signature}',1,0);
INSERT INTO email_templates (journal_id,email_key,subject,body,enabled,is_custom) VALUES (1,'EDITOR_DECISION_DECLINE','Editor decision: declined','Dear {$authorName},

We regret that "{$submissionTitle}" cannot be accepted.

{$signature}',1,0);
INSERT INTO announcements (journal_id,title,description_short,description,date_posted) VALUES (1,'Welcome to JIRS','Placeholder announcement.','<p>Placeholder announcement body.</p>',NOW());
