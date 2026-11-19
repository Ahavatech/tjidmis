<?php
namespace App\Core;
use App\Controllers\Public as P;
use App\Controllers\Backend as B;
class Router {
    /** page => [Controller class, default op]. Later phases add rows. */
    private array $routes = [
        'index'        => [P\IndexController::class, 'index'],
        'about'        => [P\AboutController::class, 'index'],
        'issue'        => [P\IssueController::class, 'current'],
        'article'      => [P\ArticleController::class, 'view'],
        'announcement' => [P\AnnouncementController::class, 'index'],
        'information'  => [P\InformationController::class, 'readers'],
        'search'       => [P\SearchController::class, 'search'],
        'gateway'      => [P\GatewayController::class, 'plugin'],
        'user'         => [P\UserController::class, 'register'],
        'login'        => [P\LoginController::class, 'index'],
        'submissions'  => [B\DashboardController::class, 'index'],
        'notification' => [B\NotificationController::class, 'index'],
        'management'   => [B\ManagementController::class, 'users'],
    ];
    public function dispatch(): void {
        Request::parse();
        $seg = Request::segments();
        $journal = Settings::journal();
        if (Request::isPost() && !Csrf::check()) { http_response_code(400); echo t('error.csrf'); return; }
        if (!$journal) {
            if (($seg[0] ?? '') === 'index' || !$seg) redirect(url(null, null, [], [], config('default_journal')));
            $this->notFound(); return;
        }
        $page = $seg[1] ?? 'index';
        $op = $seg[2] ?? null;
        $args = array_slice($seg, 3);
        if (isset($this->routes[$page])) {
            [$cls, $def] = $this->routes[$page];
            $op = $op ?: $def;
            $obj = new $cls();
            if (!preg_match("/^[a-zA-Z]\w*$/", $op) || !is_callable([$obj, $op])) { $this->notFound(); return; }
            $obj->$op(...$args);
            return;
        }
        // Unknown page: try a static page whose path matches (e.g. /pes, /APCharges)
        $sp = DB::one('SELECT * FROM static_pages WHERE journal_id=? AND path=?', [$journal['id'], $page]);
        if ($sp) { View::render('public/static_page', ['page' => $sp, 'title' => $sp['title']]); return; }
        $this->notFound();
    }
    private function notFound(): void {
        http_response_code(404);
        View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]);
    }
}
