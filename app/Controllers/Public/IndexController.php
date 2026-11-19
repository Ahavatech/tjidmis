<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings};
use App\Models\Catalog;
class IndexController {
    public function index(): void {
        $jid = (int)Settings::journal()['id'];
        $issue = Catalog::currentIssue($jid);
        View::render('public/home', [
            'issue' => $issue, 'toc' => $issue ? Catalog::toc((int)$issue['id']) : [],
            'announcements' => Settings::get('numAnnouncementsHomepage', '3') > 0 ? Catalog::announcements($jid, (int)Settings::get('numAnnouncementsHomepage', '3')) : [],
            'bodyClass' => 'index',
        ]);
    }
}
