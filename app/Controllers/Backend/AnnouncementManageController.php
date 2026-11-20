<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Sanitizer};
use App\Services\EmailTemplateService;

/** /manageAnnouncements: list, create/edit, delete. Optional e-mail to readers who opted in to notifications. */
class AnnouncementManageController {
    const ROLES = ['editor', 'manager', 'site_admin'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(self::ROLES); }
    public function index(): void {
        $this->guard(); $per = 20; $page = max(1, (int)($_GET['page'] ?? 1)); $jid = $this->jid();
        $total = (int)DB::val('SELECT COUNT(*) FROM announcements WHERE journal_id=?', [$jid]);
        View::render('backend/announcements_list', ['title' => t('menu.announcements'), 'menu' => 'settings', 'rows' => DB::all("SELECT * FROM announcements WHERE journal_id=? ORDER BY date_posted DESC, id DESC LIMIT $per OFFSET " . (($page - 1) * $per), [$jid]),
            'total' => $total, 'page' => $page, 'per' => $per, 'enabled' => Settings::get('announcementsEnabled', '1') === '1'], 'backend');
    }
    public function form(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $a = $id !== '' ? DB::one('SELECT * FROM announcements WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$a) WorkflowController::deny(404);
        if (Request::isPost()) {
            $p = $_POST; $title = trim((string)($p['title'] ?? '')); $exp = trim((string)($p['date_expire'] ?? ''));
            if ($title === '') $errors['title'] = t('form.required_field');
            if ($exp !== '' && !(preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp) && strtotime($exp))) $errors['date_expire'] = t('rev.err_date');
            $full = Sanitizer::clean((string)($p['description'] ?? '')); if (!$errors && trim(strip_tags($full)) === '') $errors['description'] = t('form.required_field');
            if (!$errors) {
                $row = ['title' => mb_substr($title, 0, 255), 'description_short' => trim((string)($p['description_short'] ?? '')) ?: null, 'description' => $full, 'date_expire' => $exp ?: null];
                if ($a) { DB::exec('UPDATE announcements SET title=?, description_short=?, description=?, date_expire=? WHERE id=?', [$row['title'], $row['description_short'], $row['description'], $row['date_expire'], $a['id']]); $nid = (int)$a['id']; }
                else { $nid = DB::insert('announcements', $row + ['journal_id' => $jid, 'date_posted' => date('Y-m-d H:i:s')]);
                    if (!empty($p['send_email'])) { $n = 0; foreach (DB::all("SELECT u.email,u.given_name,u.family_name FROM users u JOIN user_settings us ON us.user_id=u.id AND us.name='emailConsent' AND us.value='1' WHERE u.disabled=0") as $u) {
                        EmailTemplateService::send('ANNOUNCEMENT', $u['email'], ['recipientName' => trim($u['given_name'] . ' ' . $u['family_name']), 'announcementTitle' => $title, 'announcementUrl' => abs_url(url('announcement', 'view', [$nid]))]); $n++; }
                        flash(t('ann.emailed', $n)); } }
                flash(t('ann.saved')); redirect(url('manageAnnouncements'));
            }
            $a = array_merge($a ?: [], $p);
        }
        View::render('backend/announcement_form', ['title' => t('ann.edit'), 'menu' => 'settings', 'a' => $a ?: [], 'errors' => $errors, 'isEdit' => $id !== ''], 'backend');
    }
    public function delete(string $id = ''): void {
        $this->guard(); if (Request::isPost()) { DB::exec('DELETE FROM announcements WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]); flash(t('set.deleted')); }
        redirect(url('manageAnnouncements'));
    }
}
