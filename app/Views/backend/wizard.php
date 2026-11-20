<?php use App\Core\{Settings, Countries}; $steps = [1 => 'wizard.step1', 2 => 'wizard.step2', 3 => 'wizard.step3', 4 => 'wizard.step4']; $er = fn($k) => $errors[$k] ?? '';
$sid = $sub['id'] ?? null; $stepUrl = fn($i) => $sid ? url('submission', 'wizard', [$sid, $i]) : url('submission', 'wizard'); ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('wizard.title')) ?></h1>
<?php if ($sid): ?><form method="post" action="<?= e(url('submission', 'deleteDraft', [$sid])) ?>" onsubmit="return confirm('<?= e(t('wizard.confirm_delete')) ?>')"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('wizard.delete_draft')) ?></button></form><?php endif; ?></div>
<ol class="be_steps"><?php foreach ($steps as $i => $k): ?><li class="<?= $i === $n ? 'active' : '' ?><?= $i < $progress ? ' done' : '' ?><?= $i > $progress ? ' locked' : '' ?>">
  <?php if ($i <= $progress): ?><a href="<?= e($stepUrl($i)) ?>"><span class="be_stepno"><?= $i ?></span> <?= e(t($k)) ?></a><?php else: ?><span><span class="be_stepno"><?= $i ?></span> <?= e(t($k)) ?></span><?php endif; ?></li><?php endforeach; ?></ol>
<div class="be_card be_wizard">
<?= errors_summary($errors) ?>

<?php if ($n === 1): ?>
<form class="cmp_form be_form" method="post" action="<?= e($stepUrl(1)) ?>"><?= csrf_field() ?>
<div class="fields">
  <div class="locale"><label><span class="label"><?= e(t('wizard.language')) ?> <span class="required">*</span></span><select name="locale"><option value="en" selected>English</option></select></label></div>
  <?= field(t('wizard.section'), 'section_id', $sub['section_id'] ?? ($_POST['section_id'] ?? ''), ['type' => 'select', 'required' => true, 'options' => array_column($sections, 'title', 'id'), 'error' => $er('section_id'), 'help' => t('wizard.section_help')]) ?>
  <?php foreach ($sections as $s) if ($s['policy']): ?><p class="muted be_sectionpolicy"><strong><?= e($s['title']) ?>:</strong> <?= e($s['policy']) ?></p><?php endif; ?>
  <fieldset class="be_fs"><legend><?= e(t('wizard.requirements')) ?></legend><p class="muted"><?= e(t('wizard.requirements_intro')) ?></p>
    <?php foreach ($checklist as $i => $c): ?><div class="optin"><label><input type="checkbox" name="checklist[<?= $i ?>]" value="1"<?= !empty($_POST['checklist'][$i]) || ($sub && $progress >= 2) ? ' checked' : '' ?>> <?= e($c) ?></label></div><?php endforeach; ?>
    <?= $er('checklist') ? '<span class="field_error" role="alert">' . e($er('checklist')) . '</span>' : '' ?></fieldset>
  <?= field(t('wizard.comments'), 'comments_to_editor', $sub['comments_to_editor'] ?? ($_POST['comments_to_editor'] ?? ''), ['type' => 'textarea', 'rows' => 5, 'class' => 'comments']) ?>
  <fieldset class="be_fs"><legend><?= e(t('wizard.consent')) ?></legend>
    <?= checkbox('privacyConsent', e(t('reg.consent_pre')) . ' <a href="' . e(url('about', 'privacy')) . '" target="_blank">' . e(t('reg.consent_link')) . '</a>.', !empty($_POST['privacyConsent']) || ($sub && $progress >= 2), '1', $er('privacyConsent')) ?>
    <?php if ($cn = Settings::get('copyrightNotice', '')): ?><div class="be_notice"><?= rich($cn) ?></div><?= checkbox('copyrightAgree', e(t('wizard.copyright_agree')), !empty($_POST['copyrightAgree']) || ($sub && $progress >= 2), '1', $er('copyrightAgree')) ?><?php endif; ?></fieldset>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save_continue')) ?></button><a href="<?= e(url('submissions')) ?>"><?= e(t('wizard.cancel')) ?></a></div></form>

