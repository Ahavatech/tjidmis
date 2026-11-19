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
<h2><?= e(t('wf.files')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wizard.component')) ?></th><th><?= e(t('wf.uploaded')) ?></th></tr></thead><tbody>
<?php foreach ($files as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['genre']) ?></td><td><?= e(fmt_date($f['created_at'])) ?></td></tr><?php endforeach; ?>
<?php if (!$files): ?><tr><td colspan="3" class="muted"><?= e(t('wizard.no_files')) ?></td></tr><?php endif; ?></tbody></table>

<?php elseif ($isReviewStage): ?>
<?php if (!$round): ?><p class="muted"><?= e(t('wf.no_round')) ?></p><?php else: ?>
<ul class="be_roundtabs"><?php foreach ($rounds as $r): ?><li class="<?= (int)$r['id'] === (int)$round['id'] ? 'active' : '' ?>"><a href="<?= e($stageUrl(3, ['round' => $r['round']])) ?>"><?= e(t('wf.round', (int)$r['round'])) ?></a></li><?php endforeach; ?></ul>
<h2><?= e(t('wf.review_files')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wizard.component')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($reviewFiles as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['genre']) ?></td><td class="be_actions"><?php if ($mgr && $f['file_stage'] === 'review_file'): ?><form class="be_inline" method="post" action="<?= e(url('workflow', 'deleteFile', [$sid, $f['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$reviewFiles): ?><tr><td colspan="3" class="muted"><?= e(t('wizard.no_files')) ?></td></tr><?php endif; ?></tbody></table>
<?php if ($mgr && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id']): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadFile', [$sid, $round['id']])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.add_review_file')) ?></button></form><?php endif; ?>

<?php if ($mgr): ?><h2><?= e(t('wf.reviewers')) ?> <?php if ($sub['status'] === 'queued' && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id']): ?><a class="be_btn be_btn_primary be_small" href="<?= e(url('review', 'search', [$sid])) ?>"><?= e(t('rev.add')) ?></a><?php endif; ?></h2>
<table class="be_table be_reviewers"><thead><tr><th><?= e(t('wf.reviewer')) ?></th><th><?= e(t('wf.status')) ?></th><th><?= e(t('wf.response_due')) ?></th><th><?= e(t('wf.review_due')) ?></th><th><?= e(t('wf.recommendation')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($reviewers as $ra): $live = in_array($ra['status'], ['awaiting_response', 'accepted'], true); $open = $sub['status'] === 'queued' && (int)$round['id'] === (int)WorkflowService::currentRound($sid)['id']; ?>
<tr class="<?= ReviewService::isOverdue($ra) ? 'be_overdue' : '' ?><?= in_array($ra['status'], ['cancelled', 'declined'], true) ? ' be_muted_row' : '' ?>"><td><?= e(trim($ra['given_name'] . ' ' . $ra['family_name'])) ?><div class="muted be_small"><?= e(t(ReviewService::METHODS[$ra['review_method']])) ?></div></td>
<td><span class="be_chip be_chip_<?= e($ra['status']) ?>"><?= e(ReviewService::statusLabel($ra)) ?></span></td><td><?= e(fmt_date($ra['date_response_due'])) ?></td><td><?= e(fmt_date($ra['date_due'])) ?></td>
<td><?= $ra['recommendation'] ? e(t(ReviewService::RECOMMENDATIONS[$ra['recommendation']])) : '-' ?></td>
<td class="be_actions"><?php if (in_array($ra['status'], ['review_submitted', 'thanked'], true)): ?><a class="be_btn" href="<?= e(url('review', 'details', [$sid, $ra['id']])) ?>"><?= e(t('rev.details')) ?></a><?php endif; ?>
<?php if ($open): ?><?php if ($live): ?><a class="be_btn" href="<?= e(url('review', 'edit', [$sid, $ra['id']])) ?>"><?= e(t('rev.edit_deadlines')) ?></a>
<?php foreach (['remind' => 'rev.remind', 'unassign' => 'rev.unassign'] as $op => $lbl): ?><form class="be_inline" method="post" action="<?= e(url('review', $op, [$sid, $ra['id']])) ?>"><?= csrf_field() ?><button class="be_btn<?= $op === 'unassign' ? ' be_btn_danger' : '' ?>" type="submit"><?= e(t($lbl)) ?></button></form><?php endforeach; ?><?php endif; ?>
<?php if ($ra['status'] === 'review_submitted'): ?><form class="be_inline" method="post" action="<?= e(url('review', 'thank', [$sid, $ra['id']])) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('rev.thank')) ?></button></form><?php endif; ?>
<?php if (in_array($ra['status'], ['cancelled', 'declined'], true)): ?><form class="be_inline" method="post" action="<?= e(url('review', 'reinstate', [$sid, $ra['id']])) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('rev.reinstate')) ?></button></form><?php endif; ?><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$reviewers): ?><tr><td colspan="6" class="muted"><?= e(t('wf.no_reviewers')) ?></td></tr><?php endif; ?></tbody></table><?php endif; ?>

<h2><?= e(t('wf.revisions')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wf.by')) ?></th><th><?= e(t('wf.uploaded')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($revisions as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['uploader']) ?></td><td><?= e(fmt_date($f['created_at'])) ?></td>
<td class="be_actions"><?php if ($mgr || ((int)$f['uploader_user_id'] === $me && $canRevise)): ?><form class="be_inline" method="post" action="<?= e(url('workflow', 'deleteFile', [$sid, $f['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$revisions): ?><tr><td colspan="4" class="muted"><?= e(t('wf.no_revisions')) ?></td></tr><?php endif; ?></tbody></table>
<?php if ($canRevise): ?><div class="be_notice"><?= e(t('wf.revisions_requested')) ?></div><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadFile', [$sid, $round['id']])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn be_btn_primary" type="submit"><?= e(t('wf.upload_revision')) ?></button></form><?php endif; ?>

<?php if ($roundDecisions): ?><h2><?= e(t('wf.decisions')) ?></h2><ul class="be_list"><?php foreach ($roundDecisions as $d): ?><li><span><?= e(t('dec.' . $d['decision'])) ?> <span class="muted">&mdash; <?= e($mgr ? $d['who'] : t('wf.editor')) ?></span></span><span class="muted"><?= e(fmt_date($d['date_decided'])) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
<?php endif; ?>

<?php elseif ($stage === 4): ?>
<h2><?= e(t('wf.copyedit_files')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wizard.component')) ?></th><th><?= e(t('wf.uploaded')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($draftFiles as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e($f['genre']) ?></td><td><?= e(fmt_date($f['created_at'])) ?></td><td class="be_actions"><?php if ($canWork): ?><form class="be_inline" method="post" action="<?= e(url('workflow', 'deleteFile', [$sid, $f['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$draftFiles): ?><tr><td colspan="4" class="muted"><?= e(t('wf.no_copyedit_files')) ?></td></tr><?php endif; ?></tbody></table>
<?php if ($canWork && $sub['status'] === 'queued'): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadStageFile', [$sid, 4])) ?>"><?= csrf_field() ?><select name="genre"><option value="Draft"><?= e(t('wf.genre_draft')) ?></option><option value="Copyedited"><?= e(t('wf.genre_copyedited')) ?></option></select><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.upload_file')) ?></button></form><?php endif; ?>
<?php if ($mgr && $candidates && $sub['status'] === 'queued'): ?><h2><?= e(t('wf.select_files')) ?></h2><table class="be_table"><tbody><?php foreach ($candidates as $f): ?><tr><td><?= e($f['original_name']) ?> <span class="muted">(<?= e($f['genre']) ?>)</span></td><td class="be_actions"><form class="be_inline" method="post" action="<?= e(url('workflow', 'promote', [$sid, $f['id'], 'copyedit'])) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('wf.add_to_copyediting')) ?></button></form></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>

<?php elseif ($stage === 5): ?>
<h2><?= e(t('wf.prod_files')) ?></h2>
<table class="be_table"><thead><tr><th><?= e(t('wizard.file')) ?></th><th><?= e(t('wf.uploaded')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($prodFiles as $f): ?><tr><td><a href="<?= e(url('workflow', 'download', [$sid, $f['id']])) ?>"><?= e($f['original_name']) ?></a></td><td><?= e(fmt_date($f['created_at'])) ?></td><td class="be_actions"><?php if ($canWork && $sub['status'] !== 'published'): ?><form class="be_inline" method="post" action="<?= e(url('workflow', 'deleteFile', [$sid, $f['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete')) ?></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$prodFiles): ?><tr><td colspan="3" class="muted"><?= e(t('wf.no_prod_files')) ?></td></tr><?php endif; ?></tbody></table>
<?php if ($canWork && !in_array($sub['status'], ['published', 'declined'], true)): ?><form class="be_upload be_inline_form" method="post" enctype="multipart/form-data" action="<?= e(url('workflow', 'uploadStageFile', [$sid, 5])) ?>"><?= csrf_field() ?><input type="file" name="file" required><button class="be_btn" type="submit"><?= e(t('wf.upload_file')) ?></button></form><?php endif; ?>
<h2><?= e(t('wf.galleys')) ?> <?php if ($mgr): ?><a class="be_btn be_small" href="<?= e(url('publication', 'index', [$sid, 'galleys'])) ?>"><?= e(t('wf.manage_galleys')) ?></a><?php endif; ?></h2>
<ul class="be_list"><?php foreach ($galleys as $g): ?><li><span><?= e($g['label']) ?> <span class="muted"><?= e($g['original_name'] ?: $g['remote_url']) ?></span></span></li><?php endforeach; ?><?php if (!$galleys): ?><li class="muted"><?= e(t('pub.no_galleys')) ?></li><?php endif; ?></ul>
<?php if ($mgr): ?><p><a class="be_btn be_btn_primary" href="<?= e(url('publication', 'index', [$sid, 'issue'])) ?>"><?= e(in_array($sub['status'], ['scheduled', 'published'], true) ? t('wf.open_publication') : t('pub.schedule')) ?></a></p><?php endif; ?>
<?php endif; ?>

<h2><?= e(t('wf.discussions')) ?> <?php if ($sub['status'] === 'queued' || $mgr): ?><a class="be_btn be_small" href="<?= e(url('discussion', 'create', [$sid, $stage])) ?>"><?= e(t('disc.add')) ?></a><?php endif; ?></h2>
<?php if ($discussions): ?><ul class="be_list"><?php foreach ($discussions as $q): ?><li class="<?= !empty($q['unread']) ? 'unread' : '' ?>"><span><a href="<?= e(url('discussion', 'view', [$q['id']])) ?>"><?= e($q['subject']) ?></a><?= $q['closed'] ? ' <span class="be_chip">' . e(t('disc.closed')) . '</span>' : '' ?></span><span class="muted"><?= e(t('disc.messages', (int)$q['notes'])) ?><?= !empty($q['unread']) ? ' &middot; ' . e(t('disc.unread', (int)$q['unread'])) : '' ?></span></li><?php endforeach; ?></ul>
<?php else: ?><p class="muted"><?= e(t('wf.no_discussions')) ?></p><?php endif; ?>
</div>

<aside class="be_wfside">
<?php if ($decisions): ?><h2><?= e(t('wf.decision')) ?></h2><div class="be_decisions"><?php foreach ($decisions as $d): ?><a class="be_btn<?= $d === WorkflowService::DECLINE ? ' be_btn_danger' : ($d === WorkflowService::ACCEPT || $d === WorkflowService::SEND_REVIEW ? ' be_btn_primary' : '') ?>" href="<?= e(url('decision', 'index', [$sid, $d])) ?>"><?= e(t('dec.' . $d)) ?></a><?php endforeach; ?></div><?php endif; ?>
<h2><?= e(t('wf.participants')) ?> <?php if ($mgr): ?><a class="be_btn be_small" href="<?= e(url('participant', 'assign', [$sid])) ?>"><?= e(t('part.assign')) ?></a><?php endif; ?></h2>
<ul class="be_list"><?php foreach ($participants as $p): ?><li><span><?= e(trim($p['given_name'] . ' ' . $p['family_name'])) ?><br><span class="muted be_small"><?= e($p['role_name']) ?><?= $p['recommend_only'] ? ' (' . e(t('part.recommend_only')) . ')' : '' ?></span></span>
<?php if ($mgr && $p['role_key'] !== 'author'): ?><form class="be_inline" method="post" action="<?= e(url('participant', 'remove', [$sid, $p['id']])) ?>"><?= csrf_field() ?><button class="be_btn be_btn_danger be_small" type="submit"><?= e(t('part.remove')) ?></button></form><?php endif; ?></li><?php endforeach; ?>
<?php if (!$participants): ?><li class="muted"><?= e(t('wf.no_participants')) ?></li><?php endif; ?></ul>
</aside></div>
<?php endif; ?></div>
