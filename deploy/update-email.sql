-- Sets editor@tjidmis.org as the journal contact, technical support contact, envelope sender, site contact and admin email.
-- For a LIVE site that is already set up: this does not touch the menu, pages or journal text (unlike site-setup.sql).
-- Assumes journal id 1 and admin user id 1; add your table prefix if your OJS uses one.
SET NAMES utf8mb4;
REPLACE INTO `journal_settings` VALUES (1,'','contactEmail','editor@tjidmis.org','string');
REPLACE INTO `journal_settings` VALUES (1,'','supportEmail','editor@tjidmis.org','string');
REPLACE INTO `journal_settings` VALUES (1,'','envelopeSender','editor@tjidmis.org','string');
REPLACE INTO `site_settings` (`setting_name`,`locale`,`setting_value`) VALUES ('contactEmail','en_US','editor@tjidmis.org');
UPDATE `users` SET `email`='editor@tjidmis.org' WHERE `user_id`=1;
