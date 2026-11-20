<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_reports')) ?></h1></div>
<p class="muted"><?= e(t('stat.reports_help')) ?></p>
<div class="be_card"><?php $gr = []; foreach (['articles', 'reviews', 'users'] as $r) $gr[] = ['cells' => ['<strong>' . e(t('stat.report_' . $r)) . '</strong><br><span class="muted be_small">' . e(t('stat.report_' . $r . '_help')) . '</span>'], 'actions' => [[t('stat.download_csv'), url('statistics', 'report', [$r]), ['class' => 'preview']]]]; ?>
<?= grid(t('stat.menu_reports'), [], [[t('stat.menu_reports'), 100]], $gr) ?></div></div>
