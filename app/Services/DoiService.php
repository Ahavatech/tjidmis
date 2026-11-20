<?php
namespace App\Services;
use App\Core\{DB, Settings};

/** DOI pattern generation (no Crossref deposit). DOI = prefix + "." + pattern, e.g. 10.59543/jidmis.182. */
class DoiService {
    const DEFAULT_PATTERN = '%a';
    /** Tokens: %j journal abbreviation, %v volume, %i issue number, %Y year, %a submission id, %p publication id. */
    public static function build(int $sid): ?string {
        $prefix = trim((string)Settings::get('doiPrefix', ''));
        if ($prefix === '') return null;
        $r = DB::one('SELECT p.id pid, i.volume, i.number, i.year FROM submissions s JOIN publications p ON p.id=s.current_publication_id LEFT JOIN issues i ON i.id=p.issue_id WHERE s.id=?', [$sid]);
        if (!$r) return null;
        $pattern = trim((string)Settings::get('doiPattern', '')) ?: self::DEFAULT_PATTERN;
        $abbr = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string)Settings::get('abbreviation', '')));
        $suffix = strtr($pattern, ['%j' => $abbr, '%v' => (string)($r['volume'] ?? ''), '%i' => (string)($r['number'] ?? ''), '%Y' => (string)($r['year'] ?? ''), '%a' => (string)$sid, '%p' => (string)$r['pid']]);
        $suffix = trim(preg_replace('/[^A-Za-z0-9._\-]+/', '', $suffix), '.-_');
        $doi = $suffix === '' ? null : $prefix . '.' . $suffix;
        return ($doi && preg_match('#^10\.\d{4,9}/\S+$#', $doi)) ? $doi : null;
    }
    /** Assigns a DOI if the publication has none and the generated one is unused. Returns the DOI or null. */
    public static function assign(int $sid): ?string {
        $doi = self::build($sid);
        if (!$doi) return null;
        $pid = (int)DB::val('SELECT current_publication_id FROM submissions WHERE id=?', [$sid]);
        if (DB::val('SELECT 1 FROM publications WHERE doi=? AND id<>?', [$doi, $pid])) return null;
        $n = DB::exec('UPDATE publications SET doi=? WHERE id=? AND (doi IS NULL OR doi="")', [$doi, $pid]);
        return $n ? $doi : null;
    }
}
