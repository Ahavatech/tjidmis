<?php
namespace App\Controllers\Public;
use App\Core\Settings;
/** Serves the reference theme CSS recoloured with the journal's theme colour (only used once the colour differs from the original gold). */
class CssController {
    public function theme(): void { $this->out(ROOT . '/assets/css/theme.css', true); }
    public function journal(): void {
        $f = Settings::get('journalStyleSheet', ''); $p = $f ? ROOT . '/public/' . $f : '';
        if (!$p || !preg_match('#^journals/\d+/styleSheet\.css$#', $f) || !is_file($p)) { http_response_code(404); return; }
        $this->out($p, false);
    }
    private function out(string $path, bool $fonts): void {
        $c = strtolower(Settings::get('themeColor', '#d79f24')); if (!preg_match('/^#[0-9a-f]{6}$/', $c)) $c = '#d79f24';
        $css = file_get_contents($path);
        $css = str_ireplace(['#d79f24', '#b9851a', '#e6b54a', '#f7bc4a'], [$c, shade_color($c, -0.22), shade_color($c, 0.15), shade_color($c, 0.2)], $css);
        if ($fonts) $css = str_replace('../fonts/', asset('fonts/'), $css);
        $etag = '"' . md5($css) . '"';
        header('Content-Type: text/css; charset=utf-8'); header('Cache-Control: public, max-age=3600'); header('ETag: ' . $etag);
        if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); return; }
        echo $css;
    }
}
