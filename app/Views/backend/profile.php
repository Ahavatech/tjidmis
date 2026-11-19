<?php use App\Core\Countries; $er = fn($k) => $errors[$k] ?? '';
$tabs = ['identity' => 'profile.tab_identity', 'contact' => 'profile.tab_contact', 'roles' => 'profile.tab_roles', 'public' => 'profile.tab_public', 'password' => 'profile.tab_password', 'notifications' => 'profile.tab_notifications', 'apiKey' => 'profile.tab_api']; ?>
<div class="be_page"><div class="be_pagehead"><h1><?= e(t('profile.title')) ?></h1></div>
<ul class="be_tabs"><?php foreach ($tabs as $k => $l): ?><li class="<?= $k === $tab ? 'active' : '' ?>"><a href="<?= e(url('user', 'profile', [$k])) ?>"><?= e(t($l)) ?></a></li><?php endforeach; ?></ul>
<div class="be_card"><?= errors_summary($errors) ?>
<?php if ($tab === 'apiKey'): ?><p class="muted"><?= e(t('profile.api_stub')) ?></p><?php else: ?>
<form class="cmp_form be_form" method="post" action="<?= e(url('user', 'profile', [$tab])) ?>"<?= $tab === 'public' ? ' enctype="multipart/form-data"' : '' ?>><?= csrf_field() ?>
<div class="fields">
<?php if ($tab === 'identity'): ?>
  <?= field(t('reg.given'), 'given_name', $u['given_name'], ['required' => true, 'maxlength' => 120, 'error' => $er('given_name')]) ?>
  <?= field(t('reg.family'), 'family_name', $u['family_name'], ['maxlength' => 120]) ?>
  <?= field(t('reg.affiliation'), 'affiliation', $u['affiliation'], ['required' => true, 'maxlength' => 255, 'error' => $er('affiliation')]) ?>
  <?= field(t('reg.country'), 'country', $u['country'], ['type' => 'select', 'options' => Countries::all(), 'required' => true, 'error' => $er('country')]) ?>
  <p class="muted"><?= e(t('login.username')) ?>: <strong><?= e($u['username']) ?></strong> (<?= e(t('profile.username_fixed')) ?>)</p>
<?php elseif ($tab === 'contact'): ?>
  <?= field(t('reg.email'), 'email', $u['email'], ['type' => 'email', 'required' => true, 'maxlength' => 190, 'error' => $er('email')]) ?>
  <?= field(t('profile.phone'), 'phone', $u['phone'] ?? '', ['maxlength' => 40]) ?>
  <?= field(t('profile.address'), 'mailing_address', $u['mailing_address'] ?? '', ['type' => 'textarea', 'rows' => 4]) ?>
<?php elseif ($tab === 'roles'): ?>
  <p><strong><?= e(t('profile.your_roles')) ?></strong> <?= e(implode(', ', array_map(fn($r) => t('role.' . $r), $roles)) ?: '-') ?></p>
  <?= checkbox('reviewer', e(t('reg.reviewer_optin')), $isReviewer) ?>
  <?= field(t('reg.interests'), 'reviewing_interests', $u['reviewing_interests'] ?? '', ['maxlength' => 255]) ?>
<?php elseif ($tab === 'public'): ?>
  <?php if ($u['photo']): ?><p><img class="profile_photo" src="<?= e(public_url($u['photo'])) ?>" alt="" width="96"></p><?php endif; ?>
  <div class="photo"><label><span class="label"><?= e(t('profile.photo')) ?></span><input type="file" name="photo" accept="image/png,image/jpeg,image/gif"></label><?= $er('photo') ? '<span class="field_error">' . e($er('photo')) . '</span>' : '' ?></div>
  <?= field(t('profile.url'), 'url', $u['url'] ?? '', ['maxlength' => 255, 'error' => $er('url')]) ?>
  <?= field(t('profile.orcid'), 'orcid', $u['orcid'] ?? '', ['maxlength' => 60, 'error' => $er('orcid'), 'help' => t('profile.orcid_help')]) ?>
  <?= field(t('profile.bio'), 'bio', $u['bio'] ?? '', ['type' => 'textarea', 'rows' => 6]) ?>
<?php elseif ($tab === 'password'): ?>
  <?= field(t('profile.old_password'), 'oldPassword', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'current-password', 'error' => $er('oldPassword')]) ?>
  <?= field(t('reset.new'), 'password', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $er('password')]) ?>
  <?= field(t('reg.password2'), 'password2', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $er('password2')]) ?>
<?php elseif ($tab === 'notifications'): ?>
  <p><?= e(t('profile.notif_intro')) ?></p>
  <?php foreach (\App\Controllers\Backend\ProfileController::EVENTS as $ev): ?><?= checkbox($ev, e(t('profile.notif_' . $ev)), ($prefs['emailPref_' . $ev] ?? '1') === '1') ?><?php endforeach; ?>
<?php endif; ?>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('profile.save')) ?></button></div></form><?php endif; ?>
</div></div>
