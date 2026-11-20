-- Databases for the local setup: our app and the real OJS 3.3 used as a reference.
CREATE DATABASE IF NOT EXISTS jidmis CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE DATABASE IF NOT EXISTS ojs CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'journal'@'%' IDENTIFIED BY 'journalpass';
GRANT ALL ON jidmis.* TO 'journal'@'%';
GRANT ALL ON ojs.* TO 'journal'@'%';
FLUSH PRIVILEGES;
