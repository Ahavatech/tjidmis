<div class="be_page"><div class="be_pagehead"><h1><?= e(t('menu.users')) ?></h1></div>
<ul class="be_tabs"><li><a href="<?= e(url('users')) ?>"><?= e(t('users.users_tab')) ?></a></li><li><a href="<?= e(url('users', 'roles')) ?>"><?= e(t('users.roles')) ?></a></li><li class="active"><a href="<?= e(url('users', 'access')) ?>"><?= e(t('users.access')) ?></a></li></ul>
<div class="be_card"><form class="cmp_form be_form" method="post" action="<?= e(url('users', 'access')) ?>"><?= csrf_field() ?><div class="fields">
<h3 class="be_group"><?= e(t('users.sa_site')) ?></h3><?= checkbox('restrictSiteAccess', e(t('users.sa_site_opt')), $restrictSite) ?>
<h3 class="be_group"><?= e(t('users.sa_content')) ?></h3><?= checkbox('restrictArticleAccess', e(t('users.sa_content_opt')), $restrictArticle) ?>
<h3 class="be_group"><?= e(t('users.sa_reg')) ?></h3>
<div class="optin"><label><input type="radio" name="disableUserReg" value="0"<?= $disableReg ? '' : ' checked' ?>> <?= e(t('users.sa_reg_open')) ?></label></div>
<div class="optin"><label><input type="radio" name="disableUserReg" value="1"<?= $disableReg ? ' checked' : '' ?>> <?= e(t('users.sa_reg_closed')) ?></label></div>
</div><div class="buttons"><button class="submit" type="submit"><?= e(t('wizard.save')) ?></button></div></form></div></div>
