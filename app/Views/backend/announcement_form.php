<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $a[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e($isEdit ? t('ann.edit') : t('ann.add')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('manageAnnouncements', 'form', !empty($a['id']) ? [$a['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('set.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?><?= field(t('ann.short'), 'description_short', $v('description_short'), ['type' => 'textarea', 'rows' => 3]) ?>
<?= field(t('ann.full'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 10, 'required' => true, 'error' => $er('description'), 'help' => t('set.html_help')]) ?>
<?= field(t('ann.expires'), 'date_expire', $v('date_expire'), ['type' => 'date', 'error' => $er('date_expire')]) ?><?php if (!$isEdit): ?><?= checkbox('send_email', e(t('ann.send_email')), false) ?><?php endif; ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('manageAnnouncements')) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
