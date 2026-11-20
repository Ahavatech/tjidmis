<?php
namespace App\Controllers\Public;
use App\Core\{View, DB, Settings};
use App\Models\Catalog;

/** /{journal}/catalog/category/{path}: the category page OJS 3.3 offers (articles in a category and its subcategories). */
class CatalogController {
    public function index(): void { $this->notFound(); }
    public function category(string $path = ''): void {
        $jid = (int)Settings::journal()['id'];
        $cat = DB::one('SELECT * FROM categories WHERE journal_id=? AND path=?', [$jid, $path]);
        if (!$cat) { $this->notFound(); return; }
        $parent = $cat['parent_id'] ? DB::one('SELECT * FROM categories WHERE id=?', [$cat['parent_id']]) : null;
        $subs = DB::all('SELECT * FROM categories WHERE journal_id=? AND parent_id=? ORDER BY seq, title', [$jid, $cat['id']]);
        $articles = Catalog::categoryArticles($jid, (int)$cat['id']);
        View::render('public/category', ['title' => $cat['title'], 'bodyClass' => 'catalog_category', 'cat' => $cat, 'parent' => $parent, 'subs' => $subs, 'articles' => $articles]);
    }
    private function notFound(): void {
        http_response_code(404);
        View::render('public/error', ['code' => 404, 'message' => t('error.404'), 'title' => t('error.404.title')]);
    }
}
