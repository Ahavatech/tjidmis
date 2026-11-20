<div class="page page_lost_password"><?= breadcrumb([[t('reset.title'), null]]) ?>

	<h1>
		<?= e(t('reset.title')) ?>
	</h1>
	
<?= errors_summary($errors) ?>
<form class="cmp_form" method="post" action="<?= e(url('login', 'resetPassword', [$token])) ?>"><?= csrf_field() ?>
  <div class="fields"><?= field(t('reset.new'), 'password', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $errors['password'] ?? '']) ?>
  <?= field(t('reg.password2'), 'password2', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password', 'error' => $errors['password2'] ?? '']) ?>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('reset.submit')) ?></button></div></div></form></div>
