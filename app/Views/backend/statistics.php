<?php $max = max(1, ...array_map(fn($r) => max((int)$r['views'], (int)$r['downloads']), $series ?: [['views' => 0, 'downloads' => 0]])); ?>
<?php $op = 'articles'; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('stat.menu_articles')) ?></h1></div>
<form class="be_filters" method="get" action="<?= e(url('statistics', $op)) ?>"><label><?= e(t('stat.from')) ?> <input type="date" name="from" value="<?= e($from) ?>"></label><label><?= e(t('stat.to')) ?> <input type="date" name="to" value="<?= e($to) ?>"></label><button class="be_btn" type="submit"><?= e(t('dash.filter')) ?></button></form>
<div class="be_cards">
<div class="be_stat"><span><?= (int)$m['abstract_view'] ?></span><?= e(t('stat.views')) ?></div><div class="be_stat"><span><?= (int)$m['galley_download'] ?></span><?= e(t('stat.downloads')) ?></div></div>
<div class="be_card"><h2><?= e(t('stat.by_month')) ?></h2><?php if (!$series): ?><p class="muted"><?= e(t('stat.no_data')) ?></p><?php else: ?><div class="be_chart" role="img" aria-label="<?= e(t('stat.by_month')) ?>"><?php foreach ($series as $r): ?><div class="be_bargroup"><div class="be_bars"><i class="v" style="height:<?= round(100 * $r['views'] / $max) ?>%" title="<?= e(t('stat.views')) ?>: <?= (int)$r['views'] ?>"></i><i class="d" style="height:<?= round(100 * $r['downloads'] / $max) ?>%" title="<?= e(t('stat.downloads')) ?>: <?= (int)$r['downloads'] ?>"></i></div><span><?= e($r['ym']) ?></span></div><?php endforeach; ?></div>
<p class="muted be_small"><i class="be_key v"></i> <?= e(t('stat.views')) ?> &nbsp; <i class="be_key d"></i> <?= e(t('stat.downloads')) ?></p><?php endif; ?></div>
<div class="be_card" style="margin-top:1rem"><?php $gr = []; foreach ($top as $r) $gr[] = ['cells' => ['<a href="' . e(url('article', 'view', [$r['id']])) . '">' . e($r['title']) . '</a>', (int)$r['views'], (int)$r['downloads']]]; ?>
<?= grid(t('stat.top'), [], [[t('wizard.title_f'), 60], [t('stat.views'), 20], [t('stat.downloads'), 20]], $gr, t('stat.no_data')) ?></div></div>

