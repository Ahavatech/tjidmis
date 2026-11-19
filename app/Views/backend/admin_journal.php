<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $j[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('adm.journal')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('admin', 'journal', !empty($j['id']) ? [$j['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('adm.journal_name'), 'name', $v('name'), ['required' => true, 'maxlength' => 255, 'error' => $er('name')]) ?><?= field(t('adm.path'), 'path', $v('path'), ['required' => true, 'maxlength' => 32, 'error' => $er('path'), 'help' => t('adm.path_help')]) ?>
<?= checkbox('enabled', e(t('adm.enabled')), !empty($j['enabled'])) ?></div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('admin', 'index', ['journals'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
