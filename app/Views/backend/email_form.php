<?php $er = fn($k) => $errors[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('set.email_template')) ?>: <code><?= e($e['email_key']) ?></code></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('content', 'email', [$e['id']])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('wf.subject'), 'subject', $e['subject'], ['required' => true, 'maxlength' => 255, 'error' => $er('subject')]) ?><?= field(t('dec.body'), 'body', $e['body'], ['type' => 'textarea', 'rows' => 14, 'required' => true, 'error' => $er('body'), 'help' => t('set.email_vars')]) ?>
<?= checkbox('enabled', e(t('set.email_enabled')), !empty($e['enabled'])) ?></div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['workflow', 'emails'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
