<?php use App\Models\SubmissionRepo; $names = implode(', ', array_map(fn($a) => $a['family_name'] ?: $a['given_name'], $authors));
$cur = (int)$sub['stage_id']; $tabUrl = fn($t) => url('workflow', 'index', [$sub['id']], ['tab' => $t]); ?>
<div class="be_page"><div class="be_pagehead be_subhead"><div><div class="muted">#<?= (int)$sub['id'] ?> / <?= e($names) ?></div><h1><?= e($sub['title']) ?></h1></div>
<span class="be_badge_stage be_badge_<?= e($sub['status']) ?>"><?= e($sub['status'] === 'queued' ? t(SubmissionRepo::STAGES[$cur] ?? 'stage.submission') : t('status.' . $sub['status'])) ?></span></div>
<ul class="be_tabs"><li class="<?= $tab === 'workflow' ? 'active' : '' ?>"><a href="<?= e($tabUrl('workflow')) ?>"><?= e(t('wf.workflow')) ?></a></li><li class="<?= $tab === 'publication' ? 'active' : '' ?>"><a href="<?= e($tabUrl('publication')) ?>"><?= e(t('wf.publication')) ?></a></li></ul>
<?php if ($tab === 'workflow'): ?>
<div class="be_card be_wfgrid"><div class="be_wfmain">
  <ul class="be_stagebar"><?php foreach (SubmissionRepo::STAGES as $id => $k): ?><li class="<?= $id === $cur ? 'active' : ($id < $cur ? 'done' : 'future') ?>"><?= e(t($k)) ?></li><?php endforeach; ?></ul>
  <p class="be_substatus"><?= e(SubmissionRepo::statusText($sub + ['editors' => count(array_filter($participants, fn($p) => in_array($p['role_key'], ['editor', 'manager', 'section_editor'], true)))])) ?></p>
  <h2><?= e(t('wf.files')) ?></h2>
  <table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wizard.component')) ?></th><th><?= e(t('wf.uploaded')) ?></th></tr></thead><tbody>
  <?php foreach ($files as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sub['id'], $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['genre']) ?></td><td><?= e(fmt_date($f['created_at'])) ?></td></tr><?php endforeach; ?>
  <?php if (!$files): ?><tr><td colspan="3" class="muted"><?= e(t('wizard.no_files')) ?></td></tr><?php endif; ?></tbody></table>
  <h2><?= e(t('wf.discussions')) ?> <span class="be_count"><?= (int)$discussions ?></span></h2><p class="muted"><?= e(t('wf.discussions_note')) ?></p>
  <?php if ($staff): ?><div class="be_notice"><?= e(t('wf.staff_note')) ?></div>
  <h2><?= e(t('wf.log')) ?></h2><ul class="be_list"><?php foreach ($log as $l): ?><li><span><?= e($l['message'] ?: $l['event']) ?> <span class="muted">&mdash; <?= e($l['who'] ?: t('wf.system')) ?></span></span><span class="muted"><?= e($l['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<aside class="be_wfside"><h2><?= e(t('wf.participants')) ?></h2><ul class="be_list"><?php foreach ($participants as $p): ?><li><span><?= e(trim($p['given_name'] . ' ' . $p['family_name'])) ?></span><span class="muted"><?= e($p['role_name']) ?></span></li><?php endforeach; ?>
<?php if (!$participants): ?><li class="muted"><?= e(t('wf.no_participants')) ?></li><?php endif; ?></ul></aside></div>
<?php else: ?>
<div class="be_card"><dl class="be_summary"><dt><?= e(t('wizard.title_f')) ?></dt><dd><?= e(trim(($sub['prefix'] ? $sub['prefix'] . ' ' : '') . $sub['title'])) ?><?= $sub['subtitle'] ? ': ' . e($sub['subtitle']) : '' ?></dd>
<dt><?= e(t('wizard.section')) ?></dt><dd><?= e($sub['section_title']) ?></dd>
<dt><?= e(t('wizard.contributors')) ?></dt><dd><?php foreach ($authors as $a): ?><?= e(trim($a['given_name'] . ' ' . $a['family_name'])) ?> &lt;<?= e($a['email']) ?>&gt;<?= $a['affiliation'] ? ', ' . e($a['affiliation']) : '' ?><br><?php endforeach; ?></dd>
<dt><?= e(t('wizard.abstract')) ?></dt><dd><?= rich($sub['abstract']) ?></dd><dt><?= e(t('wizard.keywords')) ?></dt><dd><?= e(implode('; ', $keywords)) ?></dd></dl></div>
<?php endif; ?></div>
