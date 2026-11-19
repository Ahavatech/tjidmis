<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $pg[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('set.static_page')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'page', !empty($pg['id']) ? [$pg['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('set.path'), 'path', $v('path'), ['required' => true, 'maxlength' => 100, 'error' => $er('path'), 'help' => t('set.path_help')]) ?><?= field(t('set.title'), 'title', $v('title'), ['required' => true, 'maxlength' => 255, 'error' => $er('title')]) ?>
<?= field(t('set.content'), 'content', $v('content'), ['type' => 'textarea', 'rows' => 16, 'help' => t('set.html_help')]) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['website', 'staticPages'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
