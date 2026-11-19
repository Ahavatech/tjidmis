<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
define('APP', __DIR__);

spl_autoload_register(function (string $c) {
    if (strpos($c, 'App\\') !== 0) return;
    $f = APP . '/' . str_replace('\\', '/', substr($c, 4)) . '.php';
    if (is_file($f)) require $f;
});
if (is_file(ROOT . '/vendor/autoload.php')) require ROOT . '/vendor/autoload.php';

if (!is_file(ROOT . '/config.php')) { header('Location: install.php'); exit; }
$GLOBALS['config'] = require ROOT . '/config.php';
date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

ini_set('display_errors', !empty($GLOBALS['config']['debug']) ? '1' : '0');
error_reporting(E_ALL);
set_error_handler(function ($no, $str, $file, $line) {
    App\Core\Log::write("PHP[$no] $str in $file:$line");
    return !empty($GLOBALS['config']['debug']) ? false : true;
});
set_exception_handler(function (Throwable $e) {
    App\Core\Log::write('EXCEPTION ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (!empty($GLOBALS['config']['debug'])) { echo '<pre>' . htmlspecialchars((string)$e) . '</pre>'; return; }
    echo '<!DOCTYPE html><meta charset="utf-8"><title>Error</title><h1>Something went wrong</h1><p>Please try again later.</p>';
});

require APP . '/Core/helpers.php';
App\Core\Auth::start();
