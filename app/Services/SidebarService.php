<?php
namespace App\Services;
use App\Core\{DB, Settings};
class SidebarService {
    public static function render(int $jid): string {
        $order = Settings::json('sidebarBlocks', []);
        $blocks = [];
        foreach (DB::all('SELECT * FROM custom_blocks WHERE journal_id=?', [$jid]) as $b) $blocks[$b['name']] = $b;
        $out = '';
        foreach ($order as $key) {
            if (strpos($key, 'custom:') === 0) {
                $b = $blocks[substr($key, 7)] ?? null; if (!$b) continue;
                $html = rich($b['content']);
                $out .= '<div class="pkp_block block_custom" id="customblock-' . e($b['name']) . '"><h2 class="title' . ($b['show_title'] ? '' : ' pkp_screen_reader') . '">'
                    . e($b['title']) . '</h2><div class="content">' . $html . '</div></div>';
            } elseif ($key === 'make_submission') {
                $out .= '<div class="pkp_block block_make_submission"><h2 class="pkp_screen_reader">' . e(t('subm.make_block')) . '</h2><div class="content"><a class="block_make_submission_link" href="' . e(url('about', 'submissions')) . '">' . e(t('subm.make_block')) . '</a></div></div>';
            } elseif ($key === 'information') {
                $out .= '<div class="pkp_block block_information"><h2 class="title">' . e(t('sidebar.information')) . '</h2><div class="content"><ul>'
                    . '<li><a href="' . e(url('information', 'readers')) . '">' . e(t('sidebar.readers')) . '</a></li>'
                    . '<li><a href="' . e(url('information', 'authors')) . '">' . e(t('sidebar.authors')) . '</a></li>'
                    . '<li><a href="' . e(url('information', 'librarians')) . '">' . e(t('sidebar.librarians')) . '</a></li></ul></div></div>';
            } elseif ($key === 'web_feed') {
                $f = fn($fmt, $img, $alt) => '<li><a href="' . e(url('gateway', 'plugin', ['WebFeedGatewayPlugin', $fmt])) . '"><img src="' . e(asset('img/feeds/' . $img)) . '" alt="' . e($alt) . '"></a></li>';
                $out .= '<div class="pkp_block block_web_feed"><h2 class="title">' . e(t('sidebar.current_issue')) . '</h2><div class="content"><ul>'
                    . $f('atom', 'atom.svg', 'Atom logo') . $f('rss2', 'rss20_logo.svg', 'RSS2 logo') . $f('rss', 'rss10_logo.svg', 'RSS1 logo') . '</ul></div></div>';
            }
        }
        return $out;
    }
}
