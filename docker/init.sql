-- Local OJS database and user (dev only).
CREATE DATABASE IF NOT EXISTS ojs CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS 'journal'@'%' IDENTIFIED BY 'journalpass';
GRANT ALL ON ojs.* TO 'journal'@'%';
FLUSH PRIVILEGES;
