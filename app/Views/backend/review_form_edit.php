<?php use App\Services\ReviewFormService as RF; $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $f[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('rf.edit')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'reviewForm', !empty($f['id']) ? [$f['id']] : [])) ?>"><?= csrf_field() ?><h3 class="be_group"><?= e(t('rf.data')) ?></h3><div class="fields">
<?= field(t('rf.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= field(t('rf.description'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 6]) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['workflow', 'reviewForms'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div>
<?php if (!empty($f['id'])): ?><div class="be_card"><?php $rows = []; foreach ($items as $it) $rows[] = ['cells' => [strip_tags($it['question']), e(t(RF::TYPES[$it['element_type']])), $it['required'] ? e(t('rf.yes')) : e(t('rf.no'))], 'actions' => [
  [t('wizard.edit'), url('content', 'reviewFormItem', [$f['id'], $it['id']])], [t('wizard.delete'), url('content', 'reviewFormItemDelete', [$f['id'], $it['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => $inUse ? t('rf.confirm_item_delete') : t('rf.confirm_delete')]]]]; ?>
<?= grid(t('rf.items'), [[t('rf.item_create'), url('content', 'reviewFormItem', [$f['id']])]], [[t('rf.item'), 50], [t('rf.item_type'), 30], [t('rf.required'), 20]], $rows, t('rf.items_none')) ?></div><?php endif; ?></div>
