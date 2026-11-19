<?php
namespace App\Controllers\Public;
use App\Core\{View, Settings};
class InformationController {
    private function page(string $key, string $setting): void {
        View::render('public/page_simple', ['title' => t($key), 'bodyClass' => 'information', 'crumb' => t($key), 'html' => Settings::get($setting, ''), 'cls' => 'page_information', 'wrap' => 'description']);
    }
    public function readers(): void { $this->page('info.readers', 'readerInfo'); }
    public function authors(): void { $this->page('info.authors', 'authorInfo'); }
    public function librarians(): void { $this->page('info.librarians', 'librarianInfo'); }
}
