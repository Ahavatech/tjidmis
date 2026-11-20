<?php use App\Services\ReviewFormService as RF; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('rf.preview')) ?>: <?= e($f['title']) ?></h1><a class="be_btn" href="<?= e(url('content', 'reviewForm', [$f['id']])) ?>"><?= e(t('wf.back')) ?></a></div>
<div class="be_card"><?php if ($f['description']): ?><div class="rf_desc"><?= $f['description'] ?></div><?php endif; ?>
<form class="cmp_form be_form" onsubmit="return false"><div class="fields">
<?php foreach ($items as $it): ?><div class="rf_item"><span class="label"><?= strip_tags($it['question']) ?><?= $it['required'] ? ' <span class="req">*</span>' : '' ?></span><?php if ($it['description']): ?><p class="muted"><?= strip_tags($it['description']) ?></p><?php endif; ?><?= RF::input($it) ?></div>
<?php endforeach; ?><?php if (!$items): ?><p class="muted"><?= e(t('rf.items_none')) ?></p><?php endif; ?></div></form></div></div>
