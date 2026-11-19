<?php use App\Core\Settings; $dl = url('article', 'download', [$a['sid'], $g['id'], $g['submission_file_id']]); $css = Settings::get('journalStyleSheet', ''); $pkp = $GLOBALS['pkp'] ?? ['page' => 'article', 'op' => 'view']; ?>
<!DOCTYPE html>
<html lang="en-US" xml:lang="en-US">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= e($a['title']) ?></title>
	<link rel="stylesheet" href="<?= e(asset('css/theme.css')) ?>" type="text/css" /><link rel="stylesheet" href="<?= e(asset('css/fontawesome.css')) ?>" type="text/css" /><link rel="stylesheet" href="<?= e(asset('css/google-fonts.css')) ?>" type="text/css" /><?php if ($css): ?><link rel="stylesheet" href="<?= e(public_url($css)) ?>" type="text/css" /><?php endif; ?>
</head>
<body class="pkp_page_article pkp_op_view">
	<header class="header_view">
		<a href="<?= e(url('article', 'view', [$a['sid']])) ?>" class="return">
			<span class="pkp_screen_reader"><?= e(t('galley.return')) ?></span>
		</a>
		<a href="<?= e(url('article', 'view', [$a['sid']])) ?>" class="title"><?= e($a['title']) ?></a>
		<a href="<?= e($dl) ?>" class="download" download>
			<span class="label"><?= e(t('galley.download')) ?></span>
			<span class="pkp_screen_reader"><?= e(t('galley.download_label', $g['label'])) ?></span>
		</a>
	</header>
	<div id="pdfCanvasContainer" class="galley_view">
		<iframe src="<?= e($dl . (strpos($dl, '?') === false ? '?' : '&') . 'inline=1') ?>" width="100%" height="100%" style="min-height: 500px;" title="<?= e(t('galley.title', $g['label'], $a['title'])) ?>" allow="fullscreen" webkitallowfullscreen="webkitallowfullscreen"></iframe>
	</div>
</body>
</html>
