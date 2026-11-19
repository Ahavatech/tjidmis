<?php use App\Core\{Settings, Auth}; use App\Services\{NavService, SidebarService};
$jid = (int)Settings::journal()['id']; $name = Settings::get('name', 'Journal'); $user = Auth::user(); $logo = Settings::get('logo', '');
$nav = NavService::primary($jid); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') ? $title . ' | ' . $name : $name) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400;1,700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
<style>:root{--theme-color:<?= e(preg_match('/^#[0-9a-fA-F]{3,8}$/', Settings::get('themeColor', '')) ? Settings::get('themeColor') : '#d79f24') ?>}</style>
<?php if ($fav = Settings::get('favicon', '')): ?><link rel="icon" href="<?= e(public_url($fav)) ?>"><?php endif; ?>
<?= $headExtra ?? '' ?>
</head>
<body class="pkp_page_<?= e($bodyClass ?? 'index') ?>" dir="ltr">
<div class="pkp_structure_page">
<header class="pkp_structure_head" id="headerNavigationContainer" role="banner">
  <nav class="cmp_skip_to_content" aria-label="<?= e(t('nav.jump')) ?>">
    <a href="#pkp_content_main"><?= e(t('nav.skip_main')) ?></a>
    <a href="#siteNav"><?= e(t('nav.skip_nav')) ?></a>
    <a href="#pkp_content_footer"><?= e(t('nav.skip_footer')) ?></a>
  </nav>
  <div class="pkp_head_wrapper">
    <div class="pkp_site_name_wrapper">
      <button class="pkp_site_nav_toggle" type="button" aria-expanded="false" aria-controls="siteNav"><span><?= e(t('nav.open_menu')) ?></span></button>
      <h1 class="pkp_screen_reader"><?= e($name) ?></h1>
      <div class="pkp_site_name"><?php if ($logo): ?>
        <a href="<?= e(url()) ?>" class="is_img"><img src="<?= e(public_url($logo)) ?>" alt="<?= e($name) ?>"></a>
      <?php else: ?><a href="<?= e(url()) ?>" class="is_text"><?= e($name) ?></a><?php endif; ?></div>
    </div>
    <nav class="pkp_site_nav_menu" id="siteNavMenu" aria-label="<?= e(t('nav.site')) ?>"><a id="siteNav"></a>
      <div class="pkp_navigation_primary_row"><div class="pkp_navigation_primary_wrapper">
        <ul id="navigationPrimary" class="pkp_navigation_primary pkp_nav_list">
          <?php foreach ($nav as $n): ?>
          <li class="<?= $n['children'] ? 'has_children' : '' ?>"><a href="<?= e($n['href']) ?>"<?= $n['children'] ? ' aria-haspopup="true" aria-expanded="false"' : '' ?>><?= e($n['title']) ?></a>
            <?php if ($n['children']): ?><ul><?php foreach ($n['children'] as $c): ?><li><a href="<?= e($c['href']) ?>"><?= e($c['title']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
          </li><?php endforeach; ?>
        </ul>
        <div class="pkp_navigation_search_wrapper"><a href="<?= e(url('search')) ?>" class="pkp_search pkp_search_desktop"><span class="fa-search" aria-hidden="true"></span> <?= e(t('nav.search')) ?></a></div>
      </div></div>
      <div class="pkp_navigation_user_wrapper" id="navigationUserWrapper"><ul id="navigationUser" class="pkp_navigation_user pkp_nav_list">
        <?php if ($user): ?>
          <li class="profile"><a href="<?= e(url('submissions')) ?>"><?= e(t('nav.dashboard')) ?></a></li>
          <li class="profile"><a href="<?= e(url('login', 'signOut')) ?>"><?= e(t('nav.logout')) ?></a></li>
        <?php else: ?>
          <li class="profile"><a href="<?= e(url('user', 'register')) ?>"><?= e(t('nav.register')) ?></a></li>
          <li class="profile"><a href="<?= e(url('login')) ?>"><?= e(t('nav.login')) ?></a></li>
        <?php endif; ?>
      </ul></div>
    </nav>
  </div>
</header>
<div class="pkp_structure_content has_sidebar">
  <div class="pkp_structure_main" role="main"><a id="pkp_content_main"></a>
    <?php foreach (flash() ?? [] as [$ft, $fm]): ?><div class="flash flash_<?= e($ft) ?>"><?= e($fm) ?></div><?php endforeach; ?>
    <?= $content ?>
  </div>
  <div class="pkp_structure_sidebar left" role="complementary" aria-label="<?= e(t('sidebar.label')) ?>"><?= SidebarService::render($jid) ?></div>
</div>
<div class="pkp_structure_footer_wrapper" role="contentinfo"><a id="pkp_content_footer"></a>
  <div class="pkp_structure_footer">
    <div class="pkp_footer_content"><?= Settings::get('pageFooter', '') /* sanitised on save */ ?></div>
    <div class="pkp_brand_footer" role="complementary"><a href="<?= e(url('about', 'aboutThisPublishingSystem')) ?>"><span class="platform_badge"><?= e(t('footer.platform')) ?></span></a></div>
  </div>
</div>
</div>
<script src="<?= e(asset('js/public.js')) ?>"></script>
</body>
</html>
