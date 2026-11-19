<?php use App\Core\Auth; if (Auth::impersonating()): $real = Auth::realUser(); $cur = Auth::user(); ?>
<div class="loginas_banner" role="status"><?= e(t('loginas.banner', trim($cur['given_name'] . ' ' . $cur['family_name']))) ?>
  <a href="<?= e(url('login', 'signOutAsUser')) ?>"><?= e(t('loginas.logout', trim($cur['given_name'] . ' ' . $cur['family_name']))) ?></a></div>
<?php endif; ?>
