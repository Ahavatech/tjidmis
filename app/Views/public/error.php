<div class="page page_error"><h1><?= e($code) ?></h1><p><?= e($message) ?></p>
<p><a class="cmp_button" href="<?= e(url('index')) ?>"><?= e(t('error.home')) ?></a><?php if (\App\Core\Auth::user()): ?> <a class="cmp_button" href="<?= e(url('submissions')) ?>"><?= e(t('nav.dashboard')) ?></a><?php endif; ?></p></div>
