<?php
namespace App\Controllers\Backend;
use App\Core\{View, Auth, DB, Settings, Request, Sanitizer, Upload};
use App\Services\ReviewFormService as RF;

/** Settings screens that manage rows: sections, menu items, static pages, custom blocks, e-mail templates. */
class ContentSettingsController {
    const RESERVED = ['index', 'about', 'issue', 'article', 'announcement', 'information', 'search', 'gateway', 'user', 'login', 'submissions', 'submission', 'workflow', 'notification', 'management', 'participant',
        'decision', 'review', 'reviewer', 'discussion', 'publication', 'manageIssues', 'settings', 'users', 'statistics', 'admin', 'libraryFiles', 'css', 'manageAnnouncements', 'content', 'catalog', 'oai', 'sitemap'];
    const NAV_TYPES = ['about', 'editorial_team', 'submissions', 'privacy', 'contact', 'current', 'archives', 'announcements', 'search', 'information_readers', 'information_authors', 'information_librarians', 'static_page', 'custom_url'];
    private function jid(): int { return (int)Settings::journal()['id']; }
    private function guard(): void { Auth::require(SettingsController::ROLES); }
    private function back(string $area, string $tab): never { redirect(url('settings', 'index', [$area, $tab])); }
    private function notFound(): never { WorkflowController::deny(404); }

