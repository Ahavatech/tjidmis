<?php use App\Core\Settings; ?>
<div class="page_index_journal">
  <section class="homepage_about"><a id="homepageAbout"></a>
    <h2><?= e(t('home.about')) ?></h2>
    <?= Settings::get('description', '') ?>
  </section>
  <section class="current_issue"><a id="homepageIssue"></a>
    <h2><?= e(t('home.current_issue')) ?></h2>
    <div class="current_issue_title"><?= $issue ? e('Vol. ' . $issue['volume'] . ' No. ' . $issue['number'] . ' (' . $issue['year'] . ')') : e(t('home.no_issue')) ?></div>
    <p class="stub_note"><?= e(t('home.stub')) ?></p>
  </section>
</div>
