<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $c[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t(!empty($c['id']) ? 'cat.edit' : 'cat.add')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'category', !empty($c['id']) ? [$c['id']] : [])) ?>"><?= csrf_field() ?><h3 class="be_group"><?= e(t('cat.details')) ?></h3><div class="fields">
<?= field(t('cat.parent'), 'parent_id', $v('parent_id'), ['type' => 'select', 'options' => array_column($parents, 'title', 'id')]) ?>
<?= field(t('cat.name'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= field(t('cat.path'), 'path', $v('path'), ['required' => true, 'maxlength' => 100, 'error' => $er('path'), 'help' => t('cat.url_will_be', abs_url(url('catalog', 'category', ['{path}'])))]) ?>
<?= field(t('cat.description'), 'description', $v('description'), ['type' => 'textarea', 'rows' => 6]) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['journal', 'categories'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
