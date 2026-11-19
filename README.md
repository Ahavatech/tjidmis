# JIRS journal platform (OJS 3.3 style, custom PHP)

PHP 8.1 + MySQL 5.7+/MariaDB 10.4+, PDO, no framework. URLs mirror OJS: `/index.php/jirs/about`.

## Deploy to cPanel
1. Zip the project (include `vendor/` if you add Composer libraries) and upload to `public_html` (or a subdomain folder), then Extract.
2. cPanel > MySQL Databases: create a database and user, add the user with ALL PRIVILEGES.
3. cPanel > Select PHP Version: 8.1, enable `pdo_mysql`, `mbstring`, `fileinfo`, `dom`, `gd`.
4. Create `/home/USER/journal_files` OUTSIDE `public_html` (writable).
5. Visit `https://yourdomain/install.php`, fill in the form, tick "Load demo data" for the demo.
6. Delete `install.php` afterwards (it also locks itself through `install.lock`).
7. If PATH_INFO URLs 404, set `'url_mode' => 'query'` in `config.php`. With mod_rewrite use `'pretty_urls' => true`.

## Local development
```
php -S localhost:8000 router.php
```
then open http://localhost:8000/install.php

## Demo accounts (demo data only), password `Demo@12345`
editor, sectioneditor, reviewer1, reviewer2, author, reader. The admin account is the one you create in the installer.
