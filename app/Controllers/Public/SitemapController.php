<?php
namespace App\Controllers\Public;
use App\Core\{DB, Settings};

/** /{journal}/sitemap: the XML sitemap OJS 3.3 publishes (same URL order: front pages, static pages, search, issues with their articles and galleys). */
class SitemapController {
    public function index(): void {
        $jid = (int)Settings::journal()['id']; $u = [];
        $add = function (string $url) use (&$u) { $u[] = '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>'; };
        $add(abs_url(url())); $add(abs_url(url('user', 'register'))); $add(abs_url(url('login'))); $add(abs_url(url('announcement')));
        $add(abs_url(url('about'))); $add(abs_url(url('about', 'submissions'))); $add(abs_url(url('about', 'editorialTeam'))); $add(abs_url(url('about', 'contact')));
        foreach (DB::all('SELECT path FROM static_pages WHERE journal_id=? ORDER BY id', [$jid]) as $p) $add(abs_url(url($p['path'])));
        $add(abs_url(url('search'))); $add(abs_url(url('issue', 'current'))); $add(abs_url(url('issue', 'archive')));
        foreach (DB::all('SELECT id FROM issues WHERE journal_id=? AND published=1 ORDER BY seq ASC, date_published DESC, id DESC', [$jid]) as $i) {
            $add(abs_url(url('issue', 'view', [$i['id']])));
            foreach (DB::all("SELECT s.id sid, p.id pid FROM publications p JOIN submissions s ON s.current_publication_id=p.id WHERE p.issue_id=? AND p.status='published' AND s.status='published' ORDER BY p.seq, p.id DESC", [$i['id']]) as $a) {
                $add(abs_url(url('article', 'view', [$a['sid']])));
                foreach (DB::all('SELECT id FROM galleys WHERE publication_id=? ORDER BY seq, id', [$a['pid']]) as $g) $add(abs_url(url('article', 'view', [$a['sid'], $g['id']])));
            }
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8"?>' . "
" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode('', $u) . '</urlset>';
    }
}
