<?php $dl = url('article', 'download', [$a['sid'], $g['id'], $g['submission_file_id']]); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($a['title']) ?></title><link rel="stylesheet" href="<?= e(asset('css/public.css')) ?>"></head>
<body class="pkp_page_article pkp_op_view galley_page">
<header class="header_view">
  <a href="<?= e(url('article', 'view', [$a['sid']])) ?>" class="return"><span class="pkp_screen_reader"><?= e(t('galley.return')) ?></span>&larr;</a>
  <a href="<?= e(url('article', 'view', [$a['sid']])) ?>" class="title"><?= e($a['title']) ?></a>
  <a href="<?= e($dl) ?>" class="download" download><span class="label"><?= e(t('galley.download')) ?></span><span class="pkp_screen_reader"> <?= e($g['label']) ?></span></a>
</header>
<div id="pdfCanvasContainer" class="galley_view"><iframe src="<?= e($dl . (strpos($dl, '?') === false ? '?' : '&') . 'inline=1') ?>" width="100%" height="100%" title="<?= e($a['title']) ?>" allow="fullscreen"></iframe></div>
</body></html>
