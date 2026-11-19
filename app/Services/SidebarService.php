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
                $html = str_replace('__SUBMIT_URL__', e(url('about', 'submissions')), $b['content']);
                $out .= '<div class="pkp_block block_custom" id="customblock-' . e($b['name']) . '"><h2 class="title' . ($b['show_title'] ? '' : ' pkp_screen_reader') . '">'
                    . e($b['title']) . '</h2><div class="content">' . $html . '</div></div>';
            } elseif ($key === 'information') {
                $out .= '<div class="pkp_block block_information"><h2 class="title">' . e(t('sidebar.information')) . '</h2><div class="content"><ul>'
                    . '<li><a href="' . e(url('information', 'readers')) . '">' . e(t('sidebar.readers')) . '</a></li>'
                    . '<li><a href="' . e(url('information', 'authors')) . '">' . e(t('sidebar.authors')) . '</a></li>'
                    . '<li><a href="' . e(url('information', 'librarians')) . '">' . e(t('sidebar.librarians')) . '</a></li></ul></div></div>';
            } elseif ($key === 'web_feed') {
                $f = fn($fmt, $label) => '<li><a class="feed_link feed_' . $fmt . '" href="' . e(url('gateway', 'plugin', ['WebFeedGatewayPlugin', $fmt])) . '" title="' . e($label) . '"><span>' . e($label) . '</span></a></li>';
                $out .= '<div class="pkp_block block_web_feed"><h2 class="title">' . e(t('sidebar.current_issue')) . '</h2><div class="content"><ul>'
                    . $f('atom', 'Atom') . $f('rss2', 'RSS 2.0') . $f('rss', 'RSS 1.0') . '</ul></div></div>';
            }
        }
        return $out;
    }
}
