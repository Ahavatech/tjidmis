<div class="page page_login"><?= breadcrumb([[t('nav.login'), null]]) ?>

	<h1>
		<?= e(t('nav.login')) ?>
	</h1>
	
<p><?= e(t('form.required_note')) ?> <abbr class="required" title="<?= e(t('form.required_title')) ?>">*</abbr></p>
<form class="cmp_form cmp_form login" id="login" method="post" action="<?= e(url('login', 'signIn')) ?>"><?= csrf_field() ?>
<?php if ($error): ?>
			<div class="pkp_form_error">
				<?= e($error) ?>
			</div>
		<?php endif; ?>
  <input type="hidden" name="source" value="<?= e($source) ?>">
  <fieldset class="fields"><legend class="pkp_screen_reader"><?= e(t('nav.login')) ?></legend>
    <?= field(t('login.username'), 'username', $username, ['required' => true, 'maxlength' => 32]) ?>
    <div class="password"><label><span class="label"><?= e(t('login.password')) ?> <span class="required" aria-hidden="true">*</span><span class="pkp_screen_reader"> <?= e(t('form.required')) ?></span></span>
      <input type="password" name="password" id="password" value="" password="true" maxlength="32" required aria-required="true"> <a href="<?= e(url('login', 'lostPassword')) ?>"><?= e(t('login.forgot')) ?></a></label></div>
    <div class="remember checkbox"><label><input type="checkbox" name="remember" id="remember" value="1" checked="$remember"> <span class="label"><?= e(t('login.remember')) ?></span></label></div>
    <div class="buttons"><button class="submit" type="submit"><?= e(t('nav.login')) ?></button> <a href="<?= e(url('user', 'register', [], ['source' => $source])) ?>" class="register"><?= e(t('nav.register')) ?></a></div>
  </fieldset></form>
</div>
