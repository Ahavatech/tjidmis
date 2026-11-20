<?php use App\Models\SubmissionRepo; use App\Services\{ReviewService, WorkflowService};
$names = implode(', ', array_map(fn($a) => $a['family_name'] ?: $a['given_name'], $authors)); $sid = (int)$sub['id'];
$tabUrl = fn($t) => url('workflow', 'index', [$sid, $stage], ['tab' => $t]);
$stageUrl = fn($i, $q = []) => url('workflow', 'index', [$sid, $i], $q);
$badge = $sub['status'] === 'queued' ? t(SubmissionRepo::STAGES[$cur] ?? 'stage.submission') : t('status.' . $sub['status']);
$isReviewStage = $stage === 3; $mgr = $manage; ?>
<div class="be_page">
<div class="be_pagehead be_subhead"><div><div class="muted">#<?= $sid ?> / <?= e($names) ?></div><h1><?= e($sub['title']) ?></h1></div>
<div class="be_headright"><span class="be_badge_stage be_badge_<?= e($sub['status']) ?>"><?= e($badge) ?></span><?php if ($mgr): ?><a class="be_btn" href="<?= e(url('workflow', 'log', [$sid])) ?>"><?= e(t('wf.log')) ?></a><?php endif; ?></div></div>
<ul class="be_tabs"><li class="<?= $tab === 'workflow' ? 'active' : '' ?>"><a href="<?= e($tabUrl('workflow')) ?>"><?= e(t('wf.workflow')) ?></a></li><li class="<?= $tab === 'publication' ? 'active' : '' ?>"><a href="<?= e($tabUrl('publication')) ?>"><?= e(t('wf.publication')) ?></a></li></ul>
<?php if ($tab === 'publication'): ?>
<div class="be_card"><dl class="be_summary"><dt><?= e(t('wizard.title_f')) ?></dt><dd><?= e(trim(($sub['prefix'] ? $sub['prefix'] . ' ' : '') . $sub['title'])) ?><?= $sub['subtitle'] ? ': ' . e($sub['subtitle']) : '' ?></dd>
<dt><?= e(t('wizard.section')) ?></dt><dd><?= e($sub['section_title']) ?></dd>
<dt><?= e(t('wizard.contributors')) ?></dt><dd><?php foreach ($authors as $a): ?><?= e(trim($a['given_name'] . ' ' . $a['family_name'])) ?> &lt;<?= e($a['email']) ?>&gt;<?= $a['affiliation'] ? ', ' . e($a['affiliation']) : '' ?><br><?php endforeach; ?></dd>
<dt><?= e(t('wizard.abstract')) ?></dt><dd><?= rich($sub['abstract']) ?></dd><dt><?= e(t('wizard.keywords')) ?></dt><dd><?= e(implode('; ', $keywords)) ?></dd></dl></div>
<?php else: ?>
<ul class="be_stagetabs"><?php foreach (SubmissionRepo::STAGES as $i => $k): $future = $i > $cur && $sub['status'] !== 'published'; ?>
<li class="<?= $i === $stage ? 'active' : '' ?><?= $future ? ' future' : '' ?>"><?php if ($future): ?><span><?= e(t($k)) ?></span><?php else: ?><a href="<?= e($stageUrl($i)) ?>"><?= e(t($k)) ?><?= !empty($discTotal[$i]) ? ' <span class="be_count">' . (int)$discTotal[$i] . '</span>' : '' ?></a><?php endif; ?></li><?php endforeach; ?></ul>
<div class="be_card be_wfgrid"><div class="be_wfmain">
<p class="be_substatus"><?= e(SubmissionRepo::statusText($sub + ['editors' => count(array_filter($participants, fn($p) => in_array($p['role_key'], ['editor', 'manager', 'section_editor'], true)))])) ?></p>

<?php if ($stage === 1): ?>
<?php $gr = []; foreach ($files as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e($f['genre']), e(fmt_date($f['created_at']))]]; ?>
<?= grid(t('wf.files'), [], [[t('wizard.file'), 50], [t('wizard.component'), 25], [t('wf.uploaded'), 25]], $gr, t('wizard.no_files')) ?>

