<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $b[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('set.custom_block')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'block', !empty($b['id']) ? [$b['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('set.block_name'), 'name', $v('name'), ['required' => true, 'maxlength' => 100, 'error' => $er('name'), 'help' => t('set.path_help')]) ?><?= field(t('set.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= checkbox('show_title', e(t('set.show_title')), !empty($b['show_title'])) ?><?= field(t('set.content'), 'content', $v('content'), ['type' => 'textarea', 'rows' => 14, 'help' => t('set.html_help')]) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['website', 'blocks'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
