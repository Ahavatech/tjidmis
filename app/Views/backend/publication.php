<?php use App\Core\Settings; ?>
<?php use App\Models\Catalog; $sid = (int)$sub['id']; $tabs = ['title', 'contributors', 'metadata', 'references', 'galleys', 'permissions', 'issue', 'identifiers']; if (!\App\Services\MetadataService::enabled('citations')) $tabs = array_values(array_diff($tabs, ['references']));
$act = fn($op) => url('publication', $op, [$sid]); $save = url('publication', 'save', [$sid, $tab]);
$statusText = $sub['status'] === 'published' ? t('pub.st_published') : ($sub['status'] === 'scheduled' ? t('pub.st_scheduled', $issue ? Catalog::issueLabel($issue) : '') : t('pub.st_unscheduled'));
$er = fn($k) => $errors[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead be_subhead"><div><div class="muted">#<?= $sid ?> / <?= e($sub['title']) ?></div><h1><?= e(t('pub.title')) ?></h1></div>
<div class="be_headright"><span class="be_badge_stage be_badge_<?= e($sub['status']) ?>"><?= e($statusText) ?></span>
<?php if ($sub['status'] === 'published'): ?><a class="be_btn" href="<?= e(url('article', 'view', [$sid])) ?>" target="_blank"><?= e(t('pub.view_live')) ?></a><form method="post" action="<?= e($act('unpublish')) ?>" onsubmit="return confirm('<?= e(t('pub.confirm_unpublish')) ?>')"><?= csrf_field() ?><button class="be_btn be_btn_danger" type="submit"><?= e(t('pub.unpublish')) ?></button></form>
<?php elseif ($sub['status'] === 'scheduled' && $issue && $issue['published']): ?><form method="post" action="<?= e($act('publish')) ?>"><?= csrf_field() ?><button class="be_btn be_btn_primary" type="submit"><?= e(t('pub.publish')) ?></button></form>
<?php elseif ($sub['status'] === 'queued'): ?><a class="be_btn be_btn_primary" href="<?= e(url('publication', 'index', [$sid, 'issue'])) ?>"><?= e(t('pub.schedule')) ?></a><?php endif; ?>
<a class="be_btn" href="<?= e(url('workflow', 'index', [$sid, 5])) ?>"><?= e(t('pub.back_workflow')) ?></a></div></div>
<?php if ($locked): ?><div class="be_notice"><?= e(t('pub.locked')) ?></div><?php endif; ?>
<ul class="be_tabs be_tabs_wrap"><?php foreach ($tabs as $k): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('publication', 'index', [$sid, $k])) ?>"><?= e(t('pub.tab_' . $k)) ?></a></li><?php endforeach; ?></ul>
<div class="be_card">
<?php if ($tab === 'title'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields">
<?= field(t('wizard.prefix'), 'prefix', $pub['prefix'] ?? '', ['maxlength' => 50]) ?><?= field(t('wizard.title_f'), 'title', $pub['title'], ['required' => true, 'maxlength' => 500]) ?><?= field(t('wizard.subtitle'), 'subtitle', $pub['subtitle'] ?? '', ['maxlength' => 500]) ?>
<?= field(t('wizard.abstract'), 'abstract', strip_tags(str_replace(['</p>', '<br>'], "\n\n", (string)$pub['abstract'])), ['type' => 'textarea', 'rows' => 12, 'help' => t('wizard.abstract_help')]) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>

<?php elseif ($tab === 'contributors'): ?>
<?php $gr = []; foreach ($authors as $a) $gr[] = ['cells' => [e(trim($a['given_name'] . ' ' . $a['family_name'])) . ((int)$pub['primary_contact_id'] === (int)$a['id'] ? ' <span class="be_badge_stage">' . e(t('wizard.primary')) . '</span>' : '') . '<div class="muted be_small">' . e($a['affiliation']) . '</div>', e($a['email']), e(t('wizard.role_' . $a['user_group']))], 'actions' => $locked ? [] : array_merge(
  [[t('wizard.edit'), url('publication', 'contributor', [$sid, $a['id']])], [t('grid.move_up'), url('publication', 'moveContributor', [$sid, $a['id'], 'up']), ['post' => 1, 'class' => 'order_items']], [t('grid.move_down'), url('publication', 'moveContributor', [$sid, $a['id'], 'down']), ['post' => 1, 'class' => 'order_items']]],
  count($authors) > 1 ? [[t('wizard.delete'), url('publication', 'deleteContributor', [$sid, $a['id']]), ['post' => 1, 'class' => 'delete']]] : [])]; ?>
<?= grid(t('wizard.contributors'), $locked ? [] : [[t('wizard.add_contributor'), url('publication', 'contributor', [$sid])]], [[t('users.name'), 40], [t('reg.email'), 35], [t('wizard.role'), 25]], $gr) ?>

<?php elseif ($tab === 'metadata'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields">
<?php foreach (array_keys(\App\Services\MetadataService::FIELDS) as $mf): if (!\App\Services\MetadataService::enabled($mf) || $mf === 'citations') continue; $mshape = \App\Services\MetadataService::shape($mf); ?>
<?= field(t('meta.' . $mf), 'meta_' . $mf, \App\Services\MetadataService::toForm($mf, $pub[\App\Services\MetadataService::column($mf)] ?? null), ['help' => $mshape === 'list' ? t('pub.keywords_help') : '']) ?>
<?php endforeach; ?>
<?php $allCats = \App\Core\DB::all('SELECT id, title, parent_id FROM categories WHERE journal_id=? ORDER BY parent_id IS NOT NULL, seq, title', [(int)$sub['journal_id']]); if ($allCats): $mine = array_map('intval', array_column(\App\Core\DB::all('SELECT category_id FROM publication_categories WHERE publication_id=?', [(int)$pub['id']]), 'category_id')); ?>
<fieldset class="be_fs"><legend><?= e(t('cat.categories')) ?></legend><?php foreach ($allCats as $cat): ?><div class="optin"><label><input type="checkbox" name="categories[]" value="<?= (int)$cat['id'] ?>"<?= in_array((int)$cat['id'], $mine, true) ? ' checked' : '' ?>> <?= e(($cat['parent_id'] ? '- ' : '') . $cat['title']) ?></label></div><?php endforeach; ?></fieldset><?php endif; ?>
<p class="muted"><?= e(t('pub.language')) ?>: English</p></div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>

<?php elseif ($tab === 'references'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields">
<?= field(t('wizard.references'), 'references', trim(strip_tags(str_replace('</p>', "\n", (string)$pub['references_text']))), ['type' => 'textarea', 'rows' => 12, 'help' => t('wizard.references_help')]) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>

<?php elseif ($tab === 'galleys'): ?>
<?php $gr = []; foreach ($galleys as $g) { $act = [];
  if (!$locked) { $pid = \App\Services\MetadataService::publisherIdOn('galley') ? ' <input type="text" name="publisher_id" value="' . e($g['publisher_id'] ?? '') . '" maxlength="255" placeholder="' . e(t('pubid.label')) . '" aria-label="' . e(t('pubid.label')) . '">' : '';
    $act[] = ['', '', ['html' => '<input type="text" name="label" value="' . e($g['label']) . '" maxlength="30" aria-label="' . e(t('pub.label')) . '">' . $pid . '<button type="submit" class="pkp_controllers_linkAction pkp_linkaction_icon_edit" formaction="' . e(url('publication', 'renameGalley', [$sid, $g['id']])) . '">' . e(t('wizard.rename')) . '</button>']];
    $act[] = [t('wizard.delete'), url('publication', 'deleteGalley', [$sid, $g['id']]), ['post' => 1, 'class' => 'delete']]; }
  $gr[] = ['cells' => [e($g['label']), $g['submission_file_id'] ? '<a href="' . e(url('workflow', 'download', [$sid, $g['submission_file_id']])) . '">' . e($g['original_name']) . '</a> <span class="muted">(' . e(number_format($g['size'] / 1024, 1)) . ' KB)</span>' : '<a href="' . e($g['remote_url']) . '" target="_blank" rel="noopener">' . e($g['remote_url']) . '</a>'], 'actions' => $act]; } ?>
<?= grid(t('wf.galleys'), [], [[t('pub.label'), 30], [t('wizard.file'), 70]], $gr, t('pub.no_galleys')) ?>
<?php if (!$locked): ?><form class="cmp_form be_form be_upload" method="post" enctype="multipart/form-data" action="<?= e($act('addGalley')) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('pub.label'), 'label', 'PDF', ['required' => true, 'maxlength' => 30, 'help' => t('pub.label_help')]) ?><div><label><span class="label"><?= e(t('wizard.choose_file')) ?></span><input type="file" name="file"></label></div>
<?= field(t('pub.remote_url'), 'remote_url', '', ['help' => t('pub.remote_help')]) ?><?php if (\App\Services\MetadataService::publisherIdOn('galley')): ?><?= field(t('pubid.label'), 'publisher_id', '', ['maxlength' => 255]) ?><?php endif; ?></div><div class="buttons"><button class="be_btn be_btn_primary" type="submit"><?= e(t('pub.add_galley')) ?></button></div></form><?php endif; ?>

<?php elseif ($tab === 'permissions'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields">
<?= field(t('pub.copyright_holder'), 'copyright_holder', $pub['copyright_holder'] ?? '', ['maxlength' => 255]) ?><?= field(t('pub.copyright_year'), 'copyright_year', $pub['copyright_year'] ?? '', ['maxlength' => 4]) ?>
<?= field(t('pub.license'), 'license_url', $pub['license_url'] ?? '', ['help' => t('pub.license_help')]) ?><p class="muted"><?= e(t('pub.license_examples')) ?> <?= e(implode(', ', array_keys(\App\Controllers\Backend\PublicationController::LICENSES))) ?></p></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>

<?php elseif ($tab === 'issue'): ?>
<h2><?= e(t('pub.publication_status')) ?></h2><p><strong><?= e($statusText) ?></strong></p>
<?php if ($sub['status'] === 'queued' || $sub['status'] === 'scheduled'): ?>
<?php if ($missing && $sub['status'] === 'queued'): ?><div class="cmp_notification error"><strong><?= e(t('pub.cannot_schedule')) ?></strong><ul><?php foreach ($missing as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="cmp_form be_form" method="post" action="<?= e($act('schedule')) ?>"><?= csrf_field() ?><div class="fields"><div class="issue"><label><span class="label"><?= e(t('pub.issue')) ?></span><select name="issue_id"><?php foreach ($issues as $is): ?><option value="<?= (int)$is['id'] ?>"<?= (int)($pub['issue_id'] ?? 0) === (int)$is['id'] ? ' selected' : '' ?>><?= e(Catalog::issueLabel($is)) ?> (<?= e($is['published'] ? t('iss.published_tag') : t('iss.future_tag')) ?>)</option><?php endforeach; ?></select></label></div></div>
<div class="buttons"><button class="submit" type="submit"<?= $sub['status'] === 'queued' && $missing ? ' disabled' : '' ?>><?= e($sub['status'] === 'scheduled' ? t('pub.change_issue') : t('pub.schedule')) ?></button></div></form>
<?php if ($sub['status'] === 'scheduled'): ?><form method="post" action="<?= e($act('unschedule')) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('pub.unschedule')) ?></button></form><?php endif; ?>
<?php elseif ($sub['status'] === 'published'): ?><p><?= e(t('pub.in_issue')) ?>: <a href="<?= e(url('issue', 'view', [$issue['id']])) ?>"><?= e(Catalog::issueLabel($issue)) ?></a></p><?php endif; ?>
<hr><h2><?= e(t('pub.issue_data')) ?></h2>
<form class="cmp_form be_form" method="post" enctype="multipart/form-data" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields">
<?= field(t('wizard.section'), 'section_id', $pub['section_id'], ['type' => 'select', 'options' => array_column($sections, 'title', 'id'), 'required' => true]) ?><?= field(t('pub.pages'), 'pages', $pub['pages'] ?? '', ['maxlength' => 50]) ?>
<?= field(t('pub.url_path'), 'url_path', $pub['url_path'] ?? '', ['maxlength' => 64]) ?><?= field(t('pub.date_published'), 'date_published', $pub['date_published'] ?? '', ['type' => 'date']) ?>
<?php if ($pub['cover_image']): ?><p><img src="<?= e(public_url($pub['cover_image'])) ?>" alt="" height="80"></p><?php endif; ?><div><label><span class="label"><?= e(t('pub.cover')) ?></span><input type="file" name="cover" accept="image/png,image/jpeg,image/gif"></label></div></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>

<?php else: ?>
<h2><?= e(t('pub.doi')) ?></h2>
<form class="cmp_form be_form" method="post" action="<?= e($save) ?>"><?= csrf_field() ?><fieldset <?= $locked ? 'disabled' : '' ?> class="be_plain"><div class="fields"><?= field(t('pub.doi'), 'doi', $pub['doi'] ?? '', ['help' => t('pub.doi_help')]) ?><?php if (\App\Services\MetadataService::publisherIdOn('publication')): ?><?= field(t('pubid.label'), 'publisher_id', $pub['publisher_id'] ?? '', ['maxlength' => 255]) ?><?php endif; ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></fieldset></form>
<?php if (!$locked && empty($pub['doi']) && Settings::get('doiPrefix', '')): ?><form method="post" action="<?= e($act('assignDoi')) ?>"><?= csrf_field() ?><button class="be_btn" type="submit"><?= e(t('pub.assign_doi', \App\Services\DoiService::build($sid) ?? '')) ?></button></form><?php endif; ?>
<?php endif; ?></div></div>
