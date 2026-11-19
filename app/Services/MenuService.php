<?php
namespace App\Services;
use App\Core\Auth;
/** Role-aware back-end sidebar. Items appear only when a role allows them. */
class MenuService {
    public static function items(): array {
        $r = Auth::roles(); $has = fn(array $k) => (bool)array_intersect($k, $r);
        $m = [['key' => 'submissions', 'label' => t('menu.submissions'), 'href' => url('submissions')]];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'issues', 'label' => t('menu.issues'), 'href' => url('manageIssues')];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'announcements', 'label' => t('menu.announcements'), 'href' => url('manageAnnouncements')];
        if ($has(['manager', 'site_admin'])) $m[] = ['key' => 'settings', 'label' => t('menu.settings'), 'href' => url('settings', 'index', ['journal']), 'children' => [
            ['label' => t('menu.settings_journal'), 'href' => url('settings', 'index', ['journal'])], ['label' => t('menu.settings_website'), 'href' => url('settings', 'index', ['website'])],
            ['label' => t('menu.settings_workflow'), 'href' => url('settings', 'index', ['workflow'])], ['label' => t('menu.settings_distribution'), 'href' => url('settings', 'index', ['distribution'])]]];
        if ($has(['manager', 'site_admin'])) $m[] = ['key' => 'users', 'label' => t('menu.users'), 'href' => url('users')];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'tools', 'label' => t('menu.tools'), 'href' => url('statistics'), 'children' => [
            ['label' => t('menu.statistics'), 'href' => url('statistics')], ['label' => t('menu.import_export'), 'href' => url('management', 'importExport')]]];
        if ($has(['site_admin'])) $m[] = ['key' => 'admin', 'label' => t('menu.admin'), 'href' => url('admin')];
        return $m;
    }
}