<?php elseif ($isReviewStage): ?>
<?php if (!$round): ?><p class="muted"><?= e(t('wf.no_round')) ?></p><?php else: ?>
<ul class="be_roundtabs"><?php foreach ($rounds as $r): ?><li class="<?= (int)$r['id'] === (int)$round['id'] ? 'active' : '' ?>"><a href="<?= e($stageUrl(3, ['round' => $r['round']])) ?>"><?= e(t('wf.round', (int)$r['round'])) ?></a></li><?php endforeach; ?></ul>
<?php $gr = []; foreach ($reviewFiles as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e($f['genre'])], 'actions' => ($mgr && $f['file_stage'] === 'review_file') ? [[t('wizard.delete'), url('workflow', 'deleteFile', [$sid, $f['id']]), ['post' => 1, 'class' => 'delete']]] : []]; ?>
<?= grid(t('wf.review_files'), [], [[t('wizard.file'), 60], [t('wizard.component'), 40]], $gr, t('wizard.no_files')) ?>
<?php if ($mgr && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id']): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadFile', [$sid, $round['id']])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.add_review_file')) ?></button></form><?php endif; ?>

<?php if ($mgr): ?>
<?php $open = $sub['status'] === 'queued' && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id']; $gr = [];
foreach ($reviewers as $ra) { $live = in_array($ra['status'], ['awaiting_response', 'accepted'], true); $act = [];
  if (in_array($ra['status'], ['review_submitted', 'thanked'], true)) $act[] = [t('rev.details'), url('review', 'details', [$sid, $ra['id']]), ['class' => 'preview']];
  if ($open && $live) { $act[] = [t('rev.edit_deadlines'), url('review', 'edit', [$sid, $ra['id']])]; $act[] = [t('rev.remind'), url('review', 'remind', [$sid, $ra['id']]), ['post' => 1, 'class' => 'copy']]; $act[] = [t('rev.unassign'), url('review', 'unassign', [$sid, $ra['id']]), ['post' => 1, 'class' => 'delete']]; }
  if ($open && $ra['status'] === 'review_submitted') $act[] = [t('rev.thank'), url('review', 'thank', [$sid, $ra['id']]), ['post' => 1, 'class' => 'copy']];
  if ($open && in_array($ra['status'], ['cancelled', 'declined'], true)) $act[] = [t('rev.reinstate'), url('review', 'reinstate', [$sid, $ra['id']]), ['post' => 1, 'class' => 'copy']];
  $gr[] = ['class' => trim((ReviewService::isOverdue($ra) ? 'be_overdue ' : '') . (in_array($ra['status'], ['cancelled', 'declined'], true) ? 'be_muted_row' : '')), 'cells' => [e(trim($ra['given_name'] . ' ' . $ra['family_name'])) . '<div class="muted be_small">' . e(t(ReviewService::METHODS[$ra['review_method']])) . '</div>',
    '<span class="be_chip be_chip_' . e($ra['status']) . '">' . e(ReviewService::statusLabel($ra)) . '</span>', e(fmt_date($ra['date_response_due'])), e(fmt_date($ra['date_due'])), $ra['recommendation'] ? e(t(ReviewService::RECOMMENDATIONS[$ra['recommendation']])) : '-'], 'actions' => $act]; } ?>
<?= grid(t('wf.reviewers'), $open ? [[t('rev.add'), url('review', 'search', [$sid])]] : [], [[t('wf.reviewer'), 28], [t('wf.status'), 18], [t('wf.response_due'), 18], [t('wf.review_due'), 18], [t('wf.recommendation'), 18]], $gr, t('wf.no_reviewers')) ?><?php endif; ?>

<?php $gr = []; foreach ($revisions as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e($f['uploader']), e(fmt_date($f['created_at']))], 'actions' => ($mgr || ((int)$f['uploader_user_id'] === $me && $canRevise)) ? [[t('wizard.delete'), url('workflow', 'deleteFile', [$sid, $f['id']]), ['post' => 1, 'class' => 'delete']]] : []]; ?>
<?= grid(t('wf.revisions'), [], [[t('wizard.file'), 50], [t('wf.by'), 25], [t('wf.uploaded'), 25]], $gr, t('wf.no_revisions')) ?>
<?php if ($canRevise): ?><div class="be_notice"><?= e(t('wf.revisions_requested')) ?></div><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadFile', [$sid, $round['id']])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn be_btn_primary" type="submit"><?= e(t('wf.upload_revision')) ?></button></form><?php endif; ?>

