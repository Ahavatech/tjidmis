<?php
namespace App\Services;
use App\Core\Settings;

/** Workflow > Submission > Metadata, as in OJS 3.3: each field is off, or enabled with one of three submission modes
 *  (enable = editors only, request = authors are asked, require = authors must fill it in before they can finish). */
class MetadataService {
    const MODES = ['enable', 'request', 'require'];
    /** key => [publications column, shape (list|text|refs), label key] */
    const FIELDS = [
        'coverage' => ['coverage', 'text'], 'languages' => ['dc_languages', 'text'], 'rights' => ['rights', 'text'], 'source' => ['source', 'text'], 'subjects' => ['subjects', 'list'],
        'type' => ['dc_type', 'text'], 'disciplines' => ['disciplines', 'list'], 'keywords' => ['keywords', 'list'], 'agencies' => ['agencies', 'list'], 'citations' => ['references_text', 'refs'],
    ];
    const DEFAULTS = ['keywords' => 'request', 'citations' => 'request'];
    public static function mode(string $f): string {
        $v = (string)Settings::get($f, self::DEFAULTS[$f] ?? '0');
        return in_array($v, self::MODES, true) ? $v : '0';
    }
    public static function enabled(string $f): bool { return self::mode($f) !== '0'; }
    public static function asked(string $f): bool { return in_array(self::mode($f), ['request', 'require'], true); }
    public static function required(string $f): bool { return self::mode($f) === 'require'; }
    public static function column(string $f): string { return self::FIELDS[$f][0]; }
    public static function shape(string $f): string { return self::FIELDS[$f][1]; }
    /** Form value -> database value. */
    public static function fromPost(string $f, $raw): ?string {
        $raw = trim((string)$raw);
        switch (self::shape($f)) {
            case 'list': $l = array_values(array_unique(array_filter(array_map('trim', preg_split('/[;,\n]+/', $raw))))); return $l ? json_encode($l, JSON_UNESCAPED_UNICODE) : null;
            case 'refs': $r = implode('', array_map(fn($x) => '<p>' . e(trim($x)) . '</p>', array_filter(preg_split('/\R+/', $raw), fn($x) => trim($x) !== ''))); return $r ?: null;
            default: return $raw === '' ? null : mb_substr($raw, 0, 500);
        }
    }
    /** Database value -> form value. */
    public static function toForm(string $f, ?string $db): string {
        $db = (string)$db;
        switch (self::shape($f)) {
            case 'list': return implode('; ', json_decode($db ?: '[]', true) ?: []);
            case 'refs': return trim(strip_tags(str_replace('</p>', "\n", $db)));
            default: return $db;
        }
    }
}
