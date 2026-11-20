<?php
namespace App\Controllers\Public;
use App\Core\Settings;
use App\Services\FeedService;
use App\Models\Catalog;
class GatewayController {
    /** LOCKSS / CLOCKSS publisher manifest: one link per published issue, as the OJS archive plugins output it. */
    public function manifest(string $kind): void {
        $key = $kind === 'clockss' ? 'enableClockss' : 'enableLockss'; $label = strtoupper($kind);
        if (Settings::get($key, '') !== '1') { http_response_code(404); echo 'Not found'; return; }
        $jid = (int)Settings::journal()['id']; $h = '<!DOCTYPE html><html><head><title>' . e(t('arch.manifest_title', $label)) . '</title><meta charset="utf-8"></head><body><h1>' . e(t('arch.manifest_title', $label)) . '</h1><p>' . e(Settings::journal()['name'] ?? '') . '</p>'
            . '<p>' . e(t('arch.manifest_permission', $label)) . '</p><ul>';
        foreach (Catalog::archive($jid, 1, 1000) as $i) $h .= '<li><a href="' . e(abs_url(url('issue', 'view', [$i['id']]))) . '">' . e(Catalog::issueLabel($i)) . '</a></li>';
        header('Content-Type: text/html; charset=utf-8'); echo $h . '</ul><p><a href="' . e(abs_url(url('index'))) . '">' . e(t('arch.about_heading')) . '</a></p></body></html>';
    }
    public function lockss(): void { $this->manifest('lockss'); }
    public function clockss(): void { $this->manifest('clockss'); }
    public function plugin(string $name = '', string $fmt = ''): void {
        if ($name === 'lockss' || $name === 'clockss') { $this->manifest($name); return; }
        if (!feature('webFeeds') || $name !== 'WebFeedGatewayPlugin' || !in_array($fmt, ['atom', 'rss2', 'rss'], true)) { http_response_code(404); echo 'Not found'; return; }
        [$type, $body] = FeedService::render((int)Settings::journal()['id'], $fmt);
        header('Content-Type: ' . $type . '; charset=utf-8'); echo $body;
    }
}
