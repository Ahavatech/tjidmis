<?php use App\Services\WorkflowService; $sid = (int)$sub['id']; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('dec.' . $decision)) ?> <span class="muted">#<?= $sid ?></span></h1></div><div class="be_card">
<p class="muted"><?= e(t('dec.help_' . $decision)) ?></p>
<form class="cmp_form be_form" method="post" action="<?= e(url('decision', 'save', [$sid, $decision])) ?>"><?= csrf_field() ?><div class="fields">
<?php if ($files): ?><fieldset class="be_fs"><legend><?= e(t($decision === 'send_to_production' ? 'dec.files_prod' : 'dec.files')) ?></legend><p class="muted"><?= e(t($decision === 'send_to_production' ? 'dec.files_prod_help' : 'dec.files_help')) ?></p>
<?php foreach ($files as $f): ?><div class="optin"><label><input type="checkbox" name="files[]" value="<?= (int)$f['id'] ?>" checked> <?= e($f['original_name']) ?> <span class="muted">(<?= e($f['genre']) ?>)</span></label></div><?php endforeach; ?></fieldset><?php endif; ?>
<?php if ($mail !== null): ?><fieldset class="be_fs"><legend><?= e(t('dec.email')) ?></legend>
<p><strong><?= e(t('wf.to')) ?>:</strong> <?= e(implode(', ', array_map(fn($r) => trim($r['given_name'] . ' ' . $r['family_name']) . ' <' . $r['email'] . '>', $recipients)) ?: t('dec.no_recipients')) ?></p>
<?= field(t('wf.subject'), 'subject', $mail[0], ['maxlength' => 255]) ?><?= field(t('dec.body'), 'body', $mail[1], ['type' => 'textarea', 'rows' => 12]) ?>
<?= checkbox('skip_email', e(t('dec.skip_email')), false) ?></fieldset><?php endif; ?>
</div><div class="buttons"><button class="submit<?= $decision === WorkflowService::DECLINE ? ' be_danger' : '' ?>" type="submit"><?= e(t('dec.record')) ?></button><a href="<?= e(url('workflow', 'index', [$sid])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
