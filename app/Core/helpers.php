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

function public_url(string $file): string { $b = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); return $b . '/public/' . ltrim($file, '/'); }
function fmt_date(?string $d): string { return $d ? date('Y-m-d', strtotime($d)) : ''; }
/** $crumbs: list of [label, url|null]; the last item is the current page. Home is added automatically. */
function breadcrumb(array $crumbs): string {
    array_unshift($crumbs, [t('crumb.home'), url()]);
    $h = '<nav class="cmp_breadcrumbs" role="navigation" aria-label="' . e(t('crumb.you_are_here')) . '"><ol>';
    $last = count($crumbs) - 1;
    foreach ($crumbs as $i => [$label, $href]) {
        if ($i === $last) $h .= '<li class="current" aria-current="page"><span aria-current="page">' . e($label) . '</span></li>';
        else $h .= '<li><a href="' . e($href) . '">' . e($label) . '</a> <span class="separator">/</span></li>';
    }
    return $h . '</ol></nav>';
}
function paginate(int $total, int $page, int $per, callable $urlFor): string {
    $pages = (int)ceil($total / max(1, $per)); if ($pages <= 1) return '';
    $h = '<div class="cmp_pagination"><span class="showing">' . e(t('page.showing', ($page - 1) * $per + 1, min($total, $page * $per), $total)) . '</span> ';
    for ($i = 1; $i <= $pages; $i++) $h .= $i === $page ? '<strong class="current">' . $i . '</strong> ' : '<a href="' . e($urlFor($i)) . '">' . $i . '</a> ';
    return $h . '</div>';
}
function abs_url(string $path): string { $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $path; }
