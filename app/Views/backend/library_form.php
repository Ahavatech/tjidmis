<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $f[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t(!empty($f['id']) ? 'lib.edit' : 'lib.add')) ?></h1></div><div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" enctype="multipart/form-data" action="<?= e(url('content', 'library', !empty($f['id']) ? [$f['id']] : [])) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('cat.name'), 'name', $v('name'), ['required' => true, 'maxlength' => 255, 'error' => $er('name')]) ?>
<?= field(t('lib.type'), 'file_type', $v('file_type'), ['type' => 'select', 'required' => true, 'options' => [1 => t('lib.type_1'), 2 => t('lib.type_2'), 3 => t('lib.type_3'), 4 => t('lib.type_4'), 5 => t('lib.type_5')], 'error' => $er('file_type')]) ?>
<div class="file"><label><span class="label"><?= e(t('lib.file')) ?><?= empty($f['id']) ? ' <span class="required" aria-hidden="true">*</span>' : '' ?></span></label><?php if (!empty($f['original_name'])): ?><p class="muted"><?= e($f['original_name']) ?></p><?php endif; ?><input type="file" name="file"><?= $er('file') ? '<span class="field_error">' . e($er('file')) . '</span>' : '' ?></div>
<?= checkbox('public_access', '<strong>' . e(t('lib.public')) . '</strong>', !empty($f['public_access'])) ?>
<?php if (!empty($f['id'])): ?><p class="muted"><?= t('lib.public_url', e(abs_url(url('libraryFiles', 'downloadPublic', [$f['id']])))) ?></p><?php endif; ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('settings', 'index', ['workflow', 'library'])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
