<?php use App\Core\Settings; use App\Models\Catalog; $img = Settings::get('homepageImage', ''); ?>
<div class="page_index_journal">
  <?php if ($img): ?><div class="homepage_image">
	<img src="<?= e(public_url($img)) ?>">
</div><?php endif; ?>
  <section class="homepage_about"><a id="homepageAbout"></a>
    <h2><?= e(t('home.about')) ?></h2>
    <?= rich(Settings::get('description', '')) ?>
  </section>
  <?php if ($announcements): ?>
  <section class="cmp_announcements highlight_first"><a id="homepageAnnouncements"></a>
    <h2><?= e(t('nav.announcements')) ?></h2>
    <?php foreach ($announcements as $n): ?><article class="obj_announcement_summary">
      <h3><a href="<?= e(url('announcement', 'view', [$n['id']])) ?>"><?= e($n['title']) ?></a></h3>
      <div class="date"><?= e(fmt_date($n['date_posted'])) ?></div>
      <div class="summary"><?= $n['description_short'] ? '<p>' . e($n['description_short']) . '</p>' : '' ?><a class="read_more" href="<?= e(url('announcement', 'view', [$n['id']])) ?>"><?= e(t('home.read_more')) ?></a></div>
    </article><?php endforeach; ?>
  </section>
  <?php endif; ?>
  <?php if ($issue): ?>
  <section class="current_issue"><a id="homepageIssue"></a>
    <h2><?= e(t('home.current_issue')) ?></h2>
    <div class="current_issue_title"><?= e(Catalog::issueLabel($issue)) ?></div>
    <?= \App\Core\View::partial('partials/issue_toc', ['issue' => $issue, 'toc' => $toc, 'h' => 3]) ?>
    <a href="<?= e(url('issue', 'archive')) ?>" class="read_more"><?= e(t('home.all_issues')) ?></a>
  </section>
  <?php endif; ?>
  <?php if ($extra = Settings::get('additionalHomeContent', '')): ?><div class="additional_content">
    <?= rich($extra) ?>
  </div><?php endif; ?>
</div>
