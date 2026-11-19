<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Request};
class NotificationController {
    public function index(): void {
        Auth::requireLogin();
        $items = DB::all('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 100', [Auth::id()]);
        View::render('backend/notifications', ['title' => t('notif.title'), 'menu' => '', 'items' => $items], 'backend');
    }
    public function markRead(): void {
        Auth::requireLogin();
        if (Request::isPost()) DB::exec('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL', [Auth::id()]);
        redirect(url('notification'));
    }
}
