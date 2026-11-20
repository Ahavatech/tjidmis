<?php use App\Core\Countries; $er = fn($k) => $errors[$k] ?? ''; $a = $a ?: []; $v = fn($k) => $a[$k] ?? ''; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e($isEdit ? t('wizard.edit_contributor') : t('wizard.add_contributor')) ?></h1></div>
<div class="be_card"><?= errors_summary($errors) ?>
<form class="cmp_form be_form" method="post" action="<?= e($action ?? ($isEdit ? url('submission', 'contributor', [$sub['id'], $a['id']]) : url('submission', 'contributor', [$sub['id']]))) ?>"><?= csrf_field() ?><div class="fields">
  <?= field(t('reg.given'), 'given_name', $v('given_name'), ['required' => true, 'maxlength' => 120, 'error' => $er('given_name')]) ?>
  <?= field(t('reg.family'), 'family_name', $v('family_name'), ['maxlength' => 120]) ?>
  <?= field(t('reg.email'), 'email', $v('email'), ['type' => 'email', 'required' => true, 'maxlength' => 190, 'error' => $er('email')]) ?>
  <?= field(t('reg.country'), 'country', $v('country'), ['type' => 'select', 'options' => Countries::all(), 'required' => true, 'error' => $er('country')]) ?>
  <?= field(t('reg.affiliation'), 'affiliation', $v('affiliation'), ['maxlength' => 255]) ?>
  <?= field(t('profile.orcid'), 'orcid', $v('orcid'), ['maxlength' => 60, 'error' => $er('orcid'), 'help' => t('profile.orcid_help')]) ?>
  <?= field(t('profile.url'), 'url', $v('url'), ['maxlength' => 255, 'error' => $er('url')]) ?>
  <?= field(t('profile.bio'), 'bio', strip_tags((string)$v('bio')), ['type' => 'textarea', 'rows' => 4]) ?>
  <?= field(t('author.ci'), 'competing_interests', strip_tags((string)$v('competing_interests')), ['type' => 'textarea', 'rows' => 3, 'required' => \App\Services\MetadataService::requireAuthorCI(), 'error' => $er('competing_interests'), 'help' => t('author.ci_desc')]) ?>
  <div class="role"><label><span class="label"><?= e(t('wizard.role')) ?></span><select name="role"><option value="author"<?= ($v('user_group') ?: 'author') === 'author' ? ' selected' : '' ?>><?= e(t('wizard.role_author')) ?></option><option value="translator"<?= $v('user_group') === 'translator' ? ' selected' : '' ?>><?= e(t('wizard.role_translator')) ?></option></select></label></div>
  <?= checkbox('primary', e(t('wizard.primary_contact')), !empty($isPrimary)) ?>
  <?= checkbox('include_in_browse', e(t('wizard.include_browse')), $isEdit ? !empty($a['include_in_browse']) : true) ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button><a href="<?= e($cancel ?? url('submission', 'wizard', [$sub['id'], 3])) ?>"><?= e(t('wizard.cancel')) ?></a></div></form></div></div>
