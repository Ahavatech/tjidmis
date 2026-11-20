<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth};
/** Legacy /management/* URLs from the first phases: they now forward to the real screens. Import/Export stays a stub. */
class ManagementController {
    public function users(): void { redirect(url('users')); }
    public function issues(): void { redirect(url('manageIssues')); }
    public function announcements(): void { redirect(url('manageAnnouncements')); }
    public function settings(string $area = ''): void { redirect(url('settings', 'index', $area !== '' ? [$area] : ['journal'])); }
    public function statistics(): void { redirect(url('statistics')); }
    public function admin(): void { redirect(url('admin')); }
    public function importExport(): void {
        Auth::require(['editor', 'manager', 'site_admin']);
        View::render('backend/stub', ['title' => t('menu.import_export'), 'menu' => 'tools', 'phase' => '-'], 'backend');
    }
}
