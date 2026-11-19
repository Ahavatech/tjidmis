<?php $er = fn($k) => $errors[$k] ?? ''; $v = fn($k) => $i[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e($isEdit ? t('iss.edit') : t('iss.create')) ?></h1></div>
<?php if ($isEdit): ?><ul class="be_tabs"><li><a href="<?= e(url('manageIssues', 'edit', [$i['id']])) ?>"><?= e(t('iss.toc')) ?></a></li><li class="active"><a href="<?= e(url('manageIssues', 'form', [$i['id']])) ?>"><?= e(t('iss.data')) ?></a></li></ul><?php endif; ?>
<div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" enctype="multipart/form-data" action="<?= e($isEdit ? url('manageIssues', 'form', [$i['id']]) : url('manageIssues', 'form')) ?>"><?= csrf_field() ?><div class="fields">
<?= field(t('iss.volume'), 'volume', $v('volume'), ['maxlength' => 6, 'error' => $er('volume')]) ?><?= field(t('iss.number'), 'number', $v('number'), ['maxlength' => 20, 'error' => $er('number')]) ?><?= field(t('iss.year'), 'year', $v('year'), ['maxlength' => 4, 'error' => $er('year')]) ?><?= field(t('iss.title'), 'title', $v('title'), ['maxlength' => 255]) ?>
<fieldset class="be_fs"><legend><?= e(t('iss.identification')) ?></legend><?php foreach (['volume', 'number', 'year', 'title'] as $k): ?><?= checkbox('show_' . $k, e(t('iss.show_' . $k)), !empty($i['show_' . $k])) ?><?php endforeach; ?></fieldset>
<?= field(t('iss.description'), 'description', strip_tags(str_replace(['</p>', '<br>'], "\n\n", (string)$v('description'))), ['type' => 'textarea', 'rows' => 5]) ?>
<?php if ($v('cover_image')): ?><p><img src="<?= e(public_url($v('cover_image'))) ?>" alt="" height="90"></p><?php endif; ?><div class="cover"><label><span class="label"><?= e(t('pub.cover')) ?></span><input type="file" name="cover" accept="image/png,image/jpeg,image/gif"></label><?= $er('cover') ? '<span class="field_error">' . e($er('cover')) . '</span>' : '' ?></div>
<?= field(t('pub.url_path'), 'url_path', $v('url_path'), ['maxlength' => 64, 'error' => $er('url_path')]) ?></div>
<div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e(url('manageIssues')) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
