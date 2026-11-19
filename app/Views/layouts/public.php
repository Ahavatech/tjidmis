<?php use App\Core\{Settings, Auth}; use App\Services\{NavService, SidebarService};
$jid = (int)Settings::journal()['id']; $name = Settings::get('name', 'Journal'); $user = Auth::user(); $logo = Settings::get('logo', '');
$nav = NavService::primary($jid); $pkp = $GLOBALS['pkp'] ?? ['page' => 'index', 'op' => 'index']; $isHome = $pkp['page'] === 'index';
$css = Settings::get('journalStyleSheet', ''); ?>
<!DOCTYPE html>
<html lang="en-US" xml:lang="en-US">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= e(($title ?? '') ? $title . ' | ' . $name : $name) ?></title>
	<?php if ($fav = Settings::get('favicon', '')): ?><link rel="icon" href="<?= e(public_url($fav)) ?>" /><?php endif; ?>

	<meta name="description" content="<?= e(Settings::get('searchDescription', '') ?: $name) ?>" />
	<meta name="generator" content="Open Journal Systems 3.3.0.22" />
	<?= $headExtra ?? '' ?>
	<?= Settings::get('customHeaders', '') ? Settings::get('customHeaders') . "\n" : '' ?>
	<link rel="alternate" type="application/atom+xml" href="<?= e(url('gateway', 'plugin', ['WebFeedGatewayPlugin', 'atom'])) ?>">
	<link rel="alternate" type="application/rdf+xml" href="<?= e(url('gateway', 'plugin', ['WebFeedGatewayPlugin', 'rss'])) ?>">
	<link rel="alternate" type="application/rss+xml" href="<?= e(url('gateway', 'plugin', ['WebFeedGatewayPlugin', 'rss2'])) ?>">
	<?= theme_css_links() ?>
	<style>:root{--theme-color:<?= e(Settings::get('themeColor', '#d79f24')) ?>}</style>
</head>
<body class="pkp_page_<?= e($pkp['page']) ?> pkp_op_<?= e($pkp['op']) ?><?= $logo ? ' has_site_logo' : '' ?>" dir="ltr">
<?= \App\Core\View::partial('partials/loginas_banner') ?>
	<div class="pkp_structure_page">
				<header class="pkp_structure_head" id="headerNavigationContainer" role="banner">
						<nav class="cmp_skip_to_content" aria-label="<?= e(t('nav.jump')) ?>">
	<a href="#pkp_content_main"><?= e(t('nav.skip_main')) ?></a>
	<a href="#siteNav"><?= e(t('nav.skip_nav')) ?></a>
	<?php if ($isHome): ?><a href="#homepageAbout"><?= e(t('nav.skip_about')) ?></a>
	<a href="#homepageIssue"><?= e(t('nav.skip_issue')) ?></a><?php endif; ?>
	<a href="#pkp_content_footer"><?= e(t('nav.skip_footer')) ?></a>
</nav>

			<div class="pkp_head_wrapper">

				<div class="pkp_site_name_wrapper">
					<button class="pkp_site_nav_toggle">
						<span><?= e(t('nav.open_menu')) ?></span>
					</button>
										<?php if ($isHome): ?><h1 class="pkp_screen_reader">
													<?= e($name) ?>
											</h1><?php endif; ?>

					<div class="pkp_site_name">
																<?php if ($logo): ?><a href="<?= e(url('index')) ?>" class="is_img">
								<img src="<?= e(public_url($logo)) ?>" width="<?= e(Settings::get('logoWidth', '')) ?>" height="<?= e(Settings::get('logoHeight', '')) ?>" />
							</a><?php else: ?><a href="<?= e(url('index')) ?>" class="is_text"><?= e($name) ?></a><?php endif; ?>
										</div>
				</div>

				
				<nav class="pkp_site_nav_menu" aria-label="<?= e(t('nav.site')) ?>">
					<a id="siteNav"></a>
					<div class="pkp_navigation_primary_row">
						<div class="pkp_navigation_primary_wrapper">
																				<ul id="navigationPrimary" class="pkp_navigation_primary pkp_nav_list">
								<?php foreach ($nav as $n): ?>
								<li class="">
				<a href="<?= e($n['href']) ?>">
					<?= e($n['title']) ?>
				</a>
							<?php if ($n['children']): ?><ul>
																	<?php foreach ($n['children'] as $c): ?><li class="">
				<a href="<?= e($c['href']) ?>">
					<?= e($c['title']) ?>
				</a>
							</li>
