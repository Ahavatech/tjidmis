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
function asset(string $path): string { $b = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); return $b . '/assets/' . ltrim($path, '/\\'); }
function redirect(string $to): never { header('Location: ' . $to); exit; }
function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function old(string $k, $d = '') { return $_SESSION['old'][$k] ?? $d; }

function public_url(string $file): string { $b = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\'); return $b . '/public/' . ltrim($file, '/\\'); }
function fmt_date(?string $d): string { return $d ? date(\App\Core\Settings::get('dateFormat', 'Y-m-d') ?: 'Y-m-d', strtotime($d)) : ''; }
/** $crumbs: list of [label, url|null]; the last item is the current page. Home is added automatically. */
function breadcrumb(array $crumbs): string {
    array_unshift($crumbs, [t('crumb.home'), url('index')]);
    $h = '<nav class="cmp_breadcrumbs" role="navigation" aria-label="' . e(t('crumb.you_are_here')) . '">' . "
	<ol>";
    $last = count($crumbs) - 1;
    foreach ($crumbs as $i => [$label, $href]) {
        if ($i === $last) $h .= "
		" . '<li class="current"' . ($last > 1 ? ' aria-current="page"' : '') . ">
			" . '<span aria-current="page">' . "
				" . e($label) . "
			" . "</span>
		</li>";
        else $h .= "
		<li>
			" . '<a href="' . e($href) . '">' . "
				" . e($label) . "
			</a>
			" . '<span class="separator">/</span>' . "
		</li>";
    }
    return $h . "
	</ol>
</nav>";
}
function paginate(int $total, int $page, int $per, callable $urlFor): string {
    $pages = (int)ceil($total / max(1, $per)); if ($pages <= 1) return '';
    $h = '<div class="cmp_pagination" aria-label="' . e(t('page.label')) . '">';
    if ($page > 1) $h .= "
				" . '<a class="prev" href="' . e($urlFor($page - 1)) . '">' . e(t('page.prev')) . '</a>';
    $h .= "
				" . '<span class="current">' . "
			" . (($page - 1) * $per + 1) . '-' . min($total, $page * $per) . ' of ' . $total . "
		" . '</span>';
    if ($page < $pages) $h .= "
					" . '<a class="next" href="' . e($urlFor($page + 1)) . '">' . e(t('page.next')) . '</a>';
    return $h . "
			" . '</div>';
}
/** Absolute URL for e-mails and feeds. Uses 'base_url' from config.php (written by the installer) so a forged Host header can never poison reset links. */
function abs_url(string $path): string {
    $base = (string)config('base_url', '');
    if ($base === '') {   // fallback for old installs: only a syntactically valid host is accepted
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost'); if (!preg_match('/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $host)) $host = 'localhost';
        $base = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . $host;
    }
    return rtrim($base, '/') . $path;
}

