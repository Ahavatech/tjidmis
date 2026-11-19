<?php
// Dev router for: php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path) && !preg_match('#^/(app|install|vendor)/#', $path)) return false;
if (preg_match('#^(/install\.php)#', $path)) { require __DIR__ . '/install.php'; return true; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
if (strpos($path, '/index.php') === 0) $_SERVER['PATH_INFO'] = substr($path, 10);
else $_SERVER['PATH_INFO'] = $path;
require __DIR__ . '/index.php';