<?php if ($roundDecisions): ?><h2><?= e(t('wf.decisions')) ?></h2><ul class="be_list"><?php foreach ($roundDecisions as $d): ?><li><span><?= e(t('dec.' . $d['decision'])) ?> <span class="muted">&mdash; <?= e($mgr ? $d['who'] : t('wf.editor')) ?></span></span><span class="muted"><?= e(fmt_date($d['date_decided'])) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
<?php endif; ?>

<?php elseif ($stage === 4): ?>
<?php $gr = []; foreach ($draftFiles as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e($f['genre']), e(fmt_date($f['created_at']))], 'actions' => $canWork ? [[t('wizard.delete'), url('workflow', 'deleteFile', [$sid, $f['id']]), ['post' => 1, 'class' => 'delete']]] : []]; ?>
<?= grid(t('wf.copyedit_files'), [], [[t('wizard.file'), 50], [t('wizard.component'), 25], [t('wf.uploaded'), 25]], $gr, t('wf.no_copyedit_files')) ?>
<?php if ($canWork && $sub['status'] === 'queued'): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadStageFile', [$sid, 4])) ?>"><?= csrf_field() ?><select name="genre"><option value="Draft"><?= e(t('wf.genre_draft')) ?></option><option value="Copyedited"><?= e(t('wf.genre_copyedited')) ?></option></select><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.upload_file')) ?></button></form><?php endif; ?>
<?php if ($mgr && $candidates && $sub['status'] === 'queued'): ?><?php $gr = []; foreach ($candidates as $f) $gr[] = ['cells' => [e($f['original_name']) . ' <span class="muted">(' . e($f['genre']) . ')</span>'], 'actions' => [[t('wf.add_to_copyediting'), url('workflow', 'promote', [$sid, $f['id'], 'copyedit']), ['post' => 1, 'class' => 'copy']]]]; ?>
<?= grid(t('wf.select_files'), [], [[t('wizard.file'), 100]], $gr) ?><?php endif; ?>

<?php elseif ($stage === 5): ?>
<?php $gr = []; foreach ($prodFiles as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e(fmt_date($f['created_at']))], 'actions' => ($canWork && $sub['status'] !== 'published') ? [[t('wizard.delete'), url('workflow', 'deleteFile', [$sid, $f['id']]), ['post' => 1, 'class' => 'delete']]] : []]; ?>
<?= grid(t('wf.prod_files'), [], [[t('wizard.file'), 70], [t('wf.uploaded'), 30]], $gr, t('wf.no_prod_files')) ?>
<?php if ($canWork && !in_array($sub['status'], ['published', 'declined'], true)): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadStageFile', [$sid, 5])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.upload_file')) ?></button></form><?php endif; ?>
<h2><?= e(t('wf.galleys')) ?> <?php if ($mgr): ?><a class="be_btn be_small" href="<?= e(url('publication', 'index', [$sid, 'galleys'])) ?>"><?= e(t('wf.manage_galleys')) ?></a><?php endif; ?></h2>
<ul class="be_list"><?php foreach ($galleys as $g): ?><li><span><?= e($g['label']) ?> <span class="muted"><?= e($g['original_name'] ?: $g['remote_url']) ?></span></span></li><?php endforeach; ?><?php if (!$galleys): ?><li class="muted"><?= e(t('pub.no_galleys')) ?></li><?php endif; ?></ul>
<?php if ($mgr): ?><p><a class="be_btn be_btn_primary" href="<?= e(url('publication', 'index', [$sid, 'issue'])) ?>"><?= e(in_array($sub['status'], ['scheduled', 'published'], true) ? t('wf.open_publication') : t('pub.schedule')) ?></a></p><?php endif; ?>
<?php endif; ?>

