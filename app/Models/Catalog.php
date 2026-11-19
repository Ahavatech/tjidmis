<?php
namespace App\Models;
use App\Core\DB;

/** Read-side queries for published content (issues, articles, authors, galleys). */
class Catalog {
    public static function issueLabel(array $i): string {
        $p = [];
        if (!empty($i['show_volume']) && $i['volume'] !== null && $i['volume'] !== '') $p[] = 'Vol. ' . $i['volume'];
        if (!empty($i['show_number']) && $i['number'] !== null && $i['number'] !== '') $p[] = 'No. ' . $i['number'];
        $s = implode(' ', $p);
        if (!empty($i['show_year']) && $i['year']) $s .= ($s ? ' ' : '') . '(' . $i['year'] . ')';
        if (!empty($i['show_title']) && $i['title']) $s .= ($s ? ': ' : '') . $i['title'];
        return $s !== '' ? $s : (string)($i['title'] ?: 'Issue');
    }
    public static function currentIssue(int $jid): ?array {
        return DB::one('SELECT * FROM issues WHERE journal_id=? AND published=1 AND is_current=1', [$jid])
            ?? DB::one('SELECT * FROM issues WHERE journal_id=? AND published=1 ORDER BY date_published DESC, id DESC LIMIT 1', [$jid]);
    }
    public static function issue(int $jid, string $idOrPath): ?array {
        return DB::one('SELECT * FROM issues WHERE journal_id=? AND published=1 AND (id=? OR url_path=?)', [$jid, ctype_digit($idOrPath) ? (int)$idOrPath : 0, $idOrPath]);
    }
    public static function archiveCount(int $jid): int { return (int)DB::val('SELECT COUNT(*) FROM issues WHERE journal_id=? AND published=1', [$jid]); }
    public static function archive(int $jid, int $page, int $per): array {
        $off = max(0, ($page - 1) * $per);
        return DB::all("SELECT * FROM issues WHERE journal_id=? AND published=1 ORDER BY seq ASC, date_published DESC, id DESC LIMIT $per OFFSET $off", [$jid]);
    }
    private static function ph(array $ids): string { return implode(',', array_fill(0, count($ids), '?')); }
    /** Attach authors (as list + joined string) and galleys to article rows keyed by publication id (pid). */
    private static function decorate(array $rows): array {
        if (!$rows) return [];
        $pids = array_column($rows, 'pid');
        $auth = $gal = [];
        foreach (DB::all('SELECT * FROM authors WHERE publication_id IN (' . self::ph($pids) . ') ORDER BY seq, id', $pids) as $a) $auth[$a['publication_id']][] = $a;
        foreach (DB::all('SELECT g.*, f.stored_name, f.mime FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.publication_id IN (' . self::ph($pids) . ') ORDER BY g.seq, g.id', $pids) as $g) $gal[$g['publication_id']][] = $g;
        foreach ($rows as &$r) {
            $r['authors'] = $auth[$r['pid']] ?? [];
            $r['authors_str'] = implode(', ', array_map(fn($a) => trim($a['given_name'] . ' ' . $a['family_name']), $r['authors']));
            $r['galleys'] = $gal[$r['pid']] ?? [];
        }
        return $rows;
    }
    /** @return array<int,array{title:string,hide:bool,articles:array}> */
    public static function toc(int $issueId): array {
        $rows = DB::all("SELECT p.id pid, p.title, p.pages, p.doi, s.id sid, sec.id secid, sec.title sectitle, sec.hide_title, COALESCE(iso.seq,999) sseq
            FROM publications p JOIN submissions s ON s.id=p.submission_id AND s.current_publication_id=p.id
            JOIN sections sec ON sec.id=p.section_id LEFT JOIN issue_section_order iso ON iso.issue_id=p.issue_id AND iso.section_id=sec.id
            WHERE p.issue_id=? AND p.status='published' AND s.status='published' ORDER BY sseq, sec.id, p.seq, p.id", [$issueId]);
        $out = [];
        foreach (self::decorate($rows) as $r) {
            $out[$r['secid']] ??= ['title' => $r['sectitle'], 'hide' => (bool)$r['hide_title'], 'articles' => []];
            $out[$r['secid']]['articles'][] = $r;
        }
        return array_values($out);
    }
    public static function article(int $jid, int $sid): ?array {
        $a = DB::one("SELECT p.*, p.id pid, s.id sid, sec.title sectitle FROM submissions s JOIN publications p ON p.id=s.current_publication_id
            LEFT JOIN sections sec ON sec.id=p.section_id WHERE s.id=? AND s.journal_id=? AND s.status='published' AND p.status='published'", [$sid, $jid]);
        if (!$a) return null;
        if ($a['issue_id']) {
            $a['issue'] = DB::one('SELECT * FROM issues WHERE id=? AND published=1', [$a['issue_id']]);
            if (!$a['issue']) return null;
        } else $a['issue'] = null;
        return self::decorate([$a])[0];
    }
    public static function galley(int $pid, int $gid): ?array {
        return DB::one('SELECT g.*, f.stored_name, f.mime, f.original_name, f.file_stage, f.submission_id FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.id=? AND g.publication_id=?', [$gid, $pid]);
    }
    public static function metrics(int $sid): array {
        $r = DB::all("SELECT type, SUM(count) n FROM metrics WHERE submission_id=? GROUP BY type", [$sid]);
        $m = ['abstract_view' => 0, 'galley_download' => 0]; foreach ($r as $x) $m[$x['type']] = (int)$x['n']; return $m;
    }
    public static function announcements(int $jid, int $limit, int $offset = 0): array {
        return DB::all("SELECT * FROM announcements WHERE journal_id=? AND (date_expire IS NULL OR date_expire>=CURDATE()) ORDER BY date_posted DESC, id DESC LIMIT $limit OFFSET $offset", [$jid]);
    }
    public static function announcementCount(int $jid): int {
        return (int)DB::val('SELECT COUNT(*) FROM announcements WHERE journal_id=? AND (date_expire IS NULL OR date_expire>=CURDATE())', [$jid]);
    }
    /** Published articles for search. $f: query, authors, from (Y-m-d), to (Y-m-d). */
    public static function search(int $jid, array $f, int $limit, int $offset): array {
        $w = ["s.journal_id=?", "s.status='published'", "p.status='published'"]; $p = [$jid];
        $q = trim((string)($f['query'] ?? ''));
        $score = '0';
        if ($q !== '') {
            foreach (preg_split('/\s+/', $q) as $term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';
                $w[] = '(p.title LIKE ? OR p.abstract LIKE ? OR CAST(p.keywords AS CHAR) LIKE ?)'; array_push($p, $like, $like, $like);
            }
            $score = 'MATCH(p.title,p.abstract) AGAINST (? IN NATURAL LANGUAGE MODE)';
        }
        if (($f['authors'] ?? '') !== '') {
            $like = '%' . addcslashes(trim($f['authors']), '%_\\') . '%';
            $w[] = 'EXISTS (SELECT 1 FROM authors a WHERE a.publication_id=p.id AND CONCAT(a.given_name," ",a.family_name) LIKE ?)'; $p[] = $like;
        }
        if (!empty($f['from'])) { $w[] = 'p.date_published>=?'; $p[] = $f['from']; }
        if (!empty($f['to'])) { $w[] = 'p.date_published<=?'; $p[] = $f['to']; }
        $where = implode(' AND ', $w);
        $count = (int)DB::val("SELECT COUNT(*) FROM publications p JOIN submissions s ON s.id=p.submission_id AND s.current_publication_id=p.id WHERE $where", $p);
        $sp = $q !== '' ? array_merge([$q], $p) : $p;
        $rows = DB::all("SELECT p.id pid, p.title, p.pages, p.doi, p.date_published, s.id sid, p.issue_id, $score rel
            FROM publications p JOIN submissions s ON s.id=p.submission_id AND s.current_publication_id=p.id WHERE $where
            ORDER BY rel DESC, p.date_published DESC, p.id DESC LIMIT $limit OFFSET $offset", $sp);
        $rows = self::decorate($rows);
        foreach ($rows as &$r) $r['issue'] = $r['issue_id'] ? DB::one('SELECT * FROM issues WHERE id=?', [$r['issue_id']]) : null;
        return [$count, $rows];
    }
}
