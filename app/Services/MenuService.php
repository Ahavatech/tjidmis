<?php
namespace App\Services;
use App\Core\Auth;
/** Role-aware back-end menu, in the same order and with the same labels as OJS 3.3: Submissions, Issues, Settings, Statistics, Tools, Administration. */
class MenuService {
    public static function items(): array {
        $r = Auth::roles(); $has = fn(array $k) => (bool)array_intersect($k, $r);
        $m = [['key' => 'submissions', 'label' => t('menu.submissions'), 'href' => url('submissions')]];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'issues', 'label' => t('menu.issues'), 'href' => url('manageIssues')];
        if ($has(['manager', 'site_admin']) && SubscriptionService::enabled()) $m[] = ['key' => 'subscriptions', 'label' => t('sub.menu'), 'href' => url('subscription')];
        if ($has(['manager', 'site_admin'])) $m[] = ['key' => 'settings', 'label' => t('menu.settings'), 'href' => url('settings', 'index', ['journal']), 'children' => [
            ['label' => t('menu.settings_journal'), 'href' => url('settings', 'index', ['journal'])], ['label' => t('menu.settings_website'), 'href' => url('settings', 'index', ['website'])],
            ['label' => t('menu.settings_workflow'), 'href' => url('settings', 'index', ['workflow'])], ['label' => t('menu.settings_distribution'), 'href' => url('settings', 'index', ['distribution'])],
            ['label' => t('menu.users'), 'href' => url('users')]]];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'statistics', 'label' => t('menu.statistics'), 'href' => url('statistics', 'articles'), 'children' => [
            ['label' => t('stat.menu_articles'), 'href' => url('statistics', 'articles')], ['label' => t('stat.menu_editorial'), 'href' => url('statistics', 'editorial')],
            ['label' => t('stat.menu_users'), 'href' => url('statistics', 'users')], ['label' => t('stat.menu_reports'), 'href' => url('statistics', 'reports')]]];
        if ($has(['editor', 'manager', 'site_admin'])) $m[] = ['key' => 'tools', 'label' => t('menu.tools'), 'href' => url('management', 'importExport')];
        if ($has(['site_admin'])) $m[] = ['key' => 'admin', 'label' => t('menu.admin'), 'href' => url('admin')];
        return $m;
    }
}
