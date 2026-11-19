<div class="page page_lost_password"><?= breadcrumb([[t('lost.title'), null]]) ?><h1><?= e(t('lost.title')) ?></h1>
<p><?= e(t('lost.intro')) ?></p>
<?php if ($error): ?><div class="cmp_notification error" role="alert"><?= e($error) ?></div><?php endif; ?>
<form class="cmp_form lost_password" id="lostPasswordForm" action="<?= e(url('login', 'requestResetPassword')) ?>" method="post"><?= csrf_field() ?>
  <div class="fields"><?= field(t('lost.email'), 'email', '', ['type' => 'email', 'required' => true, 'maxlength' => 190, 'autocomplete' => 'email']) ?>
  <div class="buttons"><button class="submit" type="submit"><?= e(t('lost.title')) ?></button> <a href="<?= e(url('user', 'register')) ?>" class="register"><?= e(t('nav.register')) ?></a></div></div>
</form></div>
