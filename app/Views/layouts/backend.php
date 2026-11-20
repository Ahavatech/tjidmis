<?php use App\Core\{Settings, Auth}; use App\Services\{MenuService, NotificationService};
$u = Auth::user(); $name = Settings::get('name', 'Journal'); $logo = Settings::get('logo', ''); $unread = NotificationService::unread((int)$u['id']);
$items = MenuService::items(); $uname = trim($u['given_name'] . ' ' . $u['family_name']); ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title><?= e(($title ?? '') . ' | ' . $name) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/backend.css')) ?>">
<style>:root{--theme-color:<?= e(preg_match('/^#[0-9a-fA-F]{3,8}$/', Settings::get('themeColor', '')) ? Settings::get('themeColor') : '#d79f24') ?>}</style></head>
<body class="backend">
<?= \App\Core\View::partial('partials/loginas_banner') ?>
<header class="be_header">
  <button class="be_toggle" type="button" aria-controls="beSidebar" aria-expanded="false"><?= e(t('nav.open_menu')) ?></button>
  <a class="be_journal" href="<?= e(url('submissions')) ?>"><?= e($name) ?></a>
  <span class="be_spacer"></span>
  <a class="be_link" href="<?= e(url()) ?>" target="_blank" rel="noopener"><?= e(t('be.view_site')) ?></a>
  <a class="be_bell" href="<?= e(url('notification')) ?>" title="<?= e(t('notif.title')) ?>" aria-label="<?= e(t('notif.title')) ?>"><span aria-hidden="true">&#128276;</span><?php if ($unread): ?><span class="be_badge"><?= (int)$unread ?></span><?php endif; ?></a>
  <details class="be_user"><summary><span aria-hidden="true" class="be_usericon">&#128100;</span> <span class="be_username"><?= e($uname) ?></span></summary><ul>
    <li><a href="<?= e(url('user', 'profile')) ?>"><?= e(t('be.profile')) ?></a></li>
    <li><a href="<?= e(url('login', 'signOut')) ?>"><?= e(t('nav.logout')) ?></a></li></ul></details>
</header>
<?php $cur = strtok($_SERVER['REQUEST_URI'] ?? '', '?'); ?>
<div class="be_shell">
  <aside class="be_sidebar" id="beSidebar" aria-label="<?= e(t('menu.label')) ?>">
    <nav><ul class="be_menu"><?php foreach ($items as $it): ?>
      <?php if (!empty($it['children'])): ?><li class="be_group"><span class="be_grouphead"><?= e($it['label']) ?></span><ul><?php foreach ($it['children'] as $c): $cp = strtok($c['href'], '?'); $on = ($menu ?? '') === $it['key'] && str_starts_with($cur, $cp); ?><li><a class="<?= $on ? 'active' : '' ?>" href="<?= e($c['href']) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= e($c['label']) ?></a></li><?php endforeach; ?></ul></li>
      <?php else: $on = ($menu ?? '') === $it['key']; ?><li><a class="<?= $on ? 'active' : '' ?>" href="<?= e($it['href']) ?>"<?= $on ? ' aria-current="page"' : '' ?>><?= e($it['label']) ?></a></li><?php endif; ?>
    <?php endforeach; ?></ul></nav>
  </aside>
  <div class="be_main">
    <main class="be_content" id="main">
      <?php foreach (flash() ?? [] as [$ft, $fm]): ?><div class="flash flash_<?= e($ft) ?>" role="status"><?= e($fm) ?></div><?php endforeach; ?>
      <?= $content ?>
    </main>
  </div>
</div>
<script src="<?= e(asset('js/backend.js')) ?>"></script>
</body></html>
