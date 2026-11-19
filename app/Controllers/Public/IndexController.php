<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings, DB};
class IndexController {
    public function index(): void {
        $j = Settings::journal();
        $issue = DB::one('SELECT * FROM issues WHERE journal_id=? AND is_current=1', [$j['id']]);
        View::render('public/home', ['issue' => $issue]);
    }
}
