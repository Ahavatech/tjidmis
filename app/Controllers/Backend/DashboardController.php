<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth};
class DashboardController {
    public function index(): void {
        Auth::requireLogin();
        $r = Auth::roles(); $staff = (bool)array_intersect(['editor', 'manager', 'site_admin', 'section_editor'], $r);
        $tabs = ['queue' => t('dash.my_queue')];
        if (array_intersect(['editor', 'manager', 'site_admin'], $r)) { $tabs['unassigned'] = t('dash.unassigned'); $tabs['active'] = t('dash.all_active'); }
        $tabs['archives'] = t('dash.archives');
        View::render('backend/dashboard', ['title' => t('menu.submissions'), 'menu' => 'submissions', 'tabs' => $tabs, 'canSubmit' => in_array('author', $r, true), 'staff' => $staff], 'backend');
    }
}
