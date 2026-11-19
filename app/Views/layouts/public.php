<?php use App\Core\{Settings, Auth};
$name = Settings::get('name', 'Journal'); $user = Auth::user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') ? $title . ' | ' . $name : $name) ?></title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;700&family=Noto+Serif:ital,wght@0,400;0,700;1,400;1,700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>">
<style>:root{--theme-color:<?= e(Settings::get('themeColor', '#d79f24')) ?>}</style>
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
      <h1 class="pkp_screen_reader"><?= e($name) ?></h1>
      <div class="pkp_site_name"><a href="<?= e(url()) ?>" class="is_text"><?= e($name) ?></a></div>
    </div>
    <nav class="pkp_site_nav_menu" aria-label="<?= e(t('nav.site')) ?>"><a id="siteNav"></a>
      <div class="pkp_navigation_primary_row"><div class="pkp_navigation_primary_wrapper">
        <ul id="navigationPrimary" class="pkp_navigation_primary pkp_nav_list">
          <li><a href="<?= e(url('about')) ?>"><?= e(t('nav.about')) ?></a></li>
          <li><a href="<?= e(url('issue', 'current')) ?>"><?= e(t('nav.current')) ?></a></li>
          <li><a href="<?= e(url('issue', 'archive')) ?>"><?= e(t('nav.archives')) ?></a></li>
          <li><a href="<?= e(url('about', 'submissions')) ?>"><?= e(t('nav.submissions')) ?></a></li>
          <li><a href="<?= e(url('announcement')) ?>"><?= e(t('nav.announcements')) ?></a></li>
        </ul>
        <div class="pkp_navigation_search_wrapper"><a href="<?= e(url('search')) ?>" class="pkp_search"><?= e(t('nav.search')) ?></a></div>
      </div></div>
      <div class="pkp_navigation_user_wrapper" id="navigationUserWrapper"><ul id="navigationUser" class="pkp_navigation_user pkp_nav_list">
        <?php if ($user): ?>
          <li class="profile"><a href="<?= e(url('submissions')) ?>"><?= e($user['given_name']) ?></a></li>
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
  <div class="pkp_structure_sidebar left" role="complementary"><!-- Phase 2: sidebar blocks from settings --></div>
</div>
<div class="pkp_structure_footer_wrapper" role="contentinfo"><a id="pkp_content_footer"></a>
  <div class="pkp_structure_footer"><div class="pkp_footer_content"><?= Settings::get('pageFooter', '') /* sanitised on save */ ?></div></div>
</div>
</div>
</body>
</html>
