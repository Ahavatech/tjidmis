<div class="page page_lost_password"><?= breadcrumb([[t('lost.title'), null]]) ?>

	<h1>
		<?= e(t('lost.title')) ?>
	</h1>

<?= errors_summary($errors) ?>
<p><?= e(t('reset.instructions')) ?></p>
<form class="cmp_form" method="post" action="<?= e(url('login', 'updateResetPassword')) ?>"><?= csrf_field() ?>
  <input type="hidden" name="username" value="<?= e($username) ?>"><input type="hidden" name="hash" value="<?= e($hash) ?>">
  <div class="fields"><?= field(t('reset.new'), 'password', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $errors['password'] ?? '']) ?>
  <?= field(t('reset.new2'), 'password2', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $errors['password2'] ?? '']) ?>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('reset.submit')) ?></button></div></div></form></div>