/** Only allow same-site relative paths as post-login redirect targets (prevents open redirects). */
function safe_source(?string $s): string {
    $s = (string)$s;
    return ($s !== '' && $s[0] === '/' && !preg_match('#^//|\\\\|[\r\n]#', $s)) ? $s : '';
}
/** OJS-style form field. $o: type, required, maxlength, autocomplete, error (string), options (for select), class. */
function field(string $label, string $name, $value = '', array $o = []): string {
    $type = $o['type'] ?? 'text'; $req = !empty($o['required']); $err = $o['error'] ?? '';
    $h = '<div class="' . e($o['class'] ?? $name) . ($err ? ' has_error' : '') . '"><label><span class="label">' . e($label)
        . ($req ? ' <span class="required" aria-hidden="true">*</span><span class="pkp_screen_reader"> ' . e(t('form.required')) . '</span>' : '') . '</span>';
    if ($type === 'select') {
        $h .= '<select name="' . e($name) . '" id="' . e($name) . '"' . ($req ? ' required aria-required="true"' : '') . '><option></option>';
        foreach ($o['options'] ?? [] as $v => $l) $h .= '<option value="' . e($v) . '"' . ((string)$value === (string)$v ? ' selected' : '') . '>' . e($l) . '</option>';
        $h .= '</select>';
    } elseif ($type === 'textarea') {
        $h .= '<textarea name="' . e($name) . '" id="' . e($name) . '" rows="' . (int)($o['rows'] ?? 5) . '">' . e($value) . '</textarea>';
    } else {
        $h .= '<input type="' . e($type) . '" name="' . e($name) . '" id="' . e($name) . '"' . ($type === 'password' ? ' password="true"' : ' value="' . e($value) . '"')
            . (isset($o['maxlength']) ? ' maxlength="' . (int)$o['maxlength'] . '"' : '') . (isset($o['autocomplete']) ? ' autocomplete="' . e($o['autocomplete']) . '"' : '')
            . ($req ? ' required aria-required="true"' : '') . '>';
    }
    if (!empty($o['help'])) $h .= '<span class="help">' . e($o['help']) . '</span>';
    return $h . '</label>' . ($err ? '<span class="field_error" role="alert">' . e($err) . '</span>' : '') . '</div>';
}
function checkbox(string $name, string $labelHtml, bool $checked = false, string $value = '1', string $err = ''): string {
    return '<div class="optin"><label><input type="checkbox" name="' . e($name) . '" value="' . e($value) . '"' . ($checked ? ' checked' : '') . '> ' . $labelHtml . '</label>'
        . ($err ? '<span class="field_error" role="alert">' . e($err) . '</span>' : '') . '</div>';
}
function errors_summary(array $errors): string {
    if (!$errors) return '';
    $h = '<div class="cmp_notification error" role="alert"><strong>' . e(t('form.fix_errors')) . '</strong><ul>';
    foreach ($errors as $m) $h .= '<li>' . e($m) . '</li>';
    return $h . '</ul></div>';
}

/** OJS-style grid: header with title and add-links, columns, rows whose Edit/Delete links open under the row, "No Items" when empty.
 *  $cols: [[label, width%, align]]; $rows: [['cells' => [html...], 'actions' => [[label, url, ['post' => bool, 'confirm' => text, 'disabled' => bool, 'class' => 'delete']]]]]. Cell html must already be escaped. */
function grid(string $title, array $links, array $cols, array $rows, ?string $empty = null): string {
    static $n = 0; $id = 'grid' . (++$n); $span = count($cols);
    $h = '<div id="' . $id . '" class="pkp_controllers_grid"><div class="header"><h4>' . e($title) . '</h4>';
    if ($links) { $h .= '<ul class="actions">'; foreach ($links as $l) $h .= '<li><a class="pkp_controllers_linkAction pkp_linkaction_icon_add" href="' . e($l[1]) . '">' . e($l[0]) . '</a></li>'; $h .= '</ul>'; }
    $h .= '</div><table id="' . $id . '-table"><colgroup>';
    foreach ($cols as $c) $h .= '<col class="grid-column"' . (!empty($c[1]) ? ' style="width: ' . (int)$c[1] . '%;"' : '') . '>';
    $h .= '</colgroup><thead><tr>';
    foreach ($cols as $c) $h .= '<th scope="col" style="text-align: ' . e($c[2] ?? 'left') . ';">' . e($c[0]) . '</th>';
    $h .= '</tr></thead>';
    if ($rows) {
        $h .= '<tbody>';
        foreach ($rows as $i => $r) {
            $acts = $r['actions'] ?? []; $rid = $id . '-row-' . ($i + 1);
            $h .= '<tr id="' . $rid . '" class="gridRow' . ($acts ? ' has_extras' : '') . '">';
            foreach ($r['cells'] as $k => $cell) {
                $first = $k === 0;
                $h .= '<td' . ($first ? ' class="first_column"' : (($cols[$k][2] ?? '') === 'center' ? ' class="pkp_helpers_text_center"' : '')) . '>';
                if ($first && $acts) $h .= '<a href="#" class="show_extras" aria-expanded="false"><span class="pkp_screen_reader">' . e(t('grid.settings')) . '</span></a>';
                $h .= '<span class="gridCellContainer"><span class="label">' . $cell . '</span></span></td>';
            }
            $h .= '</tr>';
            if ($acts) {
                $h .= '<tr id="' . $rid . '-control-row" class="row_controls"><td colspan="' . $span . '"><form method="post" class="grid_row_form">' . csrf_field();
                foreach ($acts as $a) {
                    $o = $a[2] ?? []; $cls = 'pkp_controllers_linkAction pkp_linkaction_icon_' . e($o['class'] ?? 'edit');
                    if (!empty($o['post'])) $h .= '<button type="submit" class="' . $cls . '" formaction="' . e($a[1]) . '"' . (!empty($o['disabled']) ? ' disabled' : '') . (!empty($o['confirm']) ? ' onclick="return confirm(' . e(json_encode($o['confirm'])) . ')"' : '') . '>' . e($a[0]) . '</button>';
                    else $h .= '<a class="' . $cls . '" href="' . e($a[1]) . '">' . e($a[0]) . '</a>';
                }
                $h .= '</form></td></tr>';
            }
        }
        $h .= '</tbody>';
    } else $h .= '<tbody class="empty"><tr><td colspan="' . $span . '">' . e($empty ?? t('grid.no_items')) . '</td></tr></tbody>';
    return $h . '</table></div>';
}

