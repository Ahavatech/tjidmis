<?php
namespace App\Services;
use App\Core\DB;

/** Review forms as in OJS 3.3: a form has items of six types; reviewers answer them instead of the two free comment boxes. */
class ReviewFormService {
    const SMALL_TEXT = 1, TEXT = 2, TEXTAREA = 3, CHECKBOXES = 4, RADIOS = 5, DROPDOWN = 6;
    const TYPES = [1 => 'rf.type_smalltext', 2 => 'rf.type_text', 3 => 'rf.type_textarea', 4 => 'rf.type_checkboxes', 5 => 'rf.type_radios', 6 => 'rf.type_dropdown'];

    public static function hasChoices(int $type): bool { return in_array($type, [self::CHECKBOXES, self::RADIOS, self::DROPDOWN], true); }
    public static function choices(array $el): array { $o = json_decode((string)($el['options'] ?? ''), true); return is_array($o) ? array_values($o) : []; }
    public static function elements(int $formId): array { return DB::all('SELECT * FROM review_form_elements WHERE review_form_id=? ORDER BY seq,id', [$formId]); }
    public static function inUse(int $formId): bool { return (bool)DB::val("SELECT 1 FROM review_assignments WHERE review_form_id=? AND status NOT IN ('cancelled','declined')", [$formId]); }
    public static function active(int $jid): array { return DB::all('SELECT id,title FROM review_forms WHERE journal_id=? AND active=1 ORDER BY seq,id', [$jid]); }

    /** Saved answers for an assignment: element id => string (choices: option indexes joined by a comma for checkboxes). */
    public static function responses(int $assignmentId): array {
        $r = []; foreach (DB::all('SELECT review_form_element_id i, response_value v FROM review_form_responses WHERE review_assignment_id=?', [$assignmentId]) as $x) $r[(int)$x['i']] = (string)$x['v'];
        return $r;
    }
    /** Human-readable answer for display. */
    public static function display(array $el, ?string $raw): string {
        if ($raw === null || $raw === '') return '';
        if (!self::hasChoices((int)$el['element_type'])) return $raw;
        $ch = self::choices($el); $out = [];
        foreach (array_filter(explode(',', $raw), 'strlen') as $i) if (isset($ch[(int)$i])) $out[] = $ch[(int)$i];
        return implode(', ', $out);
    }
    /** Normalise posted answers; returns [values, missingRequired]. */
    public static function fromPost(array $elements, array $post): array {
        $vals = []; $missing = false;
        foreach ($elements as $el) {
            $k = 'rf_' . $el['id']; $t = (int)$el['element_type']; $raw = $post[$k] ?? null;
            if ($t === self::CHECKBOXES) $v = implode(',', array_map('intval', array_filter((array)$raw, 'is_scalar')));
            elseif ($t === self::RADIOS || $t === self::DROPDOWN) $v = ($raw !== null && $raw !== '' && !is_array($raw) && isset(self::choices($el)[(int)$raw])) ? (string)(int)$raw : '';
            else $v = is_array($raw) ? '' : trim((string)$raw);
            if ($t === self::SMALL_TEXT) $v = mb_substr($v, 0, 40);
            if ($t === self::TEXT) $v = mb_substr($v, 0, 255);
            if ($el['required'] && $v === '') $missing = true;
            $vals[(int)$el['id']] = $v;
        }
        return [$vals, $missing];
    }
    public static function save(int $assignmentId, array $vals): void {
        DB::exec('DELETE FROM review_form_responses WHERE review_assignment_id=?', [$assignmentId]);
        foreach ($vals as $id => $v) DB::insert('review_form_responses', ['review_assignment_id' => $assignmentId, 'review_form_element_id' => $id, 'response_value' => $v]);
    }
    /** HTML for one item's input. $value is the stored string. */
    public static function input(array $el, string $value = '', bool $disabled = false): string {
        $n = 'rf_' . (int)$el['id']; $t = (int)$el['element_type']; $d = $disabled ? ' disabled' : ''; $req = $el['required'] ? ' required' : '';
        $ch = self::choices($el); $sel = array_filter(explode(',', $value), 'strlen');
        switch ($t) {
            case self::SMALL_TEXT: return '<input type="text" class="rf_small" name="' . $n . '" maxlength="40" value="' . e($value) . '"' . $d . $req . '>';
            case self::TEXT: return '<input type="text" name="' . $n . '" maxlength="255" value="' . e($value) . '"' . $d . $req . '>';
            case self::TEXTAREA: return '<textarea name="' . $n . '" rows="6"' . $d . $req . '>' . e($value) . '</textarea>';
            case self::DROPDOWN:
                $h = '<select name="' . $n . '"' . $d . $req . '><option value=""></option>';
                foreach ($ch as $i => $c) $h .= '<option value="' . $i . '"' . (in_array((string)$i, $sel, true) ? ' selected' : '') . '>' . e($c) . '</option>';
                return $h . '</select>';
            default:
                $h = ''; $type = $t === self::CHECKBOXES ? 'checkbox' : 'radio'; $name = $t === self::CHECKBOXES ? $n . '[]' : $n;
                foreach ($ch as $i => $c) $h .= '<label class="rf_choice"><input type="' . $type . '" name="' . $name . '" value="' . $i . '"' . (in_array((string)$i, $sel, true) ? ' checked' : '') . $d . '> ' . e($c) . '</label> ';
                return $h;
        }
    }
}
