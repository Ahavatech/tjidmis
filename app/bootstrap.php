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
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>500</title><style>body{margin:0;font:16px/1.6 Georgia,serif;background:#f4f5f7;color:#1e2430}main{max-width:560px;margin:12vh auto;background:#fff;padding:2.5rem;border-radius:10px;box-shadow:0 1px 6px rgba(0,0,0,.1);border-top:4px solid #d79f24}h1{font:700 2rem Arial,sans-serif;color:#1f2a44;margin:0 0 .5rem}a{color:#1d5f8a}</style></head><body><main><h1>500</h1><p>Something went wrong on our side. The error has been logged. Please try again in a moment.</p><p><a href="javascript:history.back()">Go back</a></p></main></body></html>';
});

require APP . '/Core/helpers.php';
if (!headers_sent()) {   // baseline security headers on every response
    header('X-Content-Type-Options: nosniff'); header('X-Frame-Options: SAMEORIGIN'); header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') header('Strict-Transport-Security: max-age=31536000');
}
App\Core\Auth::start();
if (is_file(ROOT . '/install.lock') || is_file(ROOT . '/config.php')) App\Services\ScheduledTaskService::register();
