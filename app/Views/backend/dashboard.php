<?php use App\Models\SubmissionRepo;
$qs = fn(array $o = []) => url('submissions', null, [], array_filter(array_merge(['tab' => $tab, 'q' => $f['q'], 'stage' => $f['stage'] ?: null, 'section' => $f['section'] ?: null, 'incomplete' => $f['incomplete'] ? 1 : null], $o), fn($v) => $v !== null && $v !== '')); ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.submissions')) ?></h1>
<?php if ($canSubmit): ?><a class="be_btn be_btn_primary" href="<?= e(url('submission', 'wizard')) ?>"><?= e(t('dash.new_submission')) ?></a><?php endif; ?></div>
<ul class="be_tabs" role="tablist"><?php foreach ($tabs as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('submissions', null, [], ['tab' => $k])) ?>"><?= e($l) ?> <span class="be_count"><?= (int)$counts[$k] ?></span></a></li><?php endforeach; ?></ul>
<div class="be_card">
<form class="be_filters" method="get" action="<?= e(url('submissions')) ?>"><input type="hidden" name="tab" value="<?= e($tab) ?>">
  <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="<?= e(t('dash.search')) ?>" aria-label="<?= e(t('dash.search')) ?>">
  <select name="stage" aria-label="<?= e(t('dash.stage')) ?>"><option value=""><?= e(t('dash.all_stages')) ?></option><?php foreach (SubmissionRepo::STAGES as $id => $k): ?><option value="<?= $id ?>"<?= $f['stage'] === $id ? ' selected' : '' ?>><?= e(t($k)) ?></option><?php endforeach; ?></select>
  <select name="section" aria-label="<?= e(t('dash.section')) ?>"><option value=""><?= e(t('dash.all_sections')) ?></option><?php foreach ($sections as $s): ?><option value="<?= (int)$s['id'] ?>"<?= $f['section'] === (int)$s['id'] ? ' selected' : '' ?>><?= e($s['title']) ?></option><?php endforeach; ?></select>
  <label class="be_check"><input type="checkbox" name="incomplete" value="1"<?= $f['incomplete'] ? ' checked' : '' ?>> <?= e(t('dash.incomplete')) ?></label>
  <button class="be_btn" type="submit"><?= e(t('dash.filter')) ?></button><?php if ($f['q'] || $f['stage'] || $f['section'] || $f['incomplete']): ?><a href="<?= e(url('submissions', null, [], ['tab' => $tab])) ?>"><?= e(t('dash.clear')) ?></a><?php endif; ?>
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
