<?php use App\Models\SubmissionRepo;
$qs = fn(array $o = []) => url('submissions', null, [], array_filter(array_merge(['tab' => $tab, 'q' => $f['q'], 'stage' => $f['stage'] ?: null, 'section' => $f['section'] ?: null, 'incomplete' => $f['incomplete'] ? 1 : null,
    'overdue' => $f['overdue'] ? 1 : null, 'days' => $f['days'] ?: null, 'assigned' => $f['assigned'] ?: null, 'issue' => $f['issue'] ?: null], $o), fn($v) => $v !== null && $v !== ''));
$panelTitle = ['queue' => 'dash.panel_queue', 'unassigned' => 'dash.panel_unassigned', 'active' => 'dash.panel_active', 'archives' => 'dash.panel_archives'][$tab];
$filtersOn = $f['stage'] || $f['section'] || $f['incomplete'] || $f['overdue'] || $f['days'] || $f['assigned'] || $f['issue']; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.submissions')) ?></h1></div>
<ul class="be_tabs" role="tablist"><?php foreach ($tabs as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('submissions', null, [], ['tab' => $k])) ?>"><?= e($l) ?><?php if ((int)$counts[$k] > 0): ?> <span class="be_count"><?= (int)$counts[$k] ?></span><?php endif; ?></a></li><?php endforeach; ?></ul>
<div class="be_card">
<div class="be_panelhead"><h2><?= e(t($panelTitle)) ?></h2><?php if ($canSubmit): ?><a class="be_btn be_btn_primary" href="<?= e(url('submission', 'wizard')) ?>"><?= e(t('dash.new_submission')) ?></a><?php endif; ?></div>
<form class="be_filters be_dashfilters" method="get" action="<?= e(url('submissions')) ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('dash.search')) ?>" aria-label="<?= e(t('dash.search')) ?>">
  <button class="be_btn" type="submit"><?= e(t('dash.search_btn')) ?></button>
  <details class="be_filterpanel"<?= $filtersOn ? ' open' : '' ?>><summary class="be_btn"><?= e(t('dash.filters')) ?></summary><div class="be_filtergroups">
    <fieldset><label class="be_check"><input type="checkbox" name="overdue" value="1"<?= $f['overdue'] ? ' checked' : '' ?>> <?= e(t('dash.overdue')) ?></label>
      <label class="be_check"><input type="checkbox" name="incomplete" value="1"<?= $f['incomplete'] ? ' checked' : '' ?>> <?= e(t('dash.incomplete')) ?></label></fieldset>
    <fieldset><legend><?= e(t('dash.stages')) ?></legend><?php foreach (SubmissionRepo::STAGES as $id => $k): if ((int)$id === 2) continue; ?><label class="be_check"><input type="checkbox" name="stage[]" value="<?= (int)$id ?>"<?= in_array((int)$id, $f['stage'], true) ? ' checked' : '' ?>> <?= e(t($k)) ?></label><?php endforeach; ?></fieldset>
    <fieldset><legend><?= e(t('dash.activity')) ?></legend><label><?= e(t('dash.days_since')) ?> <input type="number" min="0" name="days" value="<?= $f['days'] ?: '' ?>"></label></fieldset>
    <?php if (in_array($tab, ['active', 'archives'], true)): ?><fieldset><label><?= e(t('dash.assigned_to_editor')) ?> <select name="assigned"><option value=""></option><?php foreach ($editors as $ed): ?><option value="<?= (int)$ed['id'] ?>"<?= $f['assigned'] === (int)$ed['id'] ? ' selected' : '' ?>><?= e(trim($ed['given_name'] . ' ' . $ed['family_name'])) ?></option><?php endforeach; ?></select></label></fieldset><?php endif; ?>
    <fieldset><legend><?= e(t('dash.sections')) ?></legend><?php foreach ($sections as $sc): ?><label class="be_check"><input type="checkbox" name="section[]" value="<?= (int)$sc['id'] ?>"<?= in_array((int)$sc['id'], $f['section'], true) ? ' checked' : '' ?>> <?= e($sc['title']) ?></label><?php endforeach; ?></fieldset>
    <fieldset><label><?= e(t('dash.issues')) ?> <select name="issue"><option value=""></option><?php foreach ($issues as $is): ?><option value="<?= (int)$is['id'] ?>"<?= $f['issue'] === (int)$is['id'] ? ' selected' : '' ?>><?= e(\App\Models\Catalog::issueLabel($is)) ?></option><?php endforeach; ?></select></label></fieldset>
    <div class="be_filteractions"><button class="be_btn be_btn_primary" type="submit"><?= e(t('dash.apply')) ?></button><?php if ($filtersOn || $f['q']): ?> <a href="<?= e(url('submissions', null, [], ['tab' => $tab])) ?>"><?= e(t('dash.clear')) ?></a><?php endif; ?></div>
  </div></details>
