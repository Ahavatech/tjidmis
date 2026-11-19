<div class="page page_login"><?= breadcrumb([[t('nav.login'), null]]) ?><h1><?= e(t('nav.login')) ?></h1>
<p><?= e(t('form.required_note')) ?> <abbr class="required" title="<?= e(t('form.required_title')) ?>">*</abbr></p>
<?php if ($error): ?><div class="cmp_notification error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form class="cmp_form cmp_form login" id="login" method="post" action="<?= e(url('login', 'signIn')) ?>"><?= csrf_field() ?>
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <fieldset class="fields"><legend class="pkp_screen_reader"><?= e(t('nav.login')) ?></legend>
    <?= field(t('login.username'), 'username', $username, ['required' => true, 'maxlength' => 190, 'autocomplete' => 'username']) ?>
    <div class="password"><label><span class="label"><?= e(t('login.password')) ?> <span class="required" aria-hidden="true">*</span><span class="pkp_screen_reader"> <?= e(t('form.required')) ?></span></span>
      <input type="password" name="password" id="password" maxlength="72" required aria-required="true" autocomplete="current-password"> <a href="<?= e(url('login', 'lostPassword')) ?>"><?= e(t('login.forgot')) ?></a></label></div>
    <div class="remember checkbox"><label><input type="checkbox" name="remember" id="remember" value="1" checked="$remember"> <span class="label"><?= e(t('login.remember')) ?></span></label></div>
    <div class="buttons"><button class="submit" type="submit"><?= e(t('nav.login')) ?></button> <a href="<?= e(url('user', 'register', [], ['source' => $source])) ?>" class="register"><?= e(t('nav.register')) ?></a></div>
  </fieldset></form>
<?php if (config('demo_mode')): ?><div class="cmp_notification demo_note"><strong><?= e(t('login.demo')) ?></strong> admin / editor / sectioneditor / reviewer1 / reviewer2 / author / reader &mdash; <code>Demo@12345</code></div><?php endif; ?>
</div>
