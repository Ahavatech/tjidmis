<?php use App\Core\Countries; $er = fn($k) => $errors[$k] ?? ''; ?>
<div class="page page_register"><?= breadcrumb([[t('nav.register'), null]]) ?><h1><?= e(t('nav.register')) ?></h1>
<p><?= e(t('form.required_note')) ?> <abbr class="required" title="<?= e(t('form.required')) ?>">*</abbr></p>
<?= errors_summary($errors) ?>
<form class="cmp_form register" id="register" method="post" action="<?= e(url('user', 'register')) ?>"><?= csrf_field() ?>
  <fieldset class="identity"><legend><?= e(t('reg.profile')) ?></legend><div class="fields">
    <?= field(t('reg.given'), 'givenName', $v['givenName'] ?? '', ['required' => true, 'maxlength' => 120, 'autocomplete' => 'given-name', 'class' => 'given_name', 'error' => $er('givenName')]) ?>
    <?= field(t('reg.family'), 'familyName', $v['familyName'] ?? '', ['maxlength' => 120, 'autocomplete' => 'family-name', 'class' => 'family_name']) ?>
    <?= field(t('reg.affiliation'), 'affiliation', $v['affiliation'] ?? '', ['required' => true, 'maxlength' => 255, 'error' => $er('affiliation')]) ?>
    <?= field(t('reg.country'), 'country', $v['country'] ?? '', ['type' => 'select', 'options' => Countries::all(), 'required' => true, 'error' => $er('country')]) ?>
  </div></fieldset>
  <fieldset class="login"><legend><?= e(t('reg.login')) ?></legend><div class="fields">
    <?= field(t('reg.email'), 'email', $v['email'] ?? '', ['type' => 'email', 'required' => true, 'maxlength' => 190, 'autocomplete' => 'email', 'error' => $er('email')]) ?>
    <?= field(t('reg.username'), 'username', $v['username'] ?? '', ['required' => true, 'maxlength' => 32, 'autocomplete' => 'username', 'help' => t('reg.username_help'), 'error' => $er('username')]) ?>
    <?= field(t('reg.password'), 'password', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'class' => 'password', 'error' => $er('password')]) ?>
    <?= field(t('reg.password2'), 'password2', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'class' => 'password', 'error' => $er('password2')]) ?>
  </div></fieldset>
  <fieldset class="consent"><legend class="pkp_screen_reader"><?= e(t('reg.privacy_options')) ?></legend><div class="fields">
    <?= checkbox('privacyConsent', e(t('reg.consent_pre')) . ' <a href="' . e(url('about', 'privacy')) . '" target="_blank" rel="noopener">' . e(t('reg.consent_link')) . '</a>.', !empty($v['privacyConsent']), '1', $er('privacyConsent')) ?>
    <?= checkbox('emailConsent', e(t('reg.email_consent')), !empty($v['emailConsent'])) ?>
  </div></fieldset>
  <fieldset class="reviewer"><div class="fields">
    <?= checkbox('reviewerGroup', e(t('reg.reviewer_optin')), !empty($v['reviewerGroup'])) ?>
    <div id="reviewerInterests" class="reviewer_interests"><?= field(t('reg.interests'), 'interests', $v['interests'] ?? '', ['maxlength' => 255]) ?></div>
  </div></fieldset>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('nav.register')) ?></button> <a href="<?= e(url('login')) ?>" class="login"><?= e(t('nav.login')) ?></a></div>
</form></div>
