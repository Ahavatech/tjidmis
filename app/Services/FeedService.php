<?php
namespace App\Services;
use App\Core\Settings;
use App\Models\Catalog;
class FeedService {
    public static function items(int $jid): array {
        $issue = Catalog::currentIssue($jid); $items = [];
        if ($issue) foreach (Catalog::toc((int)$issue['id']) as $sec) foreach ($sec['articles'] as $a) $items[] = $a;
        return [$issue, $items];
    }
    private static function abs(string $u): string { $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'; return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $u; }
    public static function render(int $jid, string $fmt): array {
        [$issue, $items] = self::items($jid);
        $name = Settings::get('name'); $home = self::abs(url()); $x = fn($s) => htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $link = fn($a) => self::abs(url('article', 'view', [$a['sid']]));
        $now = date(DATE_ATOM);
        if ($fmt === 'atom') {
            $b = '<?xml version="1.0" encoding="UTF-8"?><feed xmlns="http://www.w3.org/2005/Atom"><title>' . $x($name) . '</title><link href="' . $x($home) . '"/><id>' . $x($home) . '</id><updated>' . $now . '</updated>';
            foreach ($items as $a) $b .= '<entry><title>' . $x($a['title']) . '</title><link href="' . $x($link($a)) . '"/><id>' . $x($link($a)) . '</id><updated>' . $now . '</updated><author><name>' . $x($a['authors_str']) . '</name></author></entry>';
            return ['application/atom+xml', $b . '</feed>'];
        }
        if ($fmt === 'rss2') {
            $b = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>' . $x($name) . '</title><link>' . $x($home) . '</link><description>' . $x($name) . '</description>';
            foreach ($items as $a) $b .= '<item><title>' . $x($a['title']) . '</title><link>' . $x($link($a)) . '</link><guid>' . $x($link($a)) . '</guid><author>' . $x($a['authors_str']) . '</author></item>';
            return ['application/rss+xml', $b . '</channel></rss>'];
        }
        $b = '<?xml version="1.0" encoding="UTF-8"?><rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/"><channel rdf:about="' . $x($home) . '"><title>' . $x($name) . '</title><link>' . $x($home) . '</link><description>' . $x($name) . '</description><items><rdf:Seq>';
        foreach ($items as $a) $b .= '<rdf:li rdf:resource="' . $x($link($a)) . '"/>';
        $b .= '</rdf:Seq></items></channel>';
        foreach ($items as $a) $b .= '<item rdf:about="' . $x($link($a)) . '"><title>' . $x($a['title']) . '</title><link>' . $x($link($a)) . '</link></item>';
        return ['application/rdf+xml', $b . '</rdf:RDF>'];
    }
}