/** Expands the portable tokens stored in imported HTML (__JURL__, __PUBLIC__, __ASSETS__, __SUBMIT_URL__). */
function rich(?string $html): string {
    if ($html === null || $html === '') return '';
    $b = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    return strtr($html, ['__JURL__' => url(), '__PUBLIC__' => $b . '/public', '__ASSETS__' => $b . '/assets', '__SUBMIT_URL__' => url('about', 'submissions')]);
}

/** Is a built-in feature switched on (Settings > Website > Plugins)? Everything defaults to on. */
function feature(string $key): bool { $f = \App\Core\Settings::json('enabledFeatures', []); return $f[$key] ?? true; }
/** Darken (negative) or lighten (positive) a #rrggbb colour by a 0..1 factor. */
function shade_color(string $hex, float $f): string {
    $hex = ltrim($hex, '#'); $c = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $c = array_map(fn($v) => max(0, min(255, (int)round($f >= 0 ? $v + (255 - $v) * $f : $v * (1 + $f)))), $c);
    return sprintf('#%02x%02x%02x', ...$c);
}
/** Stylesheet links in the original order. When the theme colour was changed, the theme and journal CSS are served recoloured. */
function theme_css_links(): string {
    $c = strtolower(\App\Core\Settings::get('themeColor', '#d79f24')); $dyn = $c !== '#d79f24'; $v = substr(md5($c), 0, 8); $css = \App\Core\Settings::get('journalStyleSheet', '');
    $h = '<link rel="stylesheet" href="' . e($dyn ? url('css', 'theme', [], ['v' => $v]) : asset('css/theme.css')) . '" type="text/css" />'
       . '<link rel="stylesheet" href="' . e(asset('css/fontawesome.css')) . '" type="text/css" />' . '<link rel="stylesheet" href="' . e(asset('css/google-fonts.css')) . '" type="text/css" />';
    if ($css) $h .= '<link rel="stylesheet" href="' . e($dyn ? url('css', 'journal', [], ['v' => $v]) : public_url($css)) . '" type="text/css" />';
    return $h;
}

/** Name and badge image of a Creative Commons licence URL, as OJS shows them on the article page. */
function cc_license(string $url): array {
    if (!preg_match('#creativecommons\.org/licenses/([a-z-]+)/(\d\.\d)#i', $url, $m)) return [$url, null];
    $names = ['by' => 'Attribution', 'by-sa' => 'Attribution-ShareAlike', 'by-nc' => 'Attribution-NonCommercial', 'by-nd' => 'Attribution-NoDerivatives', 'by-nc-sa' => 'Attribution-NonCommercial-ShareAlike', 'by-nc-nd' => 'Attribution-NonCommercial-NoDerivatives'];
    $type = strtolower($m[1]);
    return ['Creative Commons ' . ($names[$type] ?? strtoupper($type)) . ' ' . $m[2] . ' International License', '//i.creativecommons.org/l/' . $type . '/' . $m[2] . '/88x31.png'];
}

/** OJS-style e-mail obfuscation: the link is written by a script from a percent-encoded string, so scrapers do not see the address. */
function obfuscate_mailto(string $email): string {
    $html = '<a href="mailto:' . $email . '" >' . $email . '</a>';
    return implode('', array_map(fn($c) => '%' . bin2hex($c), str_split($html)));
}
