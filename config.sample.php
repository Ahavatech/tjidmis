<?php
// Copied to config.php by install.php. Do not commit real credentials.
return [
    'db' => ['host' => 'localhost', 'name' => 'journal', 'user' => 'root', 'pass' => '', 'charset' => 'utf8mb4'],
    'files_dir'       => dirname(__DIR__) . '/journal_files', // OUTSIDE the web root
    'base_url'        => 'https://example.org', // scheme + host used in e-mailed links (the installer sets it)
    'url_mode'        => 'path',   // 'path' = index.php/jidmis/about ; 'query' = index.php?route=jirs/about
    'pretty_urls'     => false,    // true = /jidmis/about (needs mod_rewrite + .htaccess)
    'default_journal' => 'jidmis',
    'timezone'        => 'Africa/Lagos',
    'debug'           => false,
    'demo_mode'       => false,    // shows demo credentials on the login page
    'smtp'            => ['host' => '', 'port' => 587, 'user' => '', 'pass' => '', 'secure' => 'tls', 'from' => ''],
];
