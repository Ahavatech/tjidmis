<?php use App\Controllers\Backend\SettingsController as SC;
$areas = ['journal' => 'menu.settings_journal', 'website' => 'menu.settings_website', 'workflow' => 'menu.settings_workflow', 'distribution' => 'menu.settings_distribution'];
$tabs = SC::tabsFor($area); $er = fn($k) => $errors[$k] ?? ''; $post = url('settings', 'save', [$area, $tab]);
$lbl = fn($k) => t('set.' . $k); $help = function ($k) { $h = t('set.' . $k . '_help'); return $h === 'set.' . $k . '_help' ? '' : $h; };
$hasForm = (bool)$fields; $multipart = (bool)array_filter($fields, fn($f) => in_array($f[1], ['image', 'css'], true)); ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.settings')) ?></h1></div>
<ul class="be_tabs"><?php foreach ($areas as $a => $l): ?><li class="<?= $a === $area ? 'active' : '' ?>"><a href="<?= e(url('settings', 'index', [$a])) ?>"><?= e(t($l)) ?></a></li><?php endforeach; ?></ul>
<div class="be_card">
<ul class="be_subtabs"><?php foreach ($tabs as $tb): ?><li class="<?= $tb === $tab ? 'active' : '' ?>"><a href="<?= e(url('settings', 'index', [$area, $tb])) ?>"><?= e(t('set.tab_' . $tb)) ?></a></li><?php endforeach; ?></ul>
<?= $errors ? errors_summary(array_values($errors)) : '' ?>

<?php if ($tab === 'sections'): ?>
<p><a class="be_btn be_btn_primary" href="<?= e(url('content', 'section')) ?>"><?= e(t('set.add_section')) ?></a></p>
<table class="be_table"><thead><tr><th><?= e(t('set.title')) ?></th><th><?= e(t('set.abbrev')) ?></th><th><?= e(t('iss.articles')) ?></th><th></th></tr></thead><tbody>
<?php $n = count($sections); foreach ($sections as $i => $s): ?><tr><td><?= e($s['title']) ?></td><td><?= e($s['abbrev']) ?></td><td><?= (int)$s['used'] ?></td><td class="be_actions"><form method="post" class="be_inline"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'section', [$s['id']])) ?>"><?= e(t('wizard.edit')) ?></a>
<button class="be_btn" type="submit" formaction="<?= e(url('content', 'sectionMove', [$s['id'], 'up'])) ?>"<?= $i === 0 ? ' disabled' : '' ?>>&uarr;</button><button class="be_btn" type="submit" formaction="<?= e(url('content', 'sectionMove', [$s['id'], 'down'])) ?>"<?= $i === $n - 1 ? ' disabled' : '' ?>>&darr;</button>
<button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'sectionDelete', [$s['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_delete')) ?>')"><?= e(t('wizard.delete')) ?></button></form></td></tr><?php endforeach; ?></tbody></table>

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
<p><a class="be_btn be_btn_primary" href="<?= e(url('content', 'page')) ?>"><?= e(t('set.add_page')) ?></a></p>
<table class="be_table"><thead><tr><th><?= e(t('set.title')) ?></th><th><?= e(t('set.path')) ?></th><th></th></tr></thead><tbody><?php foreach ($pages as $pg): ?><tr><td><?= e($pg['title']) ?></td><td><a href="<?= e(url($pg['path'])) ?>" target="_blank">/<?= e($pg['path']) ?></a></td><td class="be_actions"><form method="post" class="be_inline"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'page', [$pg['id']])) ?>"><?= e(t('wizard.edit')) ?></a>
<button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'pageDelete', [$pg['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_delete')) ?>')"><?= e(t('wizard.delete')) ?></button></form></td></tr><?php endforeach; ?></tbody></table>

<?php elseif ($tab === 'blocks'): ?>
<p><a class="be_btn be_btn_primary" href="<?= e(url('content', 'block')) ?>"><?= e(t('set.add_block')) ?></a></p>
<table class="be_table"><thead><tr><th><?= e(t('set.title')) ?></th><th><?= e(t('set.block_name')) ?></th><th></th></tr></thead><tbody><?php foreach ($blocks as $b): ?><tr><td><?= e($b['title']) ?></td><td><?= e($b['name']) ?></td><td class="be_actions"><form method="post" class="be_inline"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'block', [$b['id']])) ?>"><?= e(t('wizard.edit')) ?></a>
<button class="be_btn be_btn_danger" type="submit" formaction="<?= e(url('content', 'blockDelete', [$b['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_delete')) ?>')"><?= e(t('wizard.delete')) ?></button></form></td></tr><?php endforeach; ?></tbody></table>
<p class="muted"><?= e(t('set.blocks_help')) ?></p>

