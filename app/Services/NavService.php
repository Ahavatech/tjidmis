<?php
namespace App\Services;
use App\Core\DB;
class NavService {
    public static function primary(int $jid): array {
        $rows = DB::all("SELECT n.*, sp.path sp_path FROM navigation_menu_items n LEFT JOIN static_pages sp ON sp.id=n.static_page_id
            WHERE n.journal_id=? AND n.menu='primary' ORDER BY n.seq, n.id", [$jid]);
        $top = []; $kids = [];
        foreach ($rows as $r) { $r['href'] = self::href($r); if ($r['parent_id']) $kids[$r['parent_id']][] = $r; else $top[] = $r; }
        foreach ($top as &$t) $t['children'] = $kids[$t['id']] ?? [];
        return $top;
    }
    public static function href(array $r): string {
        return match ($r['type']) {
            'about' => url('about'), 'editorial_team' => url('about', 'editorialTeam'), 'submissions' => url('about', 'submissions'),
            'privacy' => url('about', 'privacy'), 'contact' => url('about', 'contact'), 'current' => url('issue', 'current'),
            'archives' => url('issue', 'archive'), 'announcements' => url('announcement'), 'search' => url('search'),
            'information_readers' => url('information', 'readers'), 'information_authors' => url('information', 'authors'),
            'information_librarians' => url('information', 'librarians'),
            'static_page' => $r['sp_path'] ? url($r['sp_path']) : '#',
            default => $r['url'] ?: url('about'),   // parent items without a URL link to About, as the reference does
        };
    }
}
