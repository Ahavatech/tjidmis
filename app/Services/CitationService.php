<?php
namespace App\Services;
class CitationService {
    const STYLES = ['apa' => 'APA', 'mla' => 'MLA', 'chicago' => 'Chicago', 'harvard' => 'Harvard', 'vancouver' => 'Vancouver'];
    /** Returns HTML-safe (already escaped) citation strings keyed by style. */
    public static function all(array $a, array $journalName): array {
        $out = []; foreach (self::STYLES as $k => $_) $out[$k] = self::format($k, $a, $journalName); return $out;
    }
    private static function initials(string $given): string {
        $out = ''; foreach (preg_split('/[\s\-]+/', trim($given)) as $g) if ($g !== '') $out .= mb_strtoupper(mb_substr($g, 0, 1)) . '.'; return $out;
    }
    public static function format(string $style, array $a, array $j): string {
        $au = $a['authors']; $n = count($au);
        $year = $a['date_published'] ? substr($a['date_published'], 0, 4) : '';
        $vol = $a['issue']['volume'] ?? ''; $no = $a['issue']['number'] ?? ''; $pages = $a['pages'] ?? '';
        $doi = $a['doi'] ? 'https://doi.org/' . $a['doi'] : ''; $title = rtrim($a['title'], '.'); $jn = $j['name'];
        $fam = fn($x) => $x['family_name'] !== '' ? $x['family_name'] : $x['given_name'];
        switch ($style) {
            case 'apa':
                $names = array_map(fn($x) => $x['family_name'] !== '' ? $x['family_name'] . ', ' . self::initials($x['given_name']) : $x['given_name'], $au);
                $s = $n > 1 ? implode(', ', array_slice($names, 0, -1)) . ', &amp; ' . end($names) : ($names[0] ?? '');
                $r = e("$s ($year). $title. ") . '<em>' . e($jn) . '</em>' . e($vol !== '' ? ', ' : '') . ($vol !== '' ? '<em>' . e($vol) . '</em>' : '') . e($no !== '' ? "($no)" : '') . e($pages ? ", $pages" : '') . '. ' . e($doi);
                return str_replace('&amp;amp;', '&amp;', $r);
            case 'mla':
                $f = $au[0] ?? null; $s = $f ? ($f['family_name'] !== '' ? $f['family_name'] . ', ' . $f['given_name'] : $f['given_name']) : '';
                if ($n == 2) $s .= ', and ' . trim($au[1]['given_name'] . ' ' . $au[1]['family_name']); elseif ($n > 2) $s .= ', et al';
                return e("$s. \"$title.\" ") . '<em>' . e($jn) . '</em>' . e(($vol !== '' ? ", vol. $vol" : '') . ($no !== '' ? ", no. $no" : '') . ($year ? ", $year" : '') . ($pages ? ", pp. $pages" : '') . ($doi ? ", $doi" : '') . '.');
            case 'chicago':
                $names = array_map(fn($x, $i) => $i == 0 && $x['family_name'] !== '' ? $x['family_name'] . ', ' . $x['given_name'] : trim($x['given_name'] . ' ' . $x['family_name']), $au, array_keys($au));
                $s = $n > 1 ? implode(', ', array_slice($names, 0, -1)) . ', and ' . end($names) : ($names[0] ?? '');
                return e("$s. \"$title.\" ") . '<em>' . e($jn) . '</em>' . e(($vol !== '' ? " $vol" : '') . ($no !== '' ? ", no. $no" : '') . ($year ? " ($year)" : '') . ($pages ? ": $pages" : '') . ($doi ? ". $doi" : '') . '.');
            case 'harvard':
                $names = array_map(fn($x) => $fam($x) . ', ' . self::initials($x['given_name']), $au);
                $s = $n > 1 ? implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names) : ($names[0] ?? '');
                return e("$s ($year) '$title', ") . '<em>' . e($jn) . '</em>' . e(($vol !== '' ? ", $vol" : '') . ($no !== '' ? "($no)" : '') . ($pages ? ", pp. $pages" : '') . ($doi ? ". Available at: $doi" : '') . '.');
            default: // vancouver
                $names = array_map(fn($x) => $fam($x) . ' ' . str_replace('.', '', self::initials($x['given_name'])), $au);
                $s = $n > 6 ? implode(', ', array_slice($names, 0, 6)) . ', et al' : implode(', ', $names);
                return e("$s. $title. $jn. " . $year . ($vol !== '' ? ";$vol" : '') . ($no !== '' ? "($no)" : '') . ($pages ? ":$pages" : '') . '.' . ($doi ? " $doi" : ''));
        }
    }
}
