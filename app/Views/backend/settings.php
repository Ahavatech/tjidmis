<?php use App\Controllers\Backend\SettingsController as SC;
$areas = ['journal' => 'menu.settings_journal', 'website' => 'menu.settings_website', 'workflow' => 'menu.settings_workflow', 'distribution' => 'menu.settings_distribution'];
$nav = SC::NAV[$area] ?? []; $top = ''; foreach ($nav as $tk => $leaves) if (in_array($tab, $leaves, true)) $top = $tk; $er = fn($k) => $errors[$k] ?? ''; $post = url('settings', 'save', [$area, $tab]);
$lbl = fn($k) => t('set.' . $k); $help = function ($k) { $h = t('set.' . $k . '_help'); return $h === 'set.' . $k . '_help' ? '' : $h; };
$hasForm = (bool)$fields; $multipart = (bool)array_filter($fields, fn($f) => in_array($f[1], ['image', 'css'], true)); ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.settings')) ?></h1></div>
<h2 class="be_areahead"><?= e(t($areas[$area])) ?></h2>
<ul class="be_tabs"><?php foreach ($nav as $tk => $leaves): ?><li class="<?= $tk === $top ? 'active' : '' ?>"><a href="<?= e(url('settings', 'index', [$area, $leaves[0]])) ?>"><?= e(t('set.top_' . $tk)) ?></a></li><?php endforeach; ?></ul>
<div class="be_card">
<?php if (count($nav[$top] ?? []) > 1): ?><ul class="be_subtabs"><?php foreach ($nav[$top] as $tb): ?><li class="<?= $tb === $tab ? 'active' : '' ?>"><a href="<?= e(url('settings', 'index', [$area, $tb])) ?>"><?= e(t('set.tab_' . $tb)) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
<?= $errors ? errors_summary(array_values($errors)) : '' ?>

