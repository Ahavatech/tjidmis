<?php use App\Core\Countries; $er = fn($k) => $errors[$k] ?? ''; ?>
<div class="page page_register"><?= breadcrumb([[t('nav.register'), null]]) ?><h1><?= e(t('nav.register')) ?></h1>
<p><?= e(t('form.required_note')) ?> <abbr class="required" title="<?= e(t('form.required_title')) ?>">*</abbr></p>
<?= errors_summary($errors) ?>
<form class="cmp_form register" id="register" method="post" action="<?= e(url('user', 'register')) ?>"><?= csrf_field() ?>
  <fieldset class="identity"><legend><?= e(t('reg.profile')) ?></legend><div class="fields">
    <?= field(t('reg.given'), 'givenName', $v['givenName'] ?? '', ['required' => true, 'maxlength' => 255, 'autocomplete' => 'given-name', 'class' => 'given_name', 'error' => $er('givenName')]) ?>
    <?= field(t('reg.family'), 'familyName', $v['familyName'] ?? '', ['maxlength' => 255, 'autocomplete' => 'family-name', 'class' => 'family_name']) ?>
    <?= field(t('reg.affiliation'), 'affiliation', $v['affiliation'] ?? '', ['required' => true, 'error' => $er('affiliation')]) ?>
    <?= field(t('reg.country'), 'country', $v['country'] ?? '', ['type' => 'select', 'options' => Countries::all(), 'required' => true, 'error' => $er('country')]) ?>
  </div></fieldset>
  <fieldset class="login"><legend><?= e(t('reg.login')) ?></legend><div class="fields">
    <?= field(t('reg.email'), 'email', $v['email'] ?? '', ['type' => 'email', 'required' => true, 'maxlength' => 90, 'autocomplete' => 'email', 'error' => $er('email')]) ?>
    <?= field(t('reg.username'), 'username', $v['username'] ?? '', ['required' => true, 'maxlength' => 32, 'autocomplete' => 'username', 'error' => $er('username')]) ?>
    <?= field(t('reg.password'), 'password', '', ['type' => 'password', 'required' => true, 'maxlength' => 32, 'class' => 'password', 'error' => $er('password')]) ?>
    <?= field(t('reg.password2'), 'password2', '', ['type' => 'password', 'required' => true, 'maxlength' => 32, 'class' => 'password', 'error' => $er('password2')]) ?>
  </div></fieldset>
  <fieldset class="consent"><legend class="pkp_screen_reader"><?= e(t('reg.privacy_options')) ?></legend>
    <div class="fields"><div class="optin optin-privacy"><label><input type="checkbox" name="privacyConsent" value="1"<?= !empty($v['privacyConsent']) ? ' checked' : '' ?>> <?= e(t('reg.consent_pre')) ?> <a href="<?= e(url('about', 'privacy')) ?>" target="_blank"><?= e(t('reg.consent_link')) ?></a>.</label><?= $er('privacyConsent') ? '<span class="field_error" role="alert">' . e($er('privacyConsent')) . '</span>' : '' ?></div></div>
    <div class="fields"><div class="optin optin-email"><label><input type="checkbox" name="emailConsent" value="1"<?= !empty($v['emailConsent']) ? ' checked' : '' ?>> <?= e(t('reg.email_consent')) ?></label></div></div>
  </fieldset>
  <fieldset class="reviewer"><div class="fields">
    <div id="reviewerOptinGroup" class="optin"><label><input type="checkbox" name="reviewerGroup[<?= (int)$reviewerGroupId ?>]" value="1"<?= !empty($v['reviewerGroup']) ? ' checked' : '' ?>> <?= e(t('reg.reviewer_optin')) ?></label></div>
    <div id="reviewerInterests" class="reviewer_interests"><label><span class="label"><?= e(t('reg.interests')) ?></span><input type="text" name="interests" id="interests" value="<?= e($v['interests'] ?? '') ?>"></label></div>
  </div></fieldset>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('nav.register')) ?></button> <a href="<?= e(url('login')) ?>" class="login"><?= e(t('nav.login')) ?></a></div>
</form></div>
