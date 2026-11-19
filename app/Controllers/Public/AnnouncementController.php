<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings, DB};
use App\Models\Catalog;
class AnnouncementController {
    public function index(string $page = '1'): void {
        if (Settings::get('announcementsEnabled', '1') !== '1') { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]); return; }
        $jid = (int)Settings::journal()['id']; $per = max(1, (int)Settings::get('itemsPerPage', '25')); $p = max(1, (int)$page);
        View::render('public/announcements', ['title' => t('nav.announcements'), 'bodyClass' => 'announcements', 'items' => Catalog::announcements($jid, $per, ($p - 1) * $per),
            'total' => Catalog::announcementCount($jid), 'page' => $p, 'per' => $per]);
    }
    public function view(string $id = ''): void {
        $jid = (int)Settings::journal()['id'];
        $a = DB::one('SELECT * FROM announcements WHERE id=? AND journal_id=? AND (date_expire IS NULL OR date_expire>=CURDATE())', [(int)$id, $jid]);
        if (!$a) { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]); return; }
        View::render('public/announcement', ['title' => $a['title'], 'bodyClass' => 'announcements', 'a' => $a]);
    }
}