<?php if ($area === 'workflow' && $tab === 'metadata'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($post) ?>"><?= csrf_field() ?><div class="fields">
<?php foreach (array_keys(\App\Services\MetadataService::FIELDS) as $mf): $mode = \App\Services\MetadataService::mode($mf); ?>
<fieldset class="be_metafield"><legend><?= e(t('meta.' . $mf)) ?></legend><p class="muted"><?= e(t('meta.' . $mf . '_desc')) ?></p>
<div class="optin"><label><input type="checkbox" name="<?= $mf ?>_on" value="1"<?= $mode !== '0' ? ' checked' : '' ?>> <?= e(t('meta.enable_' . $mf)) ?></label></div>
<?php foreach (\App\Services\MetadataService::MODES as $m): ?><div class="optin be_metamode"><label><input type="radio" name="<?= $mf ?>_mode" value="<?= $m ?>"<?= ($mode === $m || ($mode === '0' && $m === 'enable')) ? ' checked' : '' ?>> <?= e(t('meta.' . $mf . '_' . $m)) ?></label></div><?php endforeach; ?></fieldset>
<?php endforeach; ?>
<fieldset class="be_metafield"><legend><?= e(t('meta.ci')) ?></legend>
<div class="optin"><label><input type="checkbox" name="requireAuthorCompetingInterests" value="1"<?= \App\Services\MetadataService::requireAuthorCI() ? ' checked' : '' ?>> <?= e(t('meta.ci_require')) ?></label></div></fieldset>
<fieldset class="be_metafield"><legend><?= e(t('meta.pubid')) ?></legend><p class="muted"><?= e(t('meta.pubid_desc')) ?></p>
<?php foreach (['publication' => 'meta.pubid_publication', 'galley' => 'meta.pubid_galley', 'issue' => 'meta.pubid_issue'] as $obj => $ol): ?><div class="optin"><label><input type="checkbox" name="pubid_<?= $obj ?>" value="1"<?= \App\Services\MetadataService::publisherIdOn($obj) ? ' checked' : '' ?>> <?= e(t($ol)) ?></label></div><?php endforeach; ?></fieldset>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>

<?php elseif ($tab === 'languages'): ?>
<p class="muted"><?= e(t('lang.instructions')) ?></p>
<table class="be_table"><thead><tr><th><?= e(t('lang.language')) ?></th><th><?= e(t('lang.primary')) ?></th><th><?= e(t('lang.ui')) ?></th><th><?= e(t('lang.forms')) ?></th><th><?= e(t('lang.submissions')) ?></th></tr></thead><tbody>
<tr><td>English</td><td><input type="radio" checked disabled aria-label="<?= e(t('lang.primary')) ?>"></td><td><input type="checkbox" checked disabled></td><td><input type="checkbox" checked disabled></td><td><input type="checkbox" checked disabled></td></tr></tbody></table>
<p class="muted be_small"><?= e(t('lang.only_english')) ?></p>

<?php elseif ($tab === 'reviewForms'): ?>
<p class="muted"><?= e(t('rf.list_description')) ?></p>
<?php $rows = []; foreach ($forms as $f) $rows[] = ['cells' => ['<a href="' . e(url('content', 'reviewForm', [$f['id']])) . '">' . e($f['title']) . '</a>', (int)$f['in_review'], (int)$f['completed'], $f['active'] ? e(t('rf.yes')) : e(t('rf.no'))], 'actions' => [
  [t('wizard.edit'), url('content', 'reviewForm', [$f['id']])], [t('rf.preview'), url('content', 'reviewFormPreview', [$f['id']]), ['class' => 'preview']],
  [t('rf.copy'), url('content', 'reviewFormCopy', [$f['id']]), ['post' => 1, 'class' => 'copy', 'confirm' => t('rf.confirm_copy')]],
  [$f['active'] ? t('rf.deactivate') : t('rf.activate'), url('content', 'reviewFormToggle', [$f['id']]), ['post' => 1, 'class' => 'edit', 'confirm' => $f['active'] ? t('rf.confirm_deactivate') : t('rf.confirm_activate')]],
  [t('wizard.delete'), url('content', 'reviewFormDelete', [$f['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('rf.confirm_delete')]]]]; ?>
<?= grid(t('rf.title'), [[t('rf.create'), url('content', 'reviewForm')]], [[t('rf.title'), 40], [t('rf.in_review'), 20], [t('rf.completed'), 20], [t('rf.active'), 20, 'center']], $rows, t('rf.none_created')) ?>

<?php elseif ($tab === 'pluginGallery'): ?>
<table class="be_table"><thead><tr><th><?= e(t('gal.name')) ?></th><th><?= e(t('gal.summary')) ?></th><th><?= e(t('gal.status')) ?></th></tr></thead><tbody><tr><td colspan="3" class="muted"><?= e(t('gal.none')) ?></td></tr></tbody></table>
<p class="muted be_small"><?= e(t('gal.offline')) ?></p>

<?php elseif ($tab === 'library'): ?>
<?php $rows = []; foreach ($files as $f) $rows[] = ['cells' => ['<a href="' . e(url('content', 'libraryDownload', [$f['id']])) . '">' . e($f['name']) . '</a>', e(t('lib.type_' . (int)$f['file_type'])), e(fmt_date($f['created_at'])), $f['public_access'] ? e(t('lib.yes')) : ''], 'actions' => [
  [t('wizard.edit'), url('content', 'library', [$f['id']])], [t('wizard.delete'), url('content', 'libraryDelete', [$f['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('set.confirm_delete')]]]]; ?>
<?= grid(t('set.tab_library'), [[t('lib.add'), url('content', 'library')]], [[t('cat.name'), 40], [t('lib.type'), 20], [t('lib.date'), 20], [t('lib.public'), 20]], $rows, t('lib.none')) ?>

<?php elseif ($tab === 'categories'): ?>
<?php $rows = []; foreach ($categories as $c) $rows[] = ['cells' => [e($c['title']), '<a href="' . e(url('catalog', 'category', [$c['path']])) . '" target="_blank">/' . e($c['path']) . '</a>', e($c['parent_title'] ?? ''), (int)$c['used']], 'actions' => [
  [t('wizard.edit'), url('content', 'category', [$c['id']])], [t('wizard.delete'), url('content', 'categoryDelete', [$c['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('cat.confirm_remove')]]]]; ?>
<?= grid(t('set.tab_categories'), [[t('cat.add'), url('content', 'category')]], [[t('cat.name'), 35], [t('cat.path'), 25], [t('cat.parent'), 25], [t('iss.articles'), 15]], $rows, t('cat.none')) ?>

<?php elseif ($tab === 'sections'): ?>
<?php $rows = []; $n = count($sections); foreach ($sections as $i => $sc) $rows[] = ['cells' => [e($sc['title']), e($sc['abbrev']), (int)$sc['used']], 'actions' => [
  [t('wizard.edit'), url('content', 'section', [$sc['id']])],
  [t('grid.move_up'), url('content', 'sectionMove', [$sc['id'], 'up']), ['post' => 1, 'class' => 'order_items', 'disabled' => $i === 0]], [t('grid.move_down'), url('content', 'sectionMove', [$sc['id'], 'down']), ['post' => 1, 'class' => 'order_items', 'disabled' => $i === $n - 1]],
  [t('wizard.delete'), url('content', 'sectionDelete', [$sc['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('set.confirm_delete')]]]]; ?>
<?= grid(t('set.tab_sections'), [[t('set.add_section'), url('content', 'section')]], [[t('set.title'), 45], [t('set.abbrev'), 35], [t('iss.articles'), 20]], $rows) ?>

<?php elseif ($tab === 'navigation'): ?>
<p class="muted"><?= e(t('set.nav_help')) ?></p><p><a class="be_btn be_btn_primary" href="<?= e(url('content', 'navItem')) ?>"><?= e(t('set.add_nav')) ?></a></p>
<table class="be_table"><tbody><?php $tn = count($items); foreach ($items as $i => $it): ?>
<tr class="be_navtop"><td><strong><?= e($it['title']) ?></strong> <span class="muted be_small"><?= e(t('set.navtype_' . $it['type'])) ?><?= $it['type'] === 'static_page' && $it['sp_title'] ? ': ' . e($it['sp_title']) : '' ?><?= $it['type'] === 'custom_url' && $it['url'] ? ': ' . e($it['url']) : '' ?></span></td>
<td class="be_actions"><form method="post" class="be_inline"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'navItem', [$it['id']])) ?>"><?= e(t('wizard.edit')) ?></a><button class="be_btn" type="submit" formaction="<?= e(url('content', 'navMove', [$it['id'], 'up'])) ?>"<?= $i === 0 ? ' disabled' : '' ?>>&uarr;</button><button class="be_btn" type="submit" formaction="<?= e(url('content', 'navMove', [$it['id'], 'down'])) ?>"<?= $i === $tn - 1 ? ' disabled' : '' ?>>&darr;</button>
<button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'navDelete', [$it['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_delete')) ?>')"><?= e(t('wizard.delete')) ?></button></form></td></tr>
<?php $cn = count($it['children']); foreach ($it['children'] as $k => $c): ?><tr class="be_navchild"><td>&mdash; <?= e($c['title']) ?> <span class="muted be_small"><?= e(t('set.navtype_' . $c['type'])) ?><?= $c['type'] === 'static_page' && $c['sp_title'] ? ': ' . e($c['sp_title']) : '' ?></span></td>
<td class="be_actions"><form method="post" class="be_inline"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'navItem', [$c['id']])) ?>"><?= e(t('wizard.edit')) ?></a><button class="be_btn" type="submit" formaction="<?= e(url('content', 'navMove', [$c['id'], 'up'])) ?>"<?= $k === 0 ? ' disabled' : '' ?>>&uarr;</button><button class="be_btn" type="submit" formaction="<?= e(url('content', 'navMove', [$c['id'], 'down'])) ?>"<?= $k === $cn - 1 ? ' disabled' : '' ?>>&darr;</button>
<button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'navDelete', [$c['id']])) ?>"><?= e(t('wizard.delete')) ?></button></form></td></tr><?php endforeach; ?><?php endforeach; ?></tbody></table>

<?php elseif ($tab === 'staticPages'): ?>
<?php $rows = []; foreach ($pages as $pg) $rows[] = ['cells' => [e($pg['title']), '<a href="' . e(url($pg['path'])) . '" target="_blank">/' . e($pg['path']) . '</a>'], 'actions' => [
  [t('wizard.edit'), url('content', 'page', [$pg['id']])], [t('wizard.delete'), url('content', 'pageDelete', [$pg['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('set.confirm_delete')]]]]; ?>
<?= grid(t('set.tab_staticPages'), [[t('set.add_page'), url('content', 'page')]], [[t('set.title'), 50], [t('set.path'), 50]], $rows) ?>

<?php elseif ($tab === 'blocks'): ?>
<?php $rows = []; foreach ($blocks as $b) $rows[] = ['cells' => [e($b['title']), e($b['name'])], 'actions' => [
  [t('wizard.edit'), url('content', 'block', [$b['id']])], [t('wizard.delete'), url('content', 'blockDelete', [$b['id']]), ['post' => 1, 'class' => 'delete', 'confirm' => t('set.confirm_delete')]]]]; ?>
<?= grid(t('set.tab_blocks'), [[t('set.add_block'), url('content', 'block')]], [[t('set.title'), 50], [t('set.block_name'), 50]], $rows) ?>
<p class="muted"><?= e(t('set.blocks_help')) ?></p>

<?php elseif ($tab === 'plugins'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($post) ?>"><?= csrf_field() ?><p class="muted"><?= e(t('set.plugins_help')) ?></p><div class="fields">
<?php foreach (SC::FEATURES as $k): ?><?= checkbox($k, '<strong>' . e(t('set.feat_' . $k)) . '</strong> <span class="muted">&mdash; ' . e(t('set.feat_' . $k . '_help')) . '</span>', !empty($features[$k])) ?><?php endforeach; ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>


<?php else: ?>
<?php if ($area === 'distribution' && $tab === 'archiving'): ?><p class="muted"><?= e(t('arch.intro')) ?></p><p><?= t('arch.lockss_url', e(abs_url(url('gateway', 'lockss')))) ?><br><?= t('arch.clockss_url', e(abs_url(url('gateway', 'clockss')))) ?></p><h3 class="be_group"><?= e(t('arch.pn')) ?></h3><p class="muted"><?= e(t('arch.pn_text')) ?> <?= e(t('arch.pn_missing')) ?></p><?php endif; ?>

<?php if ($area === 'website' && $tab === 'announcements'): ?><p><a class="be_btn be_btn_primary" href="<?= e(url('manageAnnouncements')) ?>"><?= e(t('menu.announcements')) ?></a></p><?php endif; ?>
<?php if ($fields): ?>
<form class="cmp_form be_form" method="post" action="<?= e($post) ?>"<?= $multipart ? ' enctype="multipart/form-data"' : '' ?>><?= csrf_field() ?><div class="fields">
<?php foreach ($fields as $f): [$k, $type] = $f; $o = $f[2] ?? []; $val = $values[$k] ?? ''; ?>
<?php if (!empty($o['group'])): ?><h3 class="be_group"><?= e(t($o['group'])) ?></h3><?php endif; ?>
<?php switch ($type):
case 'text': case 'email': case 'number': ?><?= field($lbl($k), $k, $val, ['type' => $type, 'maxlength' => 255, 'required' => !empty($o['required']), 'error' => $er($k), 'help' => $help($k)]) ?><?php break;
case 'textarea': case 'lines': ?><?= field($lbl($k), $k, $val, ['type' => 'textarea', 'rows' => $type === 'lines' ? 7 : 5, 'error' => $er($k), 'help' => $help($k)]) ?><?php break;
case 'html': ?><?= field($lbl($k), $k, $val, ['type' => 'textarea', 'rows' => 9, 'error' => $er($k), 'help' => $help($k) ?: t('set.html_help')]) ?><?php break;
case 'checkbox': ?><?= checkbox($k, '<strong>' . e($lbl($k)) . '</strong>' . ($help($k) ? ' <span class="muted">&mdash; ' . e($help($k)) . '</span>' : ''), $val === '1') ?><?php break;
case 'select': ?><?= field($lbl($k), $k, $val, ['type' => 'select', 'options' => array_map(fn($x) => !empty($o['translate']) ? t($x) : $x, $o['options']), 'error' => $er($k)]) ?><?php break;
case 'color': ?><div class="color"><label><span class="label"><?= e($lbl($k)) ?></span><input type="color" name="<?= e($k) ?>" value="<?= e(preg_match('/^#[0-9a-f]{6}$/i', $val) ? $val : '#d79f24') ?>" style="width:5rem;height:2.4rem;padding:0"> <span class="muted"><?= e(t('set.themeColor_help')) ?></span></label><?= $er($k) ? '<span class="field_error">' . e($er($k)) . '</span>' : '' ?></div><?php break;
case 'image': ?><div class="image"><label><span class="label"><?= e($lbl($k)) ?></span></label><?php if ($val): ?><p><img src="<?= e(public_url($val)) ?>" alt="" style="max-height:80px;max-width:360px;background:#eee"></p><?php endif; ?><input type="file" name="<?= e($k) ?>" accept="image/png,image/jpeg,image/gif"><?= $val ? checkbox($k . '_remove', e(t('set.remove_file')), false) : '' ?><?= $er($k) ? '<span class="field_error">' . e($er($k)) . '</span>' : '' ?></div><?php break;
case 'css': ?><div class="css"><label><span class="label"><?= e($lbl($k)) ?></span></label><?php if ($val): ?><p class="muted"><?= e(t('set.css_current')) ?>: <a href="<?= e(public_url($val)) ?>" target="_blank"><?= e($val) ?></a></p><?php endif; ?><input type="file" name="<?= e($k) ?>" accept=".css,text/css"><?= $val ? checkbox($k . '_remove', e(t('set.remove_file')), false) : '' ?><?= $er($k) ? '<span class="field_error">' . e($er($k)) . '</span>' : '' ?></div><?php break;
endswitch; ?>
<?php endforeach; ?></div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>
<?php endif; ?>

<?php if ($area === 'website' && $tab === 'appearanceSetup'): ?>
<hr id="sidebar"><h2><?= e(t('set.sidebar')) ?></h2><p class="muted"><?= e(t('set.sidebar_help')) ?></p>
<div class="be_two"><div><h3><?= e(t('set.sidebar_on')) ?></h3><ul class="be_list"><?php $sn = count($sidebarOn); foreach ($sidebarOn as $i => [$key, $label]): ?><li><span><?= e($label) ?></span><form class="be_inline" method="post"><?= csrf_field() ?>
<button class="be_btn be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['up', rawurlencode($key)])) ?>"<?= $i === 0 ? ' disabled' : '' ?>>&uarr;</button><button class="be_btn be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['down', rawurlencode($key)])) ?>"<?= $i === $sn - 1 ? ' disabled' : '' ?>>&darr;</button>
<button class="be_btn be_btn_danger be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['remove', rawurlencode($key)])) ?>"><?= e(t('part.remove')) ?></button></form></li><?php endforeach; ?></ul></div>
<div><h3><?= e(t('set.sidebar_off')) ?></h3><ul class="be_list"><?php foreach ($sidebarOff as [$key, $label]): ?><li><span><?= e($label) ?></span><form class="be_inline" method="post" action="<?= e(url('settings', 'sidebar', ['add', rawurlencode($key)])) ?>"><?= csrf_field() ?><button class="be_btn be_small" type="submit"><?= e(t('set.add')) ?></button></form></li><?php endforeach; ?><?php if (!$sidebarOff): ?><li class="muted"><?= e(t('set.none')) ?></li><?php endif; ?></ul></div></div>
<?php elseif ($area === 'workflow' && $tab === 'templates'): ?>
<hr><h2><?= e(t('set.email_templates')) ?></h2><table class="be_table"><thead><tr><th><?= e(t('set.email_key')) ?></th><th><?= e(t('wf.subject')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($templates as $tp): ?><tr class="<?= $tp['enabled'] ? '' : 'be_muted_row' ?>"><td><code><?= e($tp['email_key']) ?></code><?= $tp['is_custom'] ? ' <span class="be_chip">' . e(t('set.edited')) . '</span>' : '' ?><?= $tp['enabled'] ? '' : ' <span class="be_chip">' . e(t('set.disabled')) . '</span>' ?></td><td><?= e($tp['subject']) ?></td>
<td class="be_actions"><form class="be_inline" method="post"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'email', [$tp['id']])) ?>"><?= e(t('wizard.edit')) ?></a><?php if ($tp['is_custom']): ?><button class="be_btn" type="submit" formaction="<?= e(url('content', 'emailReset', [$tp['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_reset')) ?>')"><?= e(t('set.reset')) ?></button><?php endif; ?></form></td></tr><?php endforeach; ?></tbody></table>
<?php endif; ?>
<?php endif; ?>
</div></div>
