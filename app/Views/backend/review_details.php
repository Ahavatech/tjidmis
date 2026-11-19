<?php use App\Services\ReviewService; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('rev.details')) ?>: <?= e(trim($ra['given_name'] . ' ' . $ra['family_name'])) ?></h1><a class="be_btn" href="<?= e(url('workflow', 'index', [$sub['id'], 3])) ?>"><?= e(t('wf.back')) ?></a></div>
<div class="be_card"><dl class="be_summary"><dt><?= e(t('wf.status')) ?></dt><dd><?= e(ReviewService::statusLabel($ra)) ?></dd><dt><?= e(t('rev.method')) ?></dt><dd><?= e(t(ReviewService::METHODS[$ra['review_method']])) ?></dd>
<dt><?= e(t('wf.recommendation')) ?></dt><dd><?= $ra['recommendation'] ? e(t(ReviewService::RECOMMENDATIONS[$ra['recommendation']])) : '-' ?></dd>
<dt><?= e(t('rflow.comments_author')) ?></dt><dd><?= $ra['comments_author'] ?: '<span class="muted">-</span>' ?></dd><dt><?= e(t('rflow.comments_editor')) ?></dt><dd><?= $ra['comments_editor'] ?: '<span class="muted">-</span>' ?></dd>
<dt><?= e(t('rflow.competing')) ?></dt><dd><?= e($ra['competing_interests'] ?: t('rflow.none_declared')) ?></dd>
<dt><?= e(t('rflow.attachments')) ?></dt><dd><?php foreach ($files as $f): ?><a href="<?= e(url('workflow', 'download', [$sub['id'], $f['id']])) ?>"><?= e($f['original_name']) ?></a><br><?php endforeach; ?><?= $files ? '' : '<span class="muted">-</span>' ?></dd>
<dt><?= e(t('rev.dates')) ?></dt><dd><?= e(t('rev.assigned')) ?>: <?= e(fmt_date($ra['date_assigned'])) ?> &middot; <?= e(t('rev.completed')) ?>: <?= e(fmt_date($ra['date_completed']) ?: '-') ?></dd></dl></div></div>
