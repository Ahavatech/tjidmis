<div class="page page_lost_password"><?= breadcrumb([[t('lost.title'), null]]) ?>

	<h1>
		<?= e(t('lost.title')) ?>
	</h1>
	
<p><?= e(t('lost.intro')) ?></p>
<form class="cmp_form lost_password" id="lostPasswordForm" action="<?= e(url('login', 'requestResetPassword')) ?>" method="post"><?= csrf_field() ?>
		<?php if ($error): ?>
			<div class="pkp_form_error">
				<?= e($error) ?>
			</div>
		<?php endif; ?>
  <div class="fields"><?= field(t('lost.email'), 'email', '', ['type' => 'email', 'required' => true]) ?>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('lost.title')) ?></button> <a href="<?= e(url('user', 'register')) ?>" class="register"><?= e(t('nav.register')) ?></a></div></div>
</form></div>
