<?php
namespace App\Controllers\Public;
use App\Core\{DB, Settings};

/** OAI-PMH 2.0 endpoint at /{journal}/oai, in the same shape as OJS 3.3 (Identify, ListMetadataFormats, ListSets, ListIdentifiers, ListRecords, GetRecord; oai_dc records). */
class OaiController {
    const PAGE = 100;
    private function repoId(): string { return (string)(Settings::site('oaiRepositoryId', '') ?: 'ojs2.' . preg_replace('/^www\./', '', (string)parse_url(abs_url('/'), PHP_URL_HOST))); }
    private function x(?string $s): string { return htmlspecialchars((string)$s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
    private function stamp(?string $d): string { return gmdate('Y-m-d\TH:i:s\Z', $d ? strtotime($d . ' UTC') : 0); }
    private function head(string $verb, array $args = [], ?string $err = null): string {
        $a = ''; foreach ($args as $k => $v) $a .= ' ' . $k . '="' . $this->x($v) . '"';
        $base = abs_url(url('oai'));
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"' . "\n\t" . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n\t"
            . 'xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/' . "\n\t\t" . 'http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">' . "\n\t" . '<responseDate>' . gmdate('Y-m-d\TH:i:s\Z') . '</responseDate>' . "\n\t"
            . ($err !== null ? '<request>' . $base . '</request>' : '<request' . $a . '>' . $base . '</request>') . "\n";
    }
    private function out(string $xml): void { header('Content-Type: text/xml;charset=UTF-8'); echo $xml . "</OAI-PMH>\n"; }
    private function error(string $code, string $msg): void { $this->out($this->head('', [], $code) . "\t" . '<error code="' . $code . '">' . $this->x($msg) . '</error>' . "\n"); }

    public function index(): void {
        $p = $_GET + $_POST; $verb = (string)($p['verb'] ?? '');
        $j = Settings::journal(); $jid = (int)$j['id'];
        $args = $p; unset($args['verb']);
        $req = ['verb' => $verb] + $args;
        switch ($verb) {
            case 'Identify': $this->identify($jid, $req); return;
            case 'ListMetadataFormats': $this->formats($req); return;
            case 'ListSets': $this->sets($jid, $req); return;
            case 'GetRecord': $this->getRecord($jid, $req); return;
            case 'ListIdentifiers': case 'ListRecords': $this->listing($jid, $verb, $req); return;
            default: $this->error('badVerb', 'Illegal OAI verb');
        }
    }
    private function identify(int $jid, array $req): void {
        $earliest = DB::val("SELECT MIN(COALESCE(p.date_published, s.date_last_activity)) FROM submissions s JOIN publications p ON p.id=s.current_publication_id WHERE s.journal_id=? AND s.status='published'", [$jid]);
        $x = $this->head('Identify', ['verb' => 'Identify']) . "\t<Identify>\n"
            . "\t\t<repositoryName>" . $this->x(Settings::get('name')) . "</repositoryName>\n\t\t<baseURL>" . abs_url(url('oai')) . "</baseURL>\n\t\t<protocolVersion>2.0</protocolVersion>\n"
            . "\t\t<adminEmail>" . $this->x(Settings::get('contactEmail', '')) . "</adminEmail>\n\t\t<earliestDatestamp>" . $this->stamp($earliest) . "</earliestDatestamp>\n"
            . "\t\t<deletedRecord>persistent</deletedRecord>\n\t\t<granularity>YYYY-MM-DDThh:mm:ssZ</granularity>\n\t\t<compression>gzip</compression>\n\t\t<compression>deflate</compression>\n"
            . "\t\t<description>\n\t\t\t<oai-identifier\n\t\t\t\txmlns=\"http://www.openarchives.org/OAI/2.0/oai-identifier\"\n\t\t\t\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n\t\t\t\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai-identifier\n\t\t\t\t\thttp://www.openarchives.org/OAI/2.0/oai-identifier.xsd\">\n"
            . "\t\t\t\t<scheme>oai</scheme>\n\t\t\t\t<repositoryIdentifier>" . $this->x($this->repoId()) . "</repositoryIdentifier>\n\t\t\t\t<delimiter>:</delimiter>\n\t\t\t\t<sampleIdentifier>oai:" . $this->x($this->repoId()) . ":article/1</sampleIdentifier>\n\t\t\t</oai-identifier>\n\t\t</description>\n"
            . "\t\t<description>\n\t\t\t<toolkit\n\t\t\t\txmlns=\"http://oai.dlib.vt.edu/OAI/metadata/toolkit\"\n\t\t\t\txsi:schemaLocation=\"http://oai.dlib.vt.edu/OAI/metadata/toolkit\n\t\t\t\t\thttp://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd\">\n"
            . "\t\t\t\t<title>Open Journal Systems</title>\n\t\t\t\t<author>\n\t\t\t\t\t<name>Public Knowledge Project</name>\n\t\t\t\t\t<email>pkp.contact@gmail.com</email>\n\t\t\t\t</author>\n\t\t\t\t<version>3.3.0.22</version>\n\t\t\t\t<URL>http://pkp.sfu.ca/ojs/</URL>\n\t\t\t</toolkit>\n\t\t</description>\n\t</Identify>\n";
        $this->out($x);
    }
    private function formats(array $req): void {
        $this->out($this->head('ListMetadataFormats', $req) . "\t<ListMetadataFormats>\n\t\t<metadataFormat>\n\t\t\t<metadataPrefix>oai_dc</metadataPrefix>\n\t\t\t<schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd</schema>\n\t\t\t<metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/</metadataNamespace>\n\t\t</metadataFormat>\n\t</ListMetadataFormats>\n");
    }
    private function sets(int $jid, array $req): void {
        $dc = fn() => "\t\t\t<setDescription>\n\t\t\t\t<oai_dc:dc\n\t\t\t\t\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n\t\t\t\t\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n\t\t\t\t\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n\t\t\t\t\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n\t\t\t\t\t\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n\t\t\t\t\t<dc:description></dc:description>\n\t\t\t\t</oai_dc:dc>\n\t\t\t</setDescription>\n";
        $path = Settings::journal()['path'];
        $x = $this->head('ListSets', $req) . "\t<ListSets>\n\t\t<set>\n\t\t\t<setSpec>" . $this->x($path) . "</setSpec>\n\t\t\t<setName>" . $this->x(Settings::get('name')) . "</setName>\n" . $dc() . "\t\t</set>\n";
        foreach (DB::all('SELECT title, abbrev FROM sections WHERE journal_id=? ORDER BY seq, id', [$jid]) as $s)
            $x .= "\t\t<set>\n\t\t\t<setSpec>" . $this->x($path . ':' . $s['abbrev']) . "</setSpec>\n\t\t\t<setName>" . $this->x($s['title']) . "</setName>\n" . $dc() . "\t\t</set>\n";
        $this->out($x . "\t</ListSets>\n");
    }
    private function parseId(string $id): ?int { return preg_match('/^oai:' . preg_quote($this->repoId(), '/') . ':article\/(\d+)$/', $id, $m) ? (int)$m[1] : null; }
    private function rows(int $jid, array $f): array {
        $w = ["s.journal_id=?", "s.status='published'", "p.status='published'"]; $a = [$jid];
        if (!empty($f['id'])) { $w[] = 's.id=?'; $a[] = $f['id']; }
        if (!empty($f['from'])) { $w[] = 'COALESCE(p.date_published, s.date_last_activity) >= ?'; $a[] = $f['from']; }
        if (!empty($f['until'])) { $w[] = 'COALESCE(p.date_published, s.date_last_activity) <= ?'; $a[] = $f['until']; }
        if (!empty($f['section'])) { $w[] = 'sec.abbrev=?'; $a[] = $f['section']; }
        $off = (int)($f['offset'] ?? 0);
        return DB::all("SELECT s.id sid, p.*, p.id pid, sec.abbrev secabbrev, sec.title sectitle, COALESCE(p.date_published, s.date_last_activity) stamp, i.volume, i.number, i.year
            FROM submissions s JOIN publications p ON p.id=s.current_publication_id LEFT JOIN sections sec ON sec.id=p.section_id LEFT JOIN issues i ON i.id=p.issue_id
            WHERE " . implode(' AND ', $w) . " ORDER BY s.id LIMIT " . (self::PAGE + 1) . " OFFSET $off", $a);
    }
    private function header(array $r): string {
        $path = Settings::journal()['path'];
        return "\t\t\t<header>\n\t\t\t\t<identifier>oai:" . $this->x($this->repoId()) . ':article/' . (int)$r['sid'] . "</identifier>\n\t\t\t\t<datestamp>" . $this->stamp($r['stamp']) . "</datestamp>\n\t\t\t\t<setSpec>" . $this->x($path . ':' . $r['secabbrev']) . "</setSpec>\n\t\t\t</header>\n";
    }
    private function dc(array $r): string {
        $jname = Settings::get('name'); $eissn = Settings::get('eissn', '');
        $o = "<oai_dc:dc\n\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n";
        $l = fn(string $tag, ?string $v, bool $lang = false) => ($v === null || $v === '') ? '' : "\t<dc:$tag" . ($lang ? ' xml:lang="en-US"' : '') . '>' . $this->x($v) . "</dc:$tag>\n";
        $o .= $l('title', $r['title'], true);
        foreach (DB::all('SELECT given_name, family_name FROM authors WHERE publication_id=? ORDER BY seq, id', [$r['pid']]) as $a) $o .= $l('creator', trim($a['given_name'] . ' ' . $a['family_name']));
        $kw = $r['keywords'] ? implode('; ', (array)json_decode((string)$r['keywords'], true)) : '';
        $o .= $l('subject', $kw, true);
        $abs = trim(strip_tags((string)$r['abstract'])); if ($r['doi']) $abs .= ($abs !== '' ? "\r\n " : '') . 'DOI: https://doi.org/' . $r['doi'];
        $o .= $l('description', $abs, true) . $l('publisher', $jname, true) . $l('date', $r['date_published'] ? substr($r['date_published'], 0, 10) : null);
        $o .= "\t<dc:type>info:eu-repo/semantics/article</dc:type>\n\t<dc:type>info:eu-repo/semantics/publishedVersion</dc:type>\n\t<dc:type xml:lang=\"en-US\">Peer-reviewed Article</dc:type>\n";
        $gal = DB::all('SELECT g.id, g.label, f.mime FROM galleys g LEFT JOIN submission_files f ON f.id=g.submission_file_id WHERE g.publication_id=? ORDER BY g.seq, g.id', [$r['pid']]);
        foreach ($gal as $g) if (($g['mime'] ?? '') === 'application/pdf') { $o .= "\t<dc:format>application/pdf</dc:format>\n"; break; }
        $o .= $l('identifier', abs_url(url('article', 'view', [$r['sid']]))) . $l('identifier', $r['doi']);
        $src = $jname . '; ' . ($r['volume'] !== null && $r['volume'] !== '' ? 'Vol. ' . $r['volume'] : '') . ($r['number'] ? ' No. ' . $r['number'] : '') . ($r['year'] ? ' (' . $r['year'] . ')' : '') . ($r['pages'] ? '; ' . $r['pages'] : '');
        $o .= $l('source', $src, true) . $l('source', $eissn) . "\t<dc:language>eng</dc:language>\n";
        foreach ($gal as $g) $o .= $l('relation', abs_url(url('article', 'view', [$r['sid'], $g['id']])));
        return $o . "</oai_dc:dc>\n";
    }
    private function checkArgs(array $req, array $allowed, array $required, string $verb): bool {
        foreach ($req as $k => $_) if ($k !== 'verb' && !in_array($k, $allowed, true)) { $this->error('badArgument', 'Illegal argument: ' . $k); return false; }
        foreach ($required as $k) if (empty($req[$k])) { $this->error('badArgument', 'Missing argument: ' . $k); return false; }
        return true;
    }
    private function getRecord(int $jid, array $req): void {
        if (!$this->checkArgs($req, ['identifier', 'metadataPrefix'], ['identifier', 'metadataPrefix'], 'GetRecord')) return;
        if (($req['metadataPrefix'] ?? '') !== 'oai_dc') { $this->error('cannotDisseminateFormat', 'The metadata format identified by the value given for the metadataPrefix argument is not supported by the item or by the repository.'); return; }
        $id = $this->parseId((string)$req['identifier']); $rows = $id ? $this->rows($jid, ['id' => $id]) : [];
        if (!$rows) { $this->error('idDoesNotExist', 'The value of the identifier argument is unknown or illegal in this repository.'); return; }
        $this->out($this->head('GetRecord', $req) . "\t<GetRecord>\n\t\t<record>\n" . $this->header($rows[0]) . "\t\t\t<metadata>\n" . $this->dc($rows[0]) . "\t\t\t</metadata>\n\t\t</record>\n\t</GetRecord>\n");
    }
    private function listing(int $jid, string $verb, array $req): void {
        $token = (string)($req['resumptionToken'] ?? '');
        if ($token !== '') {
            $dec = json_decode((string)base64_decode(strtr($token, '-_', '+/')), true);
            if (count($req) > 2 || !is_array($dec) || !isset($dec['offset'])) { $this->error('badResumptionToken', 'The value of the resumptionToken argument is invalid or expired.'); return; }
            $f = array_intersect_key($dec, array_flip(['offset', 'from', 'until', 'section'])); $f['offset'] = (int)$f['offset']; $reqOut = ['verb' => $verb, 'resumptionToken' => $token];
        } else {
            if (!$this->checkArgs($req, ['metadataPrefix', 'from', 'until', 'set'], ['metadataPrefix'], $verb)) return;
            if (($req['metadataPrefix'] ?? '') !== 'oai_dc') { $this->error('cannotDisseminateFormat', 'The metadata format identified by the value given for the metadataPrefix argument is not supported by the item or by the repository.'); return; }
            $f = ['offset' => 0]; $reqOut = $req;
            foreach (['from', 'until'] as $k) if (!empty($req[$k])) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}:\d{2}Z)?$/', $req[$k])) { $this->error('badArgument', 'Illegal date format: ' . $k); return; }
                $f[$k] = str_replace(['T', 'Z'], [' ', ''], $req[$k]) . (strlen($req[$k]) === 10 ? ($k === 'until' ? ' 23:59:59' : ' 00:00:00') : '');
            }
            if (!empty($req['set'])) {
                $path = Settings::journal()['path'];
                if ($req['set'] === $path) {} elseif (strpos((string)$req['set'], $path . ':') === 0) $f['section'] = substr($req['set'], strlen($path) + 1);
                else { $this->error('noRecordsMatch', 'No matching records.'); return; }
            }
        }
        $rows = $this->rows($jid, $f);
        if (!$rows) { $this->error('noRecordsMatch', 'The combination of the values of the from, until, set and metadataPrefix arguments results in an empty list.'); return; }
        $more = count($rows) > self::PAGE; if ($more) array_pop($rows);
        $x = $this->head($verb, $reqOut) . "\t<$verb>\n";
        foreach ($rows as $r) $x .= $verb === 'ListRecords' ? "\t\t<record>\n" . $this->header($r) . "\t\t\t<metadata>\n" . $this->dc($r) . "\t\t\t</metadata>\n\t\t</record>\n" : preg_replace('/^\t\t\t/m', "\t\t", $this->header($r));
        if ($more) {
            $tk = rtrim(strtr(base64_encode(json_encode(['offset' => $f['offset'] + self::PAGE] + array_diff_key($f, ['offset' => 1]))), '+/', '-_'), '=');
            $x .= "		<resumptionToken>" . $tk . "</resumptionToken>
";
        } elseif ($token !== '') $x .= "\t\t<resumptionToken/>\n";
        $this->out($x . "\t</$verb>\n");
    }
}
