<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings};
use App\Models\Catalog;
class IssueController {
    public function current(): void {
        $GLOBALS['pkp']['op'] = 'view';
        $jid = (int)Settings::journal()['id'];
        $issue = Catalog::currentIssue($jid);
        if (!$issue) { View::render('public/issue', ['title' => t('nav.current'), 'issue' => null, 'toc' => [], 'crumbs' => [[t('nav.current'), null]], 'bodyClass' => 'issue']); return; }
        View::render('public/issue', ['title' => Catalog::issueLabel($issue), 'issue' => $issue, 'toc' => Catalog::toc((int)$issue['id']),
            'crumbs' => [[t('nav.archives'), url('issue', 'archive')], [Catalog::issueLabel($issue), null]], 'bodyClass' => 'issue']);
    }
    public function archive(string $page = '1'): void {
        $jid = (int)Settings::journal()['id'];
        $per = max(1, (int)Settings::get('itemsPerPage', '25')); $p = max(1, (int)$page);
        View::render('public/archive', ['title' => t('nav.archives'), 'bodyClass' => 'issue_archive', 'issues' => Catalog::archive($jid, $p, $per),
            'total' => Catalog::archiveCount($jid), 'page' => $p, 'per' => $per]);
    }
    public function view(string $id = ''): void {
        $jid = (int)Settings::journal()['id'];
        $issue = Catalog::issue($jid, $id);
        if (!$issue) { http_response_code(404); View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]); return; }
        View::render('public/issue', ['title' => Catalog::issueLabel($issue), 'issue' => $issue, 'toc' => Catalog::toc((int)$issue['id']),
            'crumbs' => [[t('nav.archives'), url('issue', 'archive')], [Catalog::issueLabel($issue), null]], 'bodyClass' => 'issue']);
    }
}