<?php endforeach; ?>							</ul><?php endif; ?>
			</li>
<?php endforeach; ?>			</ul>

				
															<?php if ($pkp['page'] !== 'search'): ?><div class="pkp_navigation_search_wrapper">
									<a href="<?= e(url('search')) ?>" class="pkp_search pkp_search_desktop">
				<span class="fa fa-search" aria-hidden="true"></span>
				<?= e(t('nav.search')) ?>
			</a>
							</div><?php endif; ?>
						</div>
					</div>
										<div class="pkp_navigation_user_wrapper" id="navigationUserWrapper">
							<ul id="navigationUser" class="pkp_navigation_user pkp_nav_list">
								<?php if ($user): ?>
								<li class="profile" aria-haspopup="true" aria-expanded="false">
				<a href="<?= e(url('submissions')) ?>">
					<?= e(trim($user['given_name'] . ' ' . $user['family_name'])) ?>
				</a>
									<ul>
																		<li class="">
				<a href="<?= e(url('submissions')) ?>">
					<?= e(t('nav.dashboard')) ?>
				</a>
							</li>
								<li class="">
				<a href="<?= e(url('user', 'profile')) ?>">
					<?= e(t('be.profile')) ?>
				</a>
							</li>
								<li class="">
				<a href="<?= e(url('login', 'signOut')) ?>">
					<?= e(t('nav.logout')) ?>
				</a>
							</li>
										</ul>
							</li>
								<?php else: ?>
								<li class="profile">
				<a href="<?= e(url('user', 'register')) ?>">
					<?= e(t('nav.register')) ?>
				</a>
							</li>
								<li class="profile">
				<a href="<?= e(url('login')) ?>">
					<?= e(t('nav.login')) ?>
				</a>
							</li>
								<?php endif; ?>
										</ul>

			</div>
				</nav>

																		</div><!-- .pkp_head_wrapper -->
		</header><!-- .pkp_structure_head -->

						<div class="pkp_structure_content has_sidebar">
			<div class="pkp_structure_main" role="main">
				<a id="pkp_content_main"></a>
<?php foreach (flash() ?? [] as [$ft, $fm]): ?><div class="cmp_notification <?= $ft === 'error' ? 'error' : 'success' ?>" role="status"><?= e($fm) ?></div><?php endforeach; ?>
<?= $content ?>
			</div><!-- pkp_structure_main -->

									<div class="pkp_structure_sidebar left" role="complementary" aria-label="<?= e(t('sidebar.label')) ?>">
				<?= SidebarService::render($jid) ?>
			</div><!-- pkp_sidebar.left -->
					</div><!-- pkp_structure_content -->

<div class="pkp_structure_footer_wrapper" role="contentinfo">
	<a id="pkp_content_footer"></a>

	<div class="pkp_structure_footer">

					<div class="pkp_footer_content">
				<?= rich(Settings::get('pageFooter', '')) ?>
			</div>
		
		<div class="pkp_brand_footer" role="complementary">
			<a href="<?= e(url('about', 'aboutThisPublishingSystem')) ?>">
				<img alt="<?= e(t('footer.platform_alt')) ?>" src="<?= e(asset('img/ojs_brand.png')) ?>">
			</a>
		</div>
	</div>
</div><!-- pkp_structure_footer_wrapper -->

</div><!-- pkp_structure_page -->

<script src="<?= e(asset('js/lib/jquery.min.js')) ?>" type="text/javascript"></script><script src="<?= e(asset('js/lib/popper.js')) ?>" type="text/javascript"></script><script src="<?= e(asset('js/lib/util.js')) ?>" type="text/javascript"></script><script src="<?= e(asset('js/lib/dropdown.js')) ?>" type="text/javascript"></script><script src="<?= e(asset('js/lib/main.js')) ?>" type="text/javascript"></script><script src="<?= e(asset('js/public.js')) ?>"></script>
</body>
</html>
