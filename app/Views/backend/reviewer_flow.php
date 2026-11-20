<?php use App\Services\ReviewService; $aid = (int)$ra['id']; $steps = [1 => 'rflow.step1', 2 => 'rflow.step2', 3 => 'rflow.step3', 4 => 'rflow.step4'];
$su = fn($i) => url('reviewer', 'submission', [$aid, $i]); $blind = $ra['review_method'] === 'double_anonymous'; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('rflow.review')) ?>: <?= e($ra['title']) ?></h1></div>
<ol class="be_steps"><?php foreach ($steps as $i => $k): ?><li class="<?= $i === $n ? 'active' : '' ?><?= $i < $n ? ' done' : '' ?><?= $i > $max ? ' locked' : '' ?>"><?php if ($i <= $max): ?><a href="<?= e($su($i)) ?>"><span class="be_stepno"><?= $i ?></span> <?= e(t($k)) ?></a><?php else: ?><span><span class="be_stepno"><?= $i ?></span> <?= e(t($k)) ?></span><?php endif; ?></li><?php endforeach; ?></ol>
<div class="be_card be_wizard">
<?php if ($n === 1): ?>
<h2><?= e(t('rflow.request')) ?></h2>
<dl class="be_summary"><dt><?= e(t('wizard.title_f')) ?></dt><dd><?= e($ra['title']) ?></dd>
<?php if (!$blind && $authors): ?><dt><?= e(t('wizard.contributors')) ?></dt><dd><?= e(implode(', ', array_map(fn($a) => trim($a['given_name'] . ' ' . $a['family_name']), $authors))) ?></dd><?php endif; ?>
<dt><?= e(t('wizard.abstract')) ?></dt><dd><?= rich($ra['abstract']) ?></dd><dt><?= e(t('rev.method')) ?></dt><dd><?= e(t(ReviewService::METHODS[$ra['review_method']])) ?></dd>
<dt><?= e(t('rev.dates')) ?></dt><dd><?= e(t('rev.response_due')) ?>: <?= e(fmt_date($ra['date_response_due'])) ?> &middot; <?= e(t('rev.review_due')) ?>: <?= e(fmt_date($ra['date_due'])) ?></dd></dl>
<?php if ($ra['status'] === 'declined'): ?><div class="be_notice"><?= e(t('rflow.you_declined')) ?></div>
<?php elseif ($ra['status'] === 'awaiting_response'): ?><p class="muted"><?= e(t('rflow.files_hidden')) ?></p>
<form class="cmp_form be_form" method="post" action="<?= e(url('reviewer', 'accept', [$aid])) ?>"><?= csrf_field() ?><div class="fields">
<fieldset class="be_fs"><legend><?= e(t('rflow.competing')) ?></legend><?php if ($competing): ?><div class="be_notice"><?= rich($competing) ?></div><?php endif; ?>
<div class="optin"><label><input type="radio" name="competing" value="no" checked> <?= e(t('rflow.no_competing')) ?></label></div><div class="optin"><label><input type="radio" name="competing" value="yes"> <?= e(t('rflow.yes_competing')) ?></label></div>
<?= field(t('rflow.competing_text'), 'competing_text', '', ['type' => 'textarea', 'rows' => 3]) ?></fieldset>
<?= checkbox('consent', e(t('rflow.consent')), false) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('rflow.accept')) ?></button></div></form>
<form class="cmp_form be_form" method="post" action="<?= e(url('reviewer', 'decline', [$aid])) ?>" onsubmit="return confirm('<?= e(t('rflow.confirm_decline')) ?>')"><?= csrf_field() ?><div class="fields"><?= field(t('rflow.decline_reason'), 'reason', '', ['type' => 'textarea', 'rows' => 2]) ?></div><div class="buttons"><button class="be_btn be_btn_danger" type="submit"><?= e(t('rflow.decline')) ?></button></div></form><?php endif; ?>

<?php elseif ($n === 2): ?>
<h2><?= e(t('rflow.guidelines')) ?></h2><div class="be_notice"><?= rich($guidelines) ?></div>
<div class="buttons"><a class="submit" href="<?= e($su(3)) ?>"><?= e(t('rflow.continue3')) ?></a></div>

