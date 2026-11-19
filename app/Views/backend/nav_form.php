<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $it[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('set.nav_item')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'navItem', !empty($it['id']) ? [$it['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('set.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= field(t('set.nav_type'), 'type', $v('type') ?: 'custom_url', ['type' => 'select', 'options' => array_combine($types, array_map(fn($x) => t('set.navtype_' . $x), $types)), 'required' => true, 'error' => $er('type')]) ?>
<?= field(t('set.nav_page'), 'static_page_id', $v('static_page_id'), ['type' => 'select', 'options' => array_column($pages, 'title', 'id'), 'error' => $er('static_page_id'), 'help' => t('set.nav_page_help')]) ?>
<?= field(t('set.nav_url'), 'url', $v('url'), ['maxlength' => 255, 'error' => $er('url'), 'help' => t('set.nav_url_help')]) ?>
<?= field(t('set.nav_parent'), 'parent_id', $v('parent_id'), ['type' => 'select', 'options' => array_column($tops, 'title', 'id'), 'error' => $er('parent_id'), 'help' => t('set.nav_parent_help')]) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['website', 'navigation'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
