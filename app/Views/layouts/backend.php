<?php use App\Core\{Settings, Auth}; use App\Services\{MenuService, NotificationService};
$u = Auth::user(); $name = Settings::get('name', 'Journal'); $logo = Settings::get('logo', ''); $unread = NotificationService::unread((int)$u['id']);
$items = MenuService::items(); $uname = trim($u['given_name'] . ' ' . $u['family_name']); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(($title ?? '') . ' | ' . $name) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/backend.css')) ?>">
<style>:root{--theme-color:<?= e(preg_match('/^#[0-9a-fA-F]{3,8}$/', Settings::get('themeColor', '')) ? Settings::get('themeColor') : '#d79f24') ?>}</style></head>
<body class="backend">
<?= \App\Core\View::partial('partials/loginas_banner') ?>
<div class="be_shell">
  <aside class="be_sidebar" id="beSidebar" aria-label="<?= e(t('menu.label')) ?>">
    <a class="be_brand" href="<?= e(url('submissions')) ?>"><span><?= e(Settings::get('abbreviation', $name)) ?></span></a>
    <nav><ul class="be_menu"><?php foreach ($items as $it): ?>
      <li class="<?= ($menu ?? '') === $it['key'] ? 'active' : '' ?>"><a href="<?= e($it['href']) ?>"><?= e($it['label']) ?></a>
        <?php if (!empty($it['children']) && ($menu ?? '') === $it['key']): ?><ul><?php foreach ($it['children'] as $c): ?><li><a href="<?= e($c['href']) ?>"><?= e($c['label']) ?></a></li><?php endforeach; ?></ul><?php endif; ?></li>
    <?php endforeach; ?></ul></nav>
  </aside>
  <div class="be_main">
    <header class="be_topbar">
      <button class="be_toggle" type="button" aria-controls="beSidebar" aria-expanded="false"><?= e(t('nav.open_menu')) ?></button>
      <span class="be_journal"><?= e($name) ?></span>
      <span class="be_spacer"></span>
      <a class="be_link" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= e(t('be.view_site')) ?></a>
      <a class="be_bell" href="<?= e(url('notification')) ?>" title="<?= e(t('notif.title')) ?>" aria-label="<?= e(t('notif.title')) ?>"><span aria-hidden="true">&#128276;</span><?php if ($unread): ?><span class="be_badge"><?= (int)$unread ?></span><?php endif; ?></a>
      <details class="be_user"><summary><?= e($uname) ?></summary><ul>
        <li><a href="<?= e(url('user', 'profile')) ?>"><?= e(t('be.profile')) ?></a></li>
        <li><a href="<?= e(url('login', 'signOut')) ?>"><?= e(t('nav.logout')) ?></a></li></ul></details>
    </header>
    <main class="be_content" id="main">
      <?php foreach (flash() ?? [] as [$ft, $fm]): ?><div class="flash flash_<?= e($ft) ?>" role="status"><?= e($fm) ?></div><?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= e(asset('js/backend.js')) ?>"></script>
</body></html>