<h2><?= e(t('wf.discussions_' . (int)$stage)) ?> <?php if ($sub['status'] === 'queued' || $mgr): ?><a class="be_btn be_small" href="<?= e(url('discussion', 'create', [$sid, $stage])) ?>"><?= e(t('disc.add')) ?></a><?php endif; ?></h2>
<?php if ($discussions): ?><ul class="be_list"><?php foreach ($discussions as $q): ?><li class="<?= !empty($q['unread']) ? 'unread' : '' ?>"><span><a href="<?= e(url('discussion', 'view', [$q['id']])) ?>"><?= e($q['subject']) ?></a><?= $q['closed'] ? ' <span class="be_chip">' . e(t('disc.closed')) . '</span>' : '' ?></span><span class="muted"><?= e(t('disc.messages', (int)$q['notes'])) ?><?= !empty($q['unread']) ? ' &middot; ' . e(t('disc.unread', (int)$q['unread'])) : '' ?></span></li><?php endforeach; ?></ul>
<?php else: ?><p class="muted"><?= e(t('wf.no_discussions')) ?></p><?php endif; ?>
</div>

<aside class="be_wfside">
<?php if ($decisions): ?><h2><?= e(t('wf.decision')) ?></h2><div class="be_decisions"><?php foreach ($decisions as $d): ?><a class="be_btn<?= $d === WorkflowService::DECLINE ? ' be_btn_danger' : ($d === WorkflowService::ACCEPT || $d === WorkflowService::SEND_REVIEW ? ' be_btn_primary' : '') ?>" href="<?= e(url('decision', 'index', [$sid, $d])) ?>"><?= e(t('dec.' . $d)) ?></a><?php endforeach; ?></div><?php endif; ?>
<?php if ($mgr && \App\Services\ApcService::enabled()): $apc = \App\Services\ApcService::get($sid) ?: []; $apcd = \App\Services\ApcService::defaults(); ?>
<h2><?= e(t('apc.title')) ?></h2>
<form class="cmp_form be_form be_apc" method="post" action="<?= e(url('publication', 'saveApc', [$sid])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('apc.status'), 'status', $apc['status'] ?? 'pending', ['type' => 'select', 'options' => ['pending' => t('apc.pending'), 'paid' => t('apc.paid'), 'waived' => t('apc.waived')]]) ?>
<?= field(t('apc.amount'), 'amount', $apc['amount'] ?? $apcd['amount'], ['maxlength' => 12]) ?><?= field(t('apc.currency'), 'currency', $apc['currency'] ?? $apcd['currency'], ['maxlength' => 3]) ?>
<?= field(t('apc.reference'), 'reference', $apc['reference'] ?? '', ['maxlength' => 190]) ?><?= field(t('apc.note'), 'note', $apc['note'] ?? '', ['maxlength' => 255]) ?></div>
<?php if (!empty($apc['paid_at'])): ?><p class="muted be_small"><?= e(t('apc.paid_on', fmt_date($apc['paid_at']))) ?></p><?php endif; ?>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>
<?php endif; ?>
<h2><?= e(t('wf.participants')) ?> <?php if ($mgr): ?><a class="be_btn be_small" href="<?= e(url('participant', 'assign', [$sid])) ?>"><?= e(t('part.assign')) ?></a><?php endif; ?></h2>
<ul class="be_list"><?php foreach ($participants as $p): ?><li><span><?= e(trim($p['given_name'] . ' ' . $p['family_name'])) ?><br><span class="muted be_small"><?= e($p['role_name']) ?><?= $p['recommend_only'] ? ' (' . e(t('part.recommend_only')) . ')' : '' ?></span></span>
<?php if ($mgr && $p['role_key'] !== 'author'): ?><form class="be_inline" method="post" action="<?= e(url('participant', 'remove', [$sid, $p['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger be_small" type="submit"><?= e(t('part.remove')) ?></button></form><?php endif; ?></li><?php endforeach; ?>
<?php if (!$participants): ?><li class="muted"><?= e(t('wf.no_participants')) ?></li><?php endif; ?></ul>
</aside></div>
<?php endif; ?></div>
