<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings};
/** Users & Roles list (with Login As) is real; the other areas are stubs until Phase 6/7. */
class ManagementController {
    private function stub(string $key, string $title, array $roles, string $phase): void {
        Auth::require($roles);
        View::render('backend/stub', ['title' => $title, 'menu' => $key, 'phase' => $phase], 'backend');
    }
    public function issues(): void { redirect(url('manageIssues')); }
    public function announcements(): void { $this->stub('announcements', t('menu.announcements'), ['editor', 'manager', 'site_admin'], '7'); }
    public function settings(string $tab = ''): void { $this->stub('settings', t('menu.settings'), ['manager', 'site_admin'], '7'); }
    public function statistics(): void { $this->stub('tools', t('menu.statistics'), ['editor', 'manager', 'site_admin'], '7'); }
    public function importExport(): void { $this->stub('tools', t('menu.import_export'), ['editor', 'manager', 'site_admin'], '7'); }
    public function admin(): void { $this->stub('admin', t('menu.admin'), ['site_admin'], '7'); }
    public function users(): void {
        Auth::require(['manager', 'site_admin']);
        $jid = (int)Settings::journal()['id'];
        $rows = DB::all("SELECT u.id,u.username,u.email,u.given_name,u.family_name,u.disabled, GROUP_CONCAT(DISTINCT g.name ORDER BY g.id SEPARATOR ', ') roles
            FROM users u LEFT JOIN user_user_groups x ON x.user_id=u.id LEFT JOIN user_groups g ON g.id=x.user_group_id AND (g.journal_id IS NULL OR g.journal_id=?)
            GROUP BY u.id ORDER BY u.id", [$jid]);
        View::render('backend/users', ['title' => t('menu.users'), 'menu' => 'users', 'rows' => $rows], 'backend');
    }
}