<?php elseif ($n === 3): ?>
<h2><?= e(t('rflow.download_review')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wizard.component')) ?></th></tr></thead><tbody><?php foreach ($files as $f): ?><tr><td><a href="<?= e(url('reviewer', 'download', [$aid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['genre']) ?></td></tr><?php endforeach; ?><?php if (!$files): ?><tr><td colspan="2" class="muted"><?= e(t('wizard.no_files')) ?></td></tr><?php endif; ?></tbody></table>
<form class="cmp_form be_form" method="post" action="<?= e(url('reviewer', 'saveReview', [$aid])) ?>"><?= csrf_field() ?><div class="fields">
<?php if ($rfEls): ?><?php if ($rfForm['description']): ?><div class="be_notice"><?= rich($rfForm['description']) ?></div><?php endif; ?>
<?php foreach ($rfEls as $el): ?><div class="rf_item"><span class="label"><?= strip_tags($el['question']) ?><?= $el['required'] ? ' <span class="required" aria-hidden="true">*</span>' : '' ?></span><?php if ($el['description']): ?><p class="muted"><?= strip_tags($el['description']) ?></p><?php endif; ?><?= \App\Services\ReviewFormService::input($el, $rfAns[(int)$el['id']] ?? '') ?></div>
<?php endforeach; ?><?php else: ?>
<?= field(t('rflow.comments_author'), 'comments_author', strip_tags(str_replace(['</p>', '<br>'], "\n\n", (string)$ra['comments_author'])), ['type' => 'textarea', 'rows' => 8, 'help' => t('rflow.comments_author_help')]) ?>
<?= field(t('rflow.comments_editor'), 'comments_editor', strip_tags(str_replace(['</p>', '<br>'], "\n\n", (string)$ra['comments_editor'])), ['type' => 'textarea', 'rows' => 5]) ?>
<?php endif; ?>
<?= field(t('wf.recommendation'), 'recommendation', $ra['recommendation'], ['type' => 'select', 'options' => array_map(fn($k) => t($k), ReviewService::RECOMMENDATIONS)]) ?></div>
<div class="buttons"><a class="be_btn" href="<?= e($su(2)) ?>"><?= e(t('wizard.back')) ?></a><button class="be_btn" type="submit" name="action" value="save"><?= e(t('rflow.save_later')) ?></button><button class="submit" type="submit" name="action" value="submit" onclick="return confirm('<?= e(t('rflow.confirm_submit')) ?>')"><?= e(t('rflow.submit')) ?></button></div></form>
<?php if (\App\Core\Settings::get('reviewerUploads', '1') === '1'): ?><h2><?= e(t('rflow.attachments')) ?></h2>
<table class="be_table"><tbody><?php foreach ($attachments as $f): ?><tr><td><a href="<?= e(url('reviewer', 'download', [$aid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td class="be_actions"><form class="be_inline" method="post" action="<?= e(url('reviewer', 'deleteAttachment', [$aid, $f['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form></td></tr><?php endforeach; ?></tbody></table>
<form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('reviewer', 'uploadAttachment', [$aid])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wizard.upload')) ?></button></form><?php endif; ?>

<?php else: ?>
<h2><?= e(t('rflow.complete')) ?></h2><p><?= e(t('rflow.complete_text')) ?></p>
<dl class="be_summary"><dt><?= e(t('wf.recommendation')) ?></dt><dd><?= $ra['recommendation'] ? e(t(ReviewService::RECOMMENDATIONS[$ra['recommendation']])) : '-' ?></dd><dt><?= e(t('rflow.comments_author')) ?></dt><dd><?= $ra['comments_author'] ?: '-' ?></dd><dt><?= e(t('rflow.comments_editor')) ?></dt><dd><?= $ra['comments_editor'] ?: '-' ?></dd></dl>
<p><a href="<?= e(url('submissions')) ?>"><?= e(t('wizard.return_dashboard')) ?></a></p>
<?php endif; ?></div></div>