<?php elseif ($n === 2): ?>
<h2><?= e(t('wizard.upload_files')) ?></h2><p class="muted"><?= e(t('wizard.upload_intro')) ?></p>
<?= $er('files') ? '<div class="cmp_notification error" role="alert">' . e($er('files')) . '</div>' : '' ?>
<?php $gr = []; foreach ($files as $f) $gr[] = ['cells' => ['<a href="' . e(url('workflow', 'download', [$sid, $f['id']])) . '">' . e($f['original_name']) . '</a>', e($f['genre']), e(number_format($f['size'] / 1024, 1)) . ' KB'], 'actions' => [
  ['', '', ['html' => '<input type="text" name="name" value="' . e($f['original_name']) . '" aria-label="' . e(t('wizard.rename')) . '"><button type="submit" class="pkp_controllers_linkAction pkp_linkaction_icon_edit" formaction="' . e(url('submission', 'renameFile', [$sid, $f['id']])) . '">' . e(t('wizard.rename')) . '</button>']],
  [t('wizard.delete'), url('submission', 'deleteFile', [$sid, $f['id']]), ['post' => 1, 'class' => 'delete']]]]; ?>
<?= grid(t('wizard.files'), [], [[t('wizard.file'), 45], [t('wizard.component'), 30], [t('wizard.size'), 25]], $gr, t('wizard.no_files')) ?>
<form class="cmp_form be_form be_upload" method="post" enctype="multipart/form-data" action="<?= e(url('submission', 'uploadFile', [$sid])) ?>"><?= csrf_field() ?>
<div class="fields"><div><label><span class="label"><?= e(t('wizard.component')) ?></span><select name="component"><?php foreach ($components as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label></div>
<div><label><span class="label"><?= e(t('wizard.choose_file')) ?></span><input type="file" name="file" required></label><span class="help"><?= e(t('wizard.file_types')) ?></span></div></div>
<div class="buttons"><button class="be_btn" type="submit"><?= e(t('wizard.upload')) ?></button></div></form>
<form method="post" action="<?= e($stepUrl(2)) ?>"><?= csrf_field() ?><div class="buttons"><a class="be_btn" href="<?= e($stepUrl(1)) ?>"><?= e(t('wizard.back')) ?></a><button class="submit" type="submit"><?= e(t('wizard.save_continue')) ?></button></div></form>

<?php elseif ($n === 3): ?>
<form class="cmp_form be_form" method="post" action="<?= e($stepUrl(3)) ?>"><?= csrf_field() ?>
<div class="fields">
  <?= field(t('wizard.prefix'), 'prefix', $sub['prefix'] ?? '', ['maxlength' => 50, 'help' => t('wizard.prefix_help')]) ?>
  <?= field(t('wizard.title_f'), 'title', $_POST['title'] ?? $sub['title'], ['required' => true, 'maxlength' => 500, 'error' => $er('title')]) ?>
  <?= field(t('wizard.subtitle'), 'subtitle', $_POST['subtitle'] ?? ($sub['subtitle'] ?? ''), ['maxlength' => 500]) ?>
  <?= field(t('wizard.abstract'), 'abstract', $_POST['abstract'] ?? strip_tags(str_replace(['</p>', '<br>'], "\n\n", (string)$sub['abstract'])), ['type' => 'textarea', 'rows' => 9, 'required' => true, 'error' => $er('abstract'), 'help' => t('wizard.abstract_help')]) ?>
  <fieldset class="be_fs"><legend><?= e(t('wizard.contributors')) ?></legend>
    <?= $er('authors') ? '<span class="field_error" role="alert">' . e($er('authors')) . '</span>' : '' ?>
    <?php $gr = []; foreach ($authors as $a) $gr[] = ['cells' => [e(trim($a['given_name'] . ' ' . $a['family_name'])) . ((int)$sub['primary_contact_id'] === (int)$a['id'] ? ' <span class="be_badge_stage">' . e(t('wizard.primary')) . '</span>' : ''), e($a['email']), e(t('wizard.role_' . $a['user_group']))], 'actions' => [
  [t('wizard.edit'), url('submission', 'contributor', [$sid, $a['id']])],
  [t('grid.move_up'), url('submission', 'moveContributor', [$sid, $a['id'], 'up']), ['post' => 1, 'class' => 'order_items']], [t('grid.move_down'), url('submission', 'moveContributor', [$sid, $a['id'], 'down']), ['post' => 1, 'class' => 'order_items']],
  [t('wizard.delete'), url('submission', 'deleteContributor', [$sid, $a['id']]), ['post' => 1, 'class' => 'delete']]]]; ?>
<?= grid(t('wizard.contributors'), [], [[t('users.name'), 40], [t('reg.email'), 35], [t('wizard.role'), 25]], $gr, t('wizard.no_contributors'), true) ?>
    <button class="be_btn" type="submit" name="action" value="add_contributor" formnovalidate><?= e(t('wizard.add_contributor')) ?></button> <span class="muted"><?= e(t('wizard.contrib_note')) ?></span></fieldset>
  <?php foreach (array_keys(\App\Services\MetadataService::FIELDS) as $mf): if (!\App\Services\MetadataService::asked($mf)) continue; $col = \App\Services\MetadataService::column($mf); $shape = \App\Services\MetadataService::shape($mf); ?>
  <?= field(t('meta.' . $mf), 'meta_' . $mf, $_POST['meta_' . $mf] ?? \App\Services\MetadataService::toForm($mf, $sub[$col] ?? null), ['required' => \App\Services\MetadataService::required($mf), 'type' => $shape === 'refs' ? 'textarea' : 'text', 'rows' => 6, 'help' => $shape === 'list' ? t('wizard.keywords_help') : ($shape === 'refs' ? t('wizard.references_help') : ''), 'error' => $errors['meta_' . $mf] ?? '']) ?>
  <?php endforeach; ?>
</div><div class="buttons"><a class="be_btn" href="<?= e($stepUrl(2)) ?>"><?= e(t('wizard.back')) ?></a><button class="be_btn" type="submit" name="action" value="save" formnovalidate><?= e(t('wizard.save')) ?></button><button class="submit" type="submit" name="action" value="continue"><?= e(t('wizard.save_continue')) ?></button></div></form>

<?php else: ?>
<h2><?= e(t('wizard.confirm_h')) ?></h2>
<?php if (!empty($missing)): ?><div class="cmp_notification error" role="alert"><strong><?= e(t('wizard.missing')) ?></strong><ul><?php foreach ($missing as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<dl class="be_summary"><dt><?= e(t('wizard.title_f')) ?></dt><dd><?= e(trim(($sub['prefix'] ? $sub['prefix'] . ' ' : '') . $sub['title'])) ?><?= $sub['subtitle'] ? ': ' . e($sub['subtitle']) : '' ?></dd>
<dt><?= e(t('wizard.section')) ?></dt><dd><?= e($sub['section_title']) ?></dd>
<dt><?= e(t('wizard.contributors')) ?></dt><dd><?php foreach ($authors as $a): ?><?= e(trim($a['given_name'] . ' ' . $a['family_name'])) ?> (<?= e($a['email']) ?>)<br><?php endforeach; ?></dd>
<dt><?= e(t('wizard.abstract')) ?></dt><dd><?= $sub['abstract'] ?></dd>
<dt><?= e(t('wizard.upload_files')) ?></dt><dd><?php foreach ($files as $f): ?><?= e($f['original_name']) ?> <span class="muted">(<?= e($f['genre']) ?>)</span><br><?php endforeach; ?></dd></dl>
<p class="muted"><?= e(t('wizard.confirm_text')) ?></p>
<form method="post" action="<?= e(url('submission', 'finish', [$sid])) ?>" onsubmit="return confirm('<?= e(t('wizard.confirm_finish')) ?>')"><?= csrf_field() ?><div class="buttons"><a class="be_btn" href="<?= e($stepUrl(3)) ?>"><?= e(t('wizard.back')) ?></a><button class="submit" type="submit"<?= !empty($missing) ? ' disabled' : '' ?>><?= e(t('wizard.finish')) ?></button></div></form>
<?php endif; ?>
</div></div>
