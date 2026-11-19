<?php
namespace App\Core;
/** Allow-list HTML sanitiser for rich text (swap for HTMLPurifier in vendor/ if present). */
class Sanitizer {
    private const TAGS = ['p','br','strong','b','em','i','u','sub','sup','ul','ol','li','a','h2','h3','h4','h5','blockquote','table','thead','tbody','tr','th','td','img','span','div','hr','code','pre'];
    private const ATTRS = ['a' => ['href','title','target','rel'], 'img' => ['src','alt','width','height'], 'td' => ['colspan','rowspan'], 'th' => ['colspan','rowspan'], '*' => ['class']];
    public static function clean(?string $html): string {
        if ($html === null || trim($html) === '') return '';
        $d = new \DOMDocument();
        libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="utf-8"?><div id="__r">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $d->getElementById('__r');
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $n) $out .= $d->saveHTML($n);
        return $out;
    }
    private static function walk(\DOMNode $n): void {
        foreach (iterator_to_array($n->childNodes) as $c) {
            if ($c instanceof \DOMComment) { $n->removeChild($c); continue; }
            if (!$c instanceof \DOMElement) continue;
            $tag = strtolower($c->tagName);
            if (!in_array($tag, self::TAGS, true)) {
                if (in_array($tag, ['script','style','iframe','object','embed','form'], true)) { $n->removeChild($c); continue; }
                self::walk($c);
                while ($c->firstChild) $n->insertBefore($c->firstChild, $c);
                $n->removeChild($c); continue;
            }
            $ok = array_merge(self::ATTRS[$tag] ?? [], self::ATTRS['*']);
            foreach (iterator_to_array($c->attributes) as $a) {
                $name = strtolower($a->name);
                if (!in_array($name, $ok, true)) { $c->removeAttribute($a->name); continue; }
                if (in_array($name, ['href','src'], true) && !preg_match('#^(https?:|mailto:|/|\#)#i', trim($a->value))) $c->removeAttribute($a->name);
            }
            if ($tag === 'a' && $c->getAttribute('target') === '_blank') $c->setAttribute('rel', 'noopener');
            self::walk($c);
        }
    }
}