</form>
<?php if (!$rows): ?><p class="be_empty muted"><?= e(t('dash.empty')) ?></p><?php else: ?>
<ul class="be_sublist"><?php foreach ($rows as $r): $rev = !empty($r['review_only']); $revUrl = $rev ? url('reviewer', 'submission', [$r['my_review_id']]) : ''; $mineDraft = $r['status'] === 'incomplete' && (int)$r['created_by'] === \App\Core\Auth::id(); ?>
<li class="be_subitem"><div class="be_subid">#<?= (int)$r['id'] ?></div>
  <div class="be_subbody"><div class="be_subtitle"><a href="<?= e($rev ? $revUrl : ($mineDraft ? url('submission', 'wizard', [$r['id']]) : url('workflow', 'index', [$r['id']]))) ?>"><?= e($rev ? t('dash.review_assignment', $r['title']) : ($r['title'] ?: t('dash.untitled'))) ?></a></div>
    <div class="be_submeta"><?= $rev ? e(t('dash.review_due', fmt_date($r['my_review_due']))) : e($r['authors_short'] ?: '-') ?><?= $r['section_title'] ? ' &middot; ' . e($r['section_title']) : '' ?><?= $r['open_discussions'] ? ' &middot; <span class="be_disc">' . e(t('dash.discussions', (int)$r['open_discussions'])) . '</span>' : '' ?></div>
    <div class="be_substatus"><?= $rev ? e(t('review.status_' . $r['my_review_status'])) : e(SubmissionRepo::statusText($r)) ?></div></div>
  <div class="be_subright"><?php if ($rev): ?><span class="be_badge_stage"><?= e(t('stage.review')) ?></span><a class="be_btn" href="<?= e($revUrl) ?>"><?= e(in_array($r['my_review_status'], ['review_submitted', 'thanked'], true) ? t('dash.view_review') : ($r['my_review_status'] === 'awaiting_response' ? t('dash.begin_review') : t('dash.continue_review'))) ?></a><?php elseif ($r['status'] === 'incomplete'): ?><span class="be_badge_stage be_badge_incomplete"><?= e(t('dash.incomplete_badge')) ?></span>
    <?php elseif (in_array($r['status'], ['published', 'declined'], true)): ?><span class="be_badge_stage be_badge_<?= e($r['status']) ?>"><?= e(t('status.' . $r['status'])) ?></span>
    <?php else: ?><span class="be_badge_stage"><?= e(t(SubmissionRepo::STAGES[$r['stage_id']] ?? 'stage.submission')) ?></span><?php endif; ?>
    <?php if (!$rev): ?><a class="be_btn" href="<?= e($mineDraft ? url('submission', 'wizard', [$r['id']]) : url('workflow', 'index', [$r['id']])) ?>"><?= e($mineDraft ? t('dash.continue') : t('dash.view')) ?></a><?php endif; ?></div></li>
<?php endforeach; ?></ul>
<?= paginate($total, $page, $per, fn($n) => $qs(['page' => $n])) ?>
<?php endif; ?></div></div>
