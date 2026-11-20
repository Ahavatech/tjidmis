<?php use App\Services\SubscriptionService as SS; $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $ty[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t(!empty($ty['id']) ? 'sub.type_edit' : 'sub.type_create')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('subscription', 'type', !empty($ty['id']) ? [$ty['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('sub.type_name'), 'name', $v('name'), ['required' => true, 'maxlength' => 255, 'error' => $er('name')]) ?>
<?= field(t('sub.description'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 4]) ?>
<?= field(t('sub.cost'), 'cost', $v('cost'), ['required' => true, 'maxlength' => 12, 'error' => $er('cost')]) ?><?= field(t('sub.currency'), 'currency', $v('currency'), ['required' => true, 'maxlength' => 3, 'error' => $er('currency')]) ?>
<?= field(t('sub.duration_months'), 'duration', $v('duration'), ['maxlength' => 4, 'error' => $er('duration'), 'help' => t('sub.duration_help')]) ?>
<?= field(t('sub.format'), 'format', $v('format'), ['type' => 'select', 'required' => true, 'error' => $er('format'), 'options' => array_map(fn($k) => t($k), SS::FORMATS)]) ?>
<?= checkbox('institutional', e(t('sub.institutional_col')), !empty($ty['institutional'])) ?><?= checkbox('membership', e(t('sub.membership')), !empty($ty['membership'])) ?><?= checkbox('disable_public_display', e(t('sub.disable_public')), !empty($ty['disable_public_display'])) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('subscription', 'index', ['types'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
