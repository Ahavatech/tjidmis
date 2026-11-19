<?php
use App\Core\{Lang, Csrf, Request};

function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function t(string $key, ...$args): string { return Lang::get($key, ...$args); }
function config(string $k, $default = null) { return $GLOBALS['config'][$k] ?? $default; }
function csrf_field(): string { return '<input type="hidden" name="csrfToken" value="' . e(Csrf::token()) . '">'; }

/** Build every link here: never hard-code URLs. */
function url(?string $page = null, ?string $op = null, array $args = [], array $query = [], ?string $journal = null): string {
    $journal = $journal ?? Request::journalPath();
    $segs = array_filter([$journal, $page, $op], fn($s) => $s !== null && $s !== '');
    foreach ($args as $a) $segs[] = rawurlencode((string)$a);
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    if (config('pretty_urls')) { $base = rtrim(dirname($script), '/\\'); $u = $base . '/' . implode('/', $segs); }
    elseif (config('url_mode') === 'query') { $u = $script . '?route=' . implode('/', $segs); }
    else { $u = $script . '/' . implode('/', $segs); }
    if ($query) $u .= (strpos($u, '?') === false ? '?' : '&') . http_build_query($query);
    return $u;
}
function asset(string $path): string { $b = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); return $b . '/assets/' . ltrim($path, '/'); }
function redirect(string $to): never { header('Location: ' . $to); exit; }
function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function old(string $k, $d = '') { return $_SESSION['old'][$k] ?? $d; }
