<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings};
use App\Models\Catalog;
class SearchController {
    public function index(): void { $this->search(); }
    public function search(): void {
        $jid = (int)Settings::journal()['id']; $per = max(1, (int)Settings::get('itemsPerPage', '25'));
        $g = $_GET; $page = max(1, (int)($g['searchPage'] ?? 1));
        $date = function (string $pfx, bool $end) use ($g) {
            $y = (int)($g[$pfx . 'Year'] ?? 0); if (!$y) return null;
            $m = (int)($g[$pfx . 'Month'] ?? 0) ?: ($end ? 12 : 1);
            $d = (int)($g[$pfx . 'Day'] ?? 0) ?: ($end ? (int)date('t', mktime(0, 0, 0, $m, 1, $y)) : 1);
            return checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
        };
        $f = ['query' => trim((string)($g['query'] ?? '')), 'authors' => trim((string)($g['authors'] ?? '')), 'from' => $date('dateFrom', false), 'to' => $date('dateTo', true)];
        $searched = $f['query'] !== '' || $f['authors'] !== '' || $f['from'] || $f['to'];
        [$total, $rows] = $searched ? Catalog::search($jid, $f, $per, ($page - 1) * $per) : [0, []];
        View::render('public/search', ['title' => t('nav.search'), 'bodyClass' => 'search', 'f' => $f, 'g' => $g, 'searched' => $searched, 'total' => $total, 'rows' => $rows, 'page' => $page, 'per' => $per]);
    }
}