    // ---------------------------------------------------------------- publisher library
    public function library(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $f = $id !== '' ? DB::one('SELECT * FROM library_files WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$f) $this->notFound();
        if (Request::isPost()) {
            $p = $_POST; $name = trim((string)($p['name'] ?? '')); $type = (int)($p['file_type'] ?? 0); $hasFile = !empty($_FILES['file']['name']);
            if ($name === '') $errors['name'] = t('lib.err_name');
            if ($type < 1 || $type > 5) $errors['file_type'] = t('lib.err_type');
            if (!$f && !$hasFile) $errors['file'] = t('lib.err_file');
            if (!$errors) {
                $up = null;
                if ($hasFile) { try { $up = Upload::store($_FILES['file'], 'library/tmp'); } catch (\RuntimeException $x) { $errors['file'] = $x->getMessage(); } }
                if (!$errors) {
                    $pub = empty($p['public_access']) ? 0 : 1;
                    if ($f) DB::exec('UPDATE library_files SET name=?, file_type=?, public_access=? WHERE id=?', [mb_substr($name, 0, 255), $type, $pub, $f['id']]);
                    else $fid = DB::insert('library_files', ['journal_id' => $jid, 'name' => mb_substr($name, 0, 255), 'original_name' => $up['original_name'], 'stored_name' => $up['stored_name'], 'file_type' => $type, 'public_access' => $pub, 'mime' => $up['mime'], 'size' => $up['size'], 'created_at' => date('Y-m-d H:i:s')]);
                    if ($up) {   // new upload (or replacement): move it into the file's own folder
                        $fid = (int)($f['id'] ?? $fid); $dir = Upload::baseDir() . '/library/' . $fid; if (!is_dir($dir)) mkdir($dir, 0775, true);
                        if ($f) { @unlink($dir . '/' . $f['stored_name']); DB::exec('UPDATE library_files SET original_name=?, stored_name=?, mime=?, size=? WHERE id=?', [$up['original_name'], $up['stored_name'], $up['mime'], $up['size'], $fid]); }
                        rename(Upload::baseDir() . '/' . $up['path'], $dir . '/' . $up['stored_name']);
                    }
                    flash(t('lib.saved')); $this->back('workflow', 'library');
                }
            }
            $f = array_merge($f ?: [], $p);
        }
        View::render('backend/library_form', ['title' => t(!empty($f['id']) ? 'lib.edit' : 'lib.add'), 'menu' => 'settings', 'area' => 'workflow', 'tab' => 'library', 'f' => $f ?: [], 'errors' => $errors], 'backend');
    }
    public function libraryDelete(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) {
            $f = DB::one('SELECT * FROM library_files WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]);
            if ($f) { @unlink(Upload::baseDir() . '/library/' . (int)$f['id'] . '/' . $f['stored_name']); DB::exec('DELETE FROM library_files WHERE id=?', [$f['id']]); flash(t('set.deleted')); }
        }
        $this->back('workflow', 'library');
    }
    public function libraryDownload(string $id = ''): void {
        $this->guard(); $f = DB::one('SELECT * FROM library_files WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]);
        if (!$f) $this->notFound();
        \App\Controllers\Public\LibraryController::send($f);
    }
    // ---------------------------------------------------------------- categories
    public function category(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $c = $id !== '' ? DB::one('SELECT * FROM categories WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$c) $this->notFound();
        if (Request::isPost()) {
            $p = $_POST; $title = trim((string)($p['title'] ?? '')); $path = trim((string)($p['path'] ?? '')); $parent = (int)($p['parent_id'] ?? 0);
            if ($title === '') $errors['title'] = t('cat.err_name');
            if (!preg_match('/^[A-Za-z0-9]+$/', $path)) $errors['path'] = t('cat.err_path');
            elseif (DB::val('SELECT 1 FROM categories WHERE journal_id=? AND path=? AND id<>?', [$jid, $path, (int)($c['id'] ?? 0)])) $errors['path'] = t('cat.err_path_exists');
            if ($parent && (!DB::val('SELECT 1 FROM categories WHERE id=? AND journal_id=?', [$parent, $jid]) || $parent === (int)($c['id'] ?? 0))) $parent = 0;
            if (!$errors) {
                $row = ['title' => mb_substr($title, 0, 255), 'path' => $path, 'description' => Sanitizer::clean((string)($p['description'] ?? '')) ?: null, 'parent_id' => $parent ?: null];
                if ($c) DB::exec('UPDATE categories SET title=?, path=?, description=?, parent_id=? WHERE id=?', [$row['title'], $row['path'], $row['description'], $row['parent_id'], $c['id']]);
                else { $row += ['journal_id' => $jid, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM categories WHERE journal_id=?', [$jid])]; DB::insert('categories', $row); }
                flash(t('cat.saved')); $this->back('journal', 'categories');
            }
            $c = array_merge($c ?: [], $p);
        }
        $parents = DB::all('SELECT id, title FROM categories WHERE journal_id=? AND parent_id IS NULL AND id<>? ORDER BY title', [$jid, (int)($c['id'] ?? 0)]);
        View::render('backend/category_form', ['title' => t('cat.add'), 'menu' => 'settings', 'area' => 'journal', 'tab' => 'categories', 'c' => $c ?: [], 'errors' => $errors, 'parents' => $parents], 'backend');
    }
    public function categoryDelete(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) { DB::exec('DELETE FROM categories WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]); flash(t('set.deleted')); }
        $this->back('journal', 'categories');
    }
    // ---------------------------------------------------------------- sections
    public function section(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $s = $id !== '' ? DB::one('SELECT * FROM sections WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$s) $this->notFound();
        $editors = DB::all("SELECT DISTINCT u.id,u.given_name,u.family_name FROM users u JOIN user_user_groups x ON x.user_id=u.id JOIN user_groups g ON g.id=x.user_group_id WHERE g.role_key='section_editor' AND g.journal_id=? AND u.disabled=0 ORDER BY u.family_name", [$jid]);
        if (Request::isPost()) {
            $p = $_POST; $title = trim((string)($p['title'] ?? '')); $wc = trim((string)($p['word_count'] ?? ''));
            if ($title === '') $errors['title'] = t('form.required_field');
            if ($wc !== '' && !ctype_digit($wc)) $errors['word_count'] = t('iss.err_int');
            if (!$errors) {
                $row = ['title' => mb_substr($title, 0, 255), 'abbrev' => mb_substr(trim((string)($p['abbrev'] ?? '')), 0, 30), 'policy' => trim((string)($p['policy'] ?? '')) ?: null, 'word_count' => $wc === '' ? null : (int)$wc,
                    'abstracts_required' => empty($p['abstracts_required']) ? 0 : 1, 'editor_restricted' => empty($p['editor_restricted']) ? 0 : 1, 'meta_reviewed' => empty($p['meta_reviewed']) ? 0 : 1];
                if ($s) { DB::exec('UPDATE sections SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$s['id']])); $sid = (int)$s['id']; }
                else { $row += ['journal_id' => $jid, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM sections WHERE journal_id=?', [$jid])]; $sid = DB::insert('sections', $row); }
                DB::exec('DELETE FROM section_editors WHERE section_id=?', [$sid]);
                foreach (array_intersect(array_map('intval', (array)($p['editors'] ?? [])), array_map('intval', array_column($editors, 'id'))) as $uid) DB::exec('INSERT IGNORE INTO section_editors (section_id,user_id) VALUES (?,?)', [$sid, $uid]);
                flash(t('set.section_saved')); $this->back('journal', 'sections');
            }
            $s = array_merge($s ?: [], $p);
        }
        $chosen = $s && !empty($s['id']) && !Request::isPost() ? array_map('intval', array_column(DB::all('SELECT user_id FROM section_editors WHERE section_id=?', [$s['id']]), 'user_id')) : array_map('intval', (array)($_POST['editors'] ?? []));
        View::render('backend/section_form', ['title' => t('set.section'), 'menu' => 'settings', 'area' => 'journal', 'tab' => 'sections', 's' => $s ?: ['abstracts_required' => 1, 'meta_reviewed' => 1], 'editors' => $editors, 'chosen' => $chosen, 'errors' => $errors], 'backend');
    }
    public function sectionDelete(string $id = ''): void {
        $this->guard(); $jid = $this->jid();
        if (Request::isPost()) {
            $used = (int)DB::val('SELECT COUNT(*) FROM submissions WHERE section_id=?', [(int)$id]); $count = (int)DB::val('SELECT COUNT(*) FROM sections WHERE journal_id=?', [$jid]);
            if ($used) flash(t('set.err_section_used'), 'error'); elseif ($count < 2) flash(t('set.err_section_last'), 'error');
            else { DB::exec('DELETE FROM sections WHERE id=? AND journal_id=?', [(int)$id, $jid]); flash(t('set.deleted')); }
        }
        $this->back('journal', 'sections');
    }
    public function sectionMove(string $id = '', string $dir = 'up'): void { $this->guard(); if (Request::isPost()) $this->swap('sections', 'journal_id=' . $this->jid(), (int)$id, $dir); $this->back('journal', 'sections'); }
    /** Moves one row up/down within a `seq` ordered set. */
    private function swap(string $table, string $where, int $id, string $dir): void {
        $list = array_map('intval', array_column(DB::all("SELECT id FROM `$table` WHERE $where ORDER BY seq,id"), 'id')); $i = array_search($id, $list, true); $j = $dir === 'up' ? $i - 1 : $i + 1;
        if ($i === false || $j < 0 || $j >= count($list)) return;
        [$list[$i], $list[$j]] = [$list[$j], $list[$i]]; foreach ($list as $n => $rid) DB::exec("UPDATE `$table` SET seq=? WHERE id=?", [$n + 1, $rid]);
    }

    // ---------------------------------------------------------------- navigation menu items
    public function navItem(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $it = $id !== '' ? DB::one("SELECT * FROM navigation_menu_items WHERE id=? AND journal_id=? AND menu='primary'", [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$it) $this->notFound();
        $tops = DB::all("SELECT id,title FROM navigation_menu_items WHERE journal_id=? AND menu='primary' AND parent_id IS NULL AND id<>? ORDER BY seq", [$jid, (int)($it['id'] ?? 0)]);
        $pages = DB::all('SELECT id,title FROM static_pages WHERE journal_id=? ORDER BY title', [$jid]);
        if (Request::isPost()) {
            $p = $_POST; $type = (string)($p['type'] ?? ''); $title = trim((string)($p['title'] ?? '')); $url = trim((string)($p['url'] ?? '')); $parent = (int)($p['parent_id'] ?? 0) ?: null; $page = (int)($p['static_page_id'] ?? 0) ?: null;
            if (!in_array($type, self::NAV_TYPES, true)) $errors['type'] = t('set.err_choice');
            if ($title === '') $errors['title'] = t('form.required_field');
            if ($type === 'custom_url' && $url !== '' && !preg_match('#^(https?://|/|\#)#i', $url)) $errors['url'] = t('set.err_nav_url');
            if ($type === 'static_page' && !DB::val('SELECT 1 FROM static_pages WHERE id=? AND journal_id=?', [$page, $jid])) $errors['static_page_id'] = t('set.err_choice');
            if ($parent && !in_array($parent, array_map('intval', array_column($tops, 'id')), true)) $errors['parent_id'] = t('set.err_choice');
            if ($it && $parent && DB::val('SELECT 1 FROM navigation_menu_items WHERE parent_id=?', [$it['id']])) $errors['parent_id'] = t('set.err_nav_children');
            if (!$errors) {
                $row = ['title' => mb_substr($title, 0, 255), 'type' => $type, 'static_page_id' => $type === 'static_page' ? $page : null, 'url' => $type === 'custom_url' && $url !== '' ? $url : null, 'parent_id' => $parent];
                if ($it) { DB::exec('UPDATE navigation_menu_items SET ' . implode(',', array_map(fn($k) => "`$k`=?", array_keys($row))) . ' WHERE id=?', array_merge(array_values($row), [$it['id']])); }
                else { $row += ['journal_id' => $jid, 'menu' => 'primary', 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM navigation_menu_items WHERE journal_id=? AND menu=\'primary\' AND parent_id <=> ?', [$jid, $parent])]; DB::insert('navigation_menu_items', $row); }
                flash(t('set.nav_saved')); $this->back('website', 'navigation');
            }
            $it = array_merge($it ?: [], $p);
        }
        View::render('backend/nav_form', ['title' => t('set.nav_item'), 'menu' => 'settings', 'area' => 'website', 'tab' => 'navigation', 'it' => $it ?: [], 'errors' => $errors, 'tops' => $tops, 'pages' => $pages, 'types' => self::NAV_TYPES], 'backend');
    }
    public function navDelete(string $id = ''): void {
        $this->guard(); if (Request::isPost()) { DB::exec("DELETE FROM navigation_menu_items WHERE id=? AND journal_id=? AND menu='primary'", [(int)$id, $this->jid()]); flash(t('set.deleted')); }
        $this->back('website', 'navigation');
    }
    public function navMove(string $id = '', string $dir = 'up'): void {
        $this->guard();
        if (Request::isPost()) { $it = DB::one('SELECT * FROM navigation_menu_items WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]);
            if ($it) $this->swap('navigation_menu_items', "journal_id={$this->jid()} AND menu='primary' AND " . ($it['parent_id'] ? 'parent_id=' . (int)$it['parent_id'] : 'parent_id IS NULL'), (int)$id, $dir); }
        $this->back('website', 'navigation');
    }

    // ---------------------------------------------------------------- static pages
    public function page(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $pg = $id !== '' ? DB::one('SELECT * FROM static_pages WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$pg) $this->notFound();
        if (Request::isPost()) {
            $path = trim((string)($_POST['path'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', $path)) $errors['path'] = t('set.err_path');
            elseif (in_array(strtolower($path), array_map('strtolower', self::RESERVED), true)) $errors['path'] = t('set.err_path_reserved');
            elseif (DB::val('SELECT 1 FROM static_pages WHERE journal_id=? AND path=? AND id<>?', [$jid, $path, (int)($pg['id'] ?? 0)])) $errors['path'] = t('set.err_path_taken');
            if ($title === '') $errors['title'] = t('form.required_field');
            if (!$errors) {
                $content = Sanitizer::clean((string)($_POST['content'] ?? ''));
                if ($pg) DB::exec('UPDATE static_pages SET path=?, title=?, content=? WHERE id=?', [$path, mb_substr($title, 0, 255), $content, $pg['id']]);
                else DB::insert('static_pages', ['journal_id' => $jid, 'path' => $path, 'title' => mb_substr($title, 0, 255), 'content' => $content]);
                flash(t('set.page_saved')); $this->back('website', 'staticPages');
            }
            $pg = array_merge($pg ?: [], $_POST);
        }
        View::render('backend/page_form', ['title' => t('set.static_page'), 'menu' => 'settings', 'area' => 'website', 'tab' => 'staticPages', 'pg' => $pg ?: [], 'errors' => $errors], 'backend');
    }
    public function pageDelete(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) { $jid = $this->jid(); DB::exec("DELETE FROM navigation_menu_items WHERE journal_id=? AND type='static_page' AND static_page_id=?", [$jid, (int)$id]); DB::exec('DELETE FROM static_pages WHERE id=? AND journal_id=?', [(int)$id, $jid]); flash(t('set.deleted')); }
        $this->back('website', 'staticPages');
    }

    // ---------------------------------------------------------------- custom blocks
    public function block(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $b = $id !== '' ? DB::one('SELECT * FROM custom_blocks WHERE id=? AND journal_id=?', [(int)$id, $jid]) : null; $errors = [];
        if ($id !== '' && !$b) $this->notFound();
        if (Request::isPost()) {
            $name = trim((string)($_POST['name'] ?? '')); $title = trim((string)($_POST['title'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', $name)) $errors['name'] = t('set.err_path');
            elseif (DB::val('SELECT 1 FROM custom_blocks WHERE journal_id=? AND name=? AND id<>?', [$jid, $name, (int)($b['id'] ?? 0)])) $errors['name'] = t('set.err_path_taken');
            if ($title === '') $errors['title'] = t('form.required_field');
            if (!$errors) {
                $content = Sanitizer::clean((string)($_POST['content'] ?? '')); $show = empty($_POST['show_title']) ? 0 : 1;
                if ($b) { DB::exec('UPDATE custom_blocks SET name=?, title=?, content=?, show_title=? WHERE id=?', [$name, mb_substr($title, 0, 255), $content, $show, $b['id']]);
                    if ($b['name'] !== $name) $this->renameInSidebar($jid, 'custom:' . $b['name'], 'custom:' . $name); }
                else DB::insert('custom_blocks', ['journal_id' => $jid, 'name' => $name, 'title' => mb_substr($title, 0, 255), 'content' => $content, 'show_title' => $show]);
                flash(t('set.block_saved')); $this->back('website', 'blocks');
            }
            $b = array_merge($b ?: [], $_POST);
        }
        View::render('backend/block_form', ['title' => t('set.custom_block'), 'menu' => 'settings', 'area' => 'website', 'tab' => 'blocks', 'b' => $b ?: [], 'errors' => $errors], 'backend');
    }
    private function renameInSidebar(int $jid, string $old, string $new): void {
        $l = array_map(fn($k) => $k === $old ? $new : $k, Settings::json('sidebarBlocks', [])); Settings::set('sidebarBlocks', json_encode(array_values($l)), $jid);
    }
    public function blockDelete(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) { $jid = $this->jid(); $b = DB::one('SELECT * FROM custom_blocks WHERE id=? AND journal_id=?', [(int)$id, $jid]);
            if ($b) { $l = array_values(array_diff(Settings::json('sidebarBlocks', []), ['custom:' . $b['name']])); Settings::set('sidebarBlocks', json_encode($l), $jid); DB::exec('DELETE FROM custom_blocks WHERE id=?', [$b['id']]); flash(t('set.deleted')); } }
        $this->back('website', 'blocks');
    }

    // ---------------------------------------------------------------- e-mail templates
    public function email(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $e = DB::one('SELECT * FROM email_templates WHERE id=? AND journal_id=?', [(int)$id, $jid]); if (!$e) $this->notFound(); $errors = [];
        if (Request::isPost()) {
            $sub = trim((string)($_POST['subject'] ?? '')); $body = trim((string)($_POST['body'] ?? ''));
            if ($sub === '') $errors['subject'] = t('form.required_field'); if ($body === '') $errors['body'] = t('form.required_field');
            if (!$errors) { DB::exec('UPDATE email_templates SET subject=?, body=?, enabled=?, is_custom=1 WHERE id=?', [mb_substr($sub, 0, 255), $body, empty($_POST['enabled']) ? 0 : 1, $e['id']]); flash(t('profile.saved')); $this->back('workflow', 'templates'); }
            $e = array_merge($e, $_POST);
        }
        View::render('backend/email_form', ['title' => t('set.email_template'), 'menu' => 'settings', 'area' => 'workflow', 'tab' => 'emails', 'e' => $e, 'errors' => $errors], 'backend');
    }
    public function emailReset(string $id = ''): void {
        $this->guard(); $e = DB::one('SELECT * FROM email_templates WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]);
        if ($e && Request::isPost()) {
            $defaults = json_decode((string)@file_get_contents(ROOT . '/install/email_defaults.json'), true) ?: [];
            if (isset($defaults[$e['email_key']])) { DB::exec('UPDATE email_templates SET subject=?, body=?, enabled=1, is_custom=0 WHERE id=?', [$defaults[$e['email_key']][0], $defaults[$e['email_key']][1], $e['id']]); flash(t('set.email_reset')); }
        }
        $this->back('workflow', 'templates');
    }

    // ---------------------------------------------------------------- review forms
    private function reviewFormRow(string $id): array {
        $f = DB::one('SELECT * FROM review_forms WHERE id=? AND journal_id=?', [(int)$id, $this->jid()]); if (!$f) $this->notFound(); return $f;
    }
    public function reviewForm(string $id = ''): void {
        $this->guard(); $jid = $this->jid(); $f = $id !== '' ? $this->reviewFormRow($id) : null; $errors = [];
        if (Request::isPost()) {
            $p = $_POST; $title = trim((string)($p['title'] ?? ''));
            if ($title === '') $errors['title'] = t('rf.err_title');
            if (!$errors) {
                $desc = Sanitizer::clean((string)($p['description'] ?? '')) ?: null;
                if ($f) { DB::exec('UPDATE review_forms SET title=?, description=? WHERE id=?', [mb_substr($title, 0, 255), $desc, $f['id']]); $fid = (int)$f['id']; }
                else $fid = DB::insert('review_forms', ['journal_id' => $jid, 'title' => mb_substr($title, 0, 255), 'description' => $desc, 'active' => 0, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM review_forms WHERE journal_id=?', [$jid])]);
                flash(t('rf.saved')); redirect(url('content', 'reviewForm', [$fid]));
            }
            $f = array_merge($f ?: [], $p);
        }
        View::render('backend/review_form_edit', ['title' => t('rf.edit'), 'menu' => 'settings', 'area' => 'workflow', 'tab' => 'reviewForms', 'f' => $f ?: [], 'errors' => $errors,
            'items' => !empty($f['id']) ? RF::elements((int)$f['id']) : [], 'inUse' => !empty($f['id']) && RF::inUse((int)$f['id'])], 'backend');
    }
    public function reviewFormToggle(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) {
            $f = $this->reviewFormRow($id);
            if ($f['active'] && RF::inUse((int)$f['id'])) flash(t('rf.err_in_use'), 'error');
            else { DB::exec('UPDATE review_forms SET active=? WHERE id=?', [$f['active'] ? 0 : 1, $f['id']]); flash(t('rf.saved')); }
        }
        $this->back('workflow', 'reviewForms');
    }
    public function reviewFormCopy(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) {
            $f = $this->reviewFormRow($id); $jid = $this->jid();
            $n = DB::insert('review_forms', ['journal_id' => $jid, 'title' => mb_substr(t('rf.copy_of', $f['title']), 0, 255), 'description' => $f['description'], 'active' => 0, 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM review_forms WHERE journal_id=?', [$jid])]);
            foreach (RF::elements((int)$f['id']) as $e) DB::insert('review_form_elements', ['review_form_id' => $n, 'seq' => $e['seq'], 'question' => $e['question'], 'description' => $e['description'], 'element_type' => $e['element_type'], 'options' => $e['options'], 'required' => $e['required'], 'included' => $e['included']]);
            flash(t('rf.saved'));
        }
        $this->back('workflow', 'reviewForms');
    }
    public function reviewFormDelete(string $id = ''): void {
        $this->guard();
        if (Request::isPost()) {
            $f = $this->reviewFormRow($id);
            if (DB::val('SELECT 1 FROM review_assignments WHERE review_form_id=?', [$f['id']])) flash(t('rf.err_in_use'), 'error');
            else { DB::exec('DELETE FROM review_forms WHERE id=?', [$f['id']]); flash(t('set.deleted')); }
        }
        $this->back('workflow', 'reviewForms');
    }
    public function reviewFormPreview(string $id = ''): void {
        $this->guard(); $f = $this->reviewFormRow($id);
        View::render('backend/review_form_preview', ['title' => t('rf.preview'), 'menu' => 'settings', 'f' => $f, 'items' => RF::elements((int)$f['id'])], 'backend');
    }
    public function reviewFormItem(string $fid = '', string $id = ''): void {
        $this->guard(); $f = $this->reviewFormRow($fid); $errors = [];
        $it = $id !== '' ? DB::one('SELECT * FROM review_form_elements WHERE id=? AND review_form_id=?', [(int)$id, $f['id']]) : null;
        if ($id !== '' && !$it) $this->notFound();
        if (Request::isPost()) {
            $p = $_POST; $q = trim((string)($p['question'] ?? '')); $type = (int)($p['element_type'] ?? 0);
            if ($q === '') $errors['question'] = t('rf.err_question');
            if (!isset(RF::TYPES[$type])) $errors['element_type'] = t('rf.err_type');
            if (!$errors) {
                $opts = null;
                if (RF::hasChoices($type)) $opts = json_encode(array_values(array_filter(array_map(fn($x) => mb_substr(trim((string)$x), 0, 255), (array)($p['options'] ?? [])), 'strlen')), JSON_UNESCAPED_UNICODE);
                $row = ['question' => Sanitizer::clean($q), 'description' => Sanitizer::clean((string)($p['description'] ?? '')) ?: null, 'element_type' => $type, 'options' => $opts, 'required' => empty($p['required']) ? 0 : 1, 'included' => empty($p['included']) ? 0 : 1];
                if ($it) DB::exec('UPDATE review_form_elements SET question=?, description=?, element_type=?, options=?, required=?, included=? WHERE id=?', [$row['question'], $row['description'], $row['element_type'], $row['options'], $row['required'], $row['included'], $it['id']]);
                else DB::insert('review_form_elements', $row + ['review_form_id' => $f['id'], 'seq' => 1 + (int)DB::val('SELECT COALESCE(MAX(seq),0) FROM review_form_elements WHERE review_form_id=?', [$f['id']])]);
                flash(t('rf.saved')); redirect(url('content', 'reviewForm', [$f['id']]));
            }
            $it = array_merge($it ?: [], $p); $it['options_list'] = array_values((array)($p['options'] ?? []));
        } elseif ($it) $it['options_list'] = RF::choices($it);
        View::render('backend/review_form_item', ['title' => t(!empty($it['id']) ? 'rf.item_edit' : 'rf.item_create'), 'menu' => 'settings', 'area' => 'workflow', 'tab' => 'reviewForms', 'f' => $f, 'it' => $it ?: [], 'errors' => $errors], 'backend');
    }
    public function reviewFormItemDelete(string $fid = '', string $id = ''): void {
        $this->guard(); $f = $this->reviewFormRow($fid);
        if (Request::isPost()) { DB::exec('DELETE FROM review_form_elements WHERE id=? AND review_form_id=?', [(int)$id, $f['id']]); flash(t('set.deleted')); }
        redirect(url('content', 'reviewForm', [$f['id']]));
    }
}
