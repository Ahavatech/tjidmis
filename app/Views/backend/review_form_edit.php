<?php use App\Services\ReviewFormService as RF; $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $f[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('rf.edit')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'reviewForm', !empty($f['id']) ? [$f['id']] : [])) ?>"><?= csrf_field() ?><h3 class="be_group"><?= e(t('rf.data')) ?></h3><div class="fields">
<?= field(t('rf.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= field(t('rf.description'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 6]) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['workflow', 'reviewForms'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div>
<?php if (!empty($f['id'])): ?><div class="be_card"><h3 class="be_group"><?= e(t('rf.items')) ?></h3>
<p><a class="be_btn be_btn_primary" href="<?= e(url('content', 'reviewFormItem', [$f['id']])) ?>"><?= e(t('rf.item_create')) ?></a></p>
<table class="be_table"><thead><tr><th><?= e(t('rf.item')) ?></th><th><?= e(t('rf.item_type')) ?></th><th><?= e(t('rf.required')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($items as $it): ?><tr><td><a href="<?= e(url('content', 'reviewFormItem', [$f['id'], $it['id']])) ?>"><?= strip_tags($it['question']) ?></a></td><td><?= e(t(RF::TYPES[$it['element_type']])) ?></td><td><?= $it['required'] ? e(t('rf.yes')) : e(t('rf.no')) ?></td>
<td><form method="post" class="be_inline"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'reviewFormItemDelete', [$f['id'], $it['id']])) ?>" onclick="return confirm('<?= e($inUse ? t('rf.confirm_item_delete') : t('rf.confirm_delete')) ?>')"><?= e(t('wizard.delete')) ?></button></form></td></tr>
<?php endforeach; ?><?php if (!$items): ?><tr><td colspan="4" class="muted"><?= e(t('rf.items_none')) ?></td></tr><?php endif; ?></tbody></table></div><?php endif; ?></div>