<?php elseif ($tab === 'plugins'): ?>
<form class="cmp_form be_form" method="post" action="<?= e($post) ?>"><?= csrf_field() ?><p class="muted"><?= e(t('set.plugins_help')) ?></p><div class="fields">
<?php foreach (SC::FEATURES as $k): ?><?= checkbox($k, '<strong>' . e(t('set.feat_' . $k)) . '</strong> <span class="muted">&mdash; ' . e(t('set.feat_' . $k . '_help')) . '</span>', !empty($features[$k])) ?><?php endforeach; ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form>

<?php elseif ($tab === 'access'): ?><p><?= e(t('set.access_text')) ?></p>

<?php else: ?>
<?php if ($area === 'website' && $tab === 'announcements'): ?><p><a class="be_btn be_btn_primary" href="<?= e(url('manageAnnouncements')) ?>"><?= e(t('menu.announcements')) ?></a></p><?php endif; ?>
<form class="cmp_form be_form" method="post" action="<?= e($post) ?>"<?= $multipart ? ' enctype="multipart/form-data"' : '' ?>><?= csrf_field() ?><div class="fields">
<?php foreach ($fields as $f): [$k, $type] = $f; $o = $f[2] ?? []; $val = $values[$k] ?? ''; ?>
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

<?php if ($area === 'website' && $tab === 'appearance'): ?>
<hr id="sidebar"><h2><?= e(t('set.sidebar')) ?></h2><p class="muted"><?= e(t('set.sidebar_help')) ?></p>
<div class="be_two"><div><h3><?= e(t('set.sidebar_on')) ?></h3><ul class="be_list"><?php $sn = count($sidebarOn); foreach ($sidebarOn as $i => [$key, $label]): ?><li><span><?= e($label) ?></span><form class="be_inline" method="post"><?= csrf_field() ?>
<button class="be_btn be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['up', rawurlencode($key)])) ?>"<?= $i === 0 ? ' disabled' : '' ?>>&uarr;</button><button class="be_btn be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['down', rawurlencode($key)])) ?>"<?= $i === $sn - 1 ? ' disabled' : '' ?>>&darr;</button>
<button class="be_btn be_btn_danger be_small" type="submit" formaction="<?= e(url('settings', 'sidebar', ['remove', rawurlencode($key)])) ?>"><?= e(t('part.remove')) ?></button></form></li><?php endforeach; ?></ul></div>
<div><h3><?= e(t('set.sidebar_off')) ?></h3><ul class="be_list"><?php foreach ($sidebarOff as [$key, $label]): ?><li><span><?= e($label) ?></span><form class="be_inline" method="post" action="<?= e(url('settings', 'sidebar', ['add', rawurlencode($key)])) ?>"><?= csrf_field() ?><button class="be_btn be_small" type="submit"><?= e(t('set.add')) ?></button></form></li><?php endforeach; ?><?php if (!$sidebarOff): ?><li class="muted"><?= e(t('set.none')) ?></li><?php endif; ?></ul></div></div>
<?php elseif ($area === 'workflow' && $tab === 'emails'): ?>
<hr><h2><?= e(t('set.email_templates')) ?></h2><table class="be_table"><thead><tr><th><?= e(t('set.email_key')) ?></th><th><?= e(t('wf.subject')) ?></th><th></th></tr></thead><tbody>
<?php foreach ($templates as $tp): ?><tr class="<?= $tp['enabled'] ? '' : 'be_muted_row' ?>"><td><code><?= e($tp['email_key']) ?></code><?= $tp['is_custom'] ? ' <span class="be_chip">' . e(t('set.edited')) . '</span>' : '' ?><?= $tp['enabled'] ? '' : ' <span class="be_chip">' . e(t('set.disabled')) . '</span>' ?></td><td><?= e($tp['subject']) ?></td>
<td class="be_actions"><form class="be_inline" method="post"><?= csrf_field() ?><a class="be_btn" href="<?= e(url('content', 'email', [$tp['id']])) ?>"><?= e(t('wizard.edit')) ?></a><?php if ($tp['is_custom']): ?><button class="be_btn" type="submit" formaction="<?= e(url('content', 'emailReset', [$tp['id']])) ?>" onclick="return confirm('<?= e(t('set.confirm_reset')) ?>')"><?= e(t('set.reset')) ?></button><?php endif; ?></form></td></tr><?php endforeach; ?></tbody></table>
<?php endif; ?>
<?php endif; ?>
</div></div>
