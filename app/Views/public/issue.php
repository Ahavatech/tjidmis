<div class="page page_issue"><?= breadcrumb($crumbs) ?>
<?php if ($issue): ?><h1><?= e(\App\Models\Catalog::issueLabel($issue)) ?></h1><?= \App\Core\View::partial('partials/issue_toc', ['issue' => $issue, 'toc' => $toc, 'h' => 2]) ?>
<?php else: ?><h1><?= e(t('nav.current')) ?></h1><p><?= e(t('home.no_issue')) ?></p><?php endif; ?></div>
