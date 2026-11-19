<?php
namespace App\Controllers\Public;
use App\Core\View;
/** Placeholder for account pages built in Phase 3 (register, login, lost password, dashboard). */
class PendingController {
    private function show(string $label): void {
        View::render('public/page_simple', ['title' => $label, 'bodyClass' => 'user', 'crumb' => $label, 'html' => '<p>' . e(t('pending.text')) . '</p>', 'cls' => 'page_pending']);
    }
    public function index(): void { $this->show(t('nav.login')); }
    public function register(): void { $this->show(t('nav.register')); }
    public function lostPassword(): void { $this->show(t('pending.lost')); }
    public function signOut(): void { $this->show(t('nav.logout')); }
    public function dashboard(): void { $this->show(t('pending.dashboard')); }
}
