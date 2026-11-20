<?php

declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "Run this exporter from an installed project with config.php present.\n");
    exit(1);
}

$config = require $configPath;
$outputDir = $argv[1] ?? '';
if ($outputDir === '') {
    fwrite(STDERR, "Usage: php tools/export_ojs_native.php <new-output-directory>\n");
    exit(1);
}
if (file_exists($outputDir)) {
    fwrite(STDERR, "Output path already exists; choose a new directory: {$outputDir}\n");
    exit(1);
}

$filesDir = rtrim((string) $config['files_dir'], DIRECTORY_SEPARATOR);
$db = $config['db'];
$pdo = new PDO(
    "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
    $db['user'],
    $db['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

$journal = $pdo->prepare('SELECT id FROM journals WHERE path = ?');
$journal->execute([$config['default_journal']]);
$journalId = $journal->fetchColumn();
if (!$journalId) {
    throw new RuntimeException('Configured journal was not found in the source database.');
}

$issuesQuery = $pdo->prepare(
    'SELECT id, volume, number, year, title, description, published, is_current, access_status,
            date_published, seq, url_path
     FROM issues
     WHERE journal_id = ? AND published = 1
     ORDER BY seq DESC, id'
);
$issuesQuery->execute([$journalId]);
$issues = $issuesQuery->fetchAll();
if (!$issues) {
    throw new RuntimeException('No published issues were found in the source database.');
}

if (!mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new RuntimeException("Could not create output directory: {$outputDir}");
}
$pdfDir = $outputDir . DIRECTORY_SEPARATOR . 'files';
if (!mkdir($pdfDir, 0775, true) && !is_dir($pdfDir)) {
    throw new RuntimeException("Could not create PDF directory: {$pdfDir}");
}

$ns = 'http://pkp.sfu.ca';
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;
$root = $doc->createElementNS($ns, 'issues');
$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', "{$ns} native.xsd");
$doc->appendChild($root);
$fileCount = 0;
$articleCount = 0;

$appendText = static function (DOMNode $parent, string $name, ?string $value, ?string $locale = null) use ($doc, $ns): ?DOMElement {
    if ($value === null || $value === '') {
        return null;
    }
    $element = $doc->createElementNS($ns, $name);
    if ($locale !== null) {
        $element->setAttribute('locale', $locale);
    }
    $element->appendChild($doc->createTextNode($value));
    $parent->appendChild($element);
    return $element;
};

$appendLocalized = static function (DOMNode $parent, string $name, ?string $value) use ($appendText): void {
    $appendText($parent, $name, $value, 'en_US');
};

$decodeList = static function (?string $value): array {
    if ($value === null || $value === '') {
        return [];
    }
    $items = json_decode($value, true);
    return is_array($items) ? array_values(array_filter($items, 'is_string')) : [];
};

$copyPdf = static function (array $file) use ($filesDir, $pdfDir, &$fileCount): string {
    $storedName = basename((string) $file['stored_name']);
    $source = $filesDir . '/journals/' . (int) $file['journal_id']
        . '/submissions/' . (int) $file['submission_id'] . '/galley/' . $storedName;
    if (!is_file($source) || !is_readable($source)) {
        throw new RuntimeException("Missing source galley file: {$source}");
    }
    if ((int) $file['size'] !== filesize($source)) {
        throw new RuntimeException("Source galley size mismatch: {$source}");
    }

    $name = 'file-' . (int) $file['id'] . '.pdf';
    if (!copy($source, $pdfDir . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException("Could not copy source galley file: {$source}");
    }
    $fileCount++;
    return 'files/' . $name;
};

$articlesQuery = $pdo->prepare(
    "SELECT s.id AS submission_id, s.status AS submission_status, s.date_submitted, s.locale,
            p.*, i.journal_id, g.id AS galley_id, g.label, g.submission_file_id, g.remote_url,
            f.id AS file_id, f.original_name, f.stored_name, f.mime, f.size,
            f.submission_id AS file_submission_id
     FROM publications p
     JOIN submissions s ON s.id = p.submission_id
     JOIN issues i ON i.id = p.issue_id
     LEFT JOIN galleys g ON g.publication_id = p.id
     LEFT JOIN submission_files f ON f.id = g.submission_file_id
     WHERE p.issue_id = ? AND p.status = 'published' AND s.status = 'published'
     ORDER BY p.seq, p.id, g.seq, g.id"
);
$authorsQuery = $pdo->prepare(
    'SELECT id, given_name, family_name, email, country, affiliation, orcid, url, bio,
            include_in_browse, seq
     FROM authors
     WHERE publication_id = ?
     ORDER BY seq, id'
);

foreach ($issues as $issueRow) {
    $issue = $doc->createElementNS($ns, 'issue');
    $issue->setAttribute('published', '1');
    $issue->setAttribute('current', (int) $issueRow['is_current'] === 1 ? '1' : '0');
    $issue->setAttribute('access_status', (string) $issueRow['access_status']);
    if ($issueRow['url_path'] !== null && $issueRow['url_path'] !== '') {
        $issue->setAttribute('url_path', (string) $issueRow['url_path']);
    }
    $root->appendChild($issue);

    if ($issueRow['description'] !== null) {
        $appendLocalized($issue, 'description', (string) $issueRow['description']);
    }

    $identification = $doc->createElementNS($ns, 'issue_identification');
    $issue->appendChild($identification);
    foreach (['volume', 'number', 'year'] as $field) {
        if ($issueRow[$field] !== null && $issueRow[$field] !== '') {
            $appendText($identification, $field, (string) $issueRow[$field]);
        }
    }
    if ($issueRow['title'] !== null && $issueRow['title'] !== '') {
        $appendLocalized($identification, 'title', (string) $issueRow['title']);
    }
    if ($issueRow['date_published'] !== null) {
        $appendText($issue, 'date_published', substr((string) $issueRow['date_published'], 0, 10));
    }

    $articles = $doc->createElementNS($ns, 'articles');
    $issue->appendChild($articles);
    $articlesQuery->execute([(int) $issueRow['id']]);
    $rows = $articlesQuery->fetchAll();
    $publications = [];
    foreach ($rows as $row) {
        $publications[(int) $row['id']][] = $row;
    }

    foreach ($publications as $publicationRows) {
        $articleData = $publicationRows[0];
        if (!$articleData['date_published']) {
            throw new RuntimeException("Published article {$articleData['id']} has no publication date.");
        }

        $article = $doc->createElementNS($ns, 'article');
        $article->setAttribute('locale', 'en_US');
        $article->setAttribute('status', '3');
        $article->setAttribute('stage', 'production');
        $article->setAttribute('date_submitted', substr((string) ($articleData['date_submitted'] ?? $articleData['date_published']), 0, 10));
        $article->setAttribute('current_publication_id', (string) $articleData['id']);
        $articles->appendChild($article);

        $submissionId = $doc->createElementNS($ns, 'id', (string) $articleData['submission_id']);
        $submissionId->setAttribute('type', 'internal');
        $submissionId->setAttribute('advice', 'ignore');
        $article->appendChild($submissionId);

        foreach ($publicationRows as $fileRow) {
            if ($fileRow['file_id'] === null) {
                if ($fileRow['remote_url']) {
                    throw new RuntimeException("Article {$articleData['id']} uses a remote galley, which needs separate handling.");
                }
                throw new RuntimeException("Article {$articleData['id']} has a galley without a stored PDF.");
            }
            if (strtolower((string) $fileRow['mime']) !== 'application/pdf') {
                throw new RuntimeException("Article {$articleData['id']} has a non-PDF galley.");
            }
            $relativePdf = $copyPdf([
                'id' => $fileRow['file_id'],
                'journal_id' => $fileRow['journal_id'],
                'submission_id' => $fileRow['file_submission_id'],
                'stored_name' => $fileRow['stored_name'],
                'size' => $fileRow['size'],
            ]);

            $submissionFile = $doc->createElementNS($ns, 'submission_file');
            $submissionFile->setAttribute('id', (string) $fileRow['file_id']);
            $submissionFile->setAttribute('stage', 'production_ready');
            $submissionFile->setAttribute('viewable', 'true');
            $submissionFile->setAttribute('file_id', (string) $fileRow['file_id']);
            $article->appendChild($submissionFile);
            $appendLocalized($submissionFile, 'name', (string) $fileRow['original_name']);

            $revision = $doc->createElementNS($ns, 'file');
            $revision->setAttribute('id', (string) $fileRow['file_id']);
            $revision->setAttribute('filesize', (string) $fileRow['size']);
            $revision->setAttribute('extension', 'pdf');
            $submissionFile->appendChild($revision);
            $href = $doc->createElementNS($ns, 'href');
            $href->setAttribute('src', $relativePdf);
            $href->setAttribute('mime_type', 'application/pdf');
            $revision->appendChild($href);
        }

        $publication = $doc->createElementNS($ns, 'publication');
        $publication->setAttribute('locale', 'en_US');
        $publication->setAttribute('version', (string) $articleData['version']);
        $publication->setAttribute('status', '3');
        $publication->setAttribute('date_published', (string) $articleData['date_published']);
        $publication->setAttribute('section_ref', 'ART');
        $publication->setAttribute('seq', (string) $articleData['seq']);
        $publication->setAttribute('access_status', (string) $articleData['access_status']);
        if ($articleData['primary_contact_id'] !== null) {
            $publication->setAttribute('primary_contact_id', (string) $articleData['primary_contact_id']);
        }
        $article->appendChild($publication);

        $publicationId = $doc->createElementNS($ns, 'id', (string) $articleData['id']);
        $publicationId->setAttribute('type', 'internal');
        $publicationId->setAttribute('advice', 'ignore');
        $publication->appendChild($publicationId);
        if ($articleData['doi']) {
            $doi = $doc->createElementNS($ns, 'id', (string) $articleData['doi']);
            $doi->setAttribute('type', 'doi');
            $doi->setAttribute('advice', 'update');
            $publication->appendChild($doi);
        }

        $appendLocalized($publication, 'title', (string) $articleData['title']);
        $appendLocalized($publication, 'subtitle', $articleData['subtitle'] === null ? null : (string) $articleData['subtitle']);
        $appendLocalized($publication, 'abstract', $articleData['abstract'] === null ? null : (string) $articleData['abstract']);
        $appendLocalized($publication, 'rights', $articleData['rights'] === null ? null : (string) $articleData['rights']);
        if ($articleData['license_url']) {
            $appendText($publication, 'licenseUrl', (string) $articleData['license_url']);
        }
        $appendLocalized($publication, 'copyrightHolder', $articleData['copyright_holder'] === null ? null : (string) $articleData['copyright_holder']);
        if ($articleData['copyright_year']) {
            $appendText($publication, 'copyrightYear', (string) $articleData['copyright_year']);
        }

        foreach ([
            'keywords' => 'keyword',
            'subjects' => 'subject',
            'disciplines' => 'discipline',
            'agencies' => 'agency',
        ] as $elementName => $itemName) {
            $items = $decodeList($articleData[$elementName]);
            if (!$items) {
                continue;
            }
            $container = $doc->createElementNS($ns, $elementName);
            $container->setAttribute('locale', 'en_US');
            $publication->appendChild($container);
            foreach ($items as $item) {
                $appendText($container, $itemName, $item);
            }
        }

        $authorsQuery->execute([(int) $articleData['id']]);
        $authors = $authorsQuery->fetchAll();
        if (!$authors) {
            throw new RuntimeException("Published article {$articleData['id']} has no authors.");
        }
        $authorsNode = $doc->createElementNS($ns, 'authors');
        $publication->appendChild($authorsNode);
        foreach ($authors as $index => $authorRow) {
            if (trim((string) $authorRow['given_name']) === '') {
                throw new RuntimeException("Author {$authorRow['id']} has no given name.");
            }
            $author = $doc->createElementNS($ns, 'author');
            $author->setAttribute('id', (string) $authorRow['id']);
            $author->setAttribute('seq', (string) $authorRow['seq']);
            $author->setAttribute('user_group_ref', 'Author');
            $author->setAttribute('include_in_browse', (int) $authorRow['include_in_browse'] === 1 ? 'true' : 'false');
            $author->setAttribute(
                'primary_contact',
                $articleData['primary_contact_id'] !== null
                    ? ((int) $authorRow['id'] === (int) $articleData['primary_contact_id'] ? 'true' : 'false')
                    : ($index === 0 ? 'true' : 'false')
            );
            $authorsNode->appendChild($author);
            $appendLocalized($author, 'givenname', (string) $authorRow['given_name']);
            $appendLocalized($author, 'familyname', (string) $authorRow['family_name']);
            $appendLocalized($author, 'affiliation', $authorRow['affiliation'] === null ? null : (string) $authorRow['affiliation']);
            if ($authorRow['country']) {
                $appendText($author, 'country', (string) $authorRow['country']);
            }
            $email = $doc->createElementNS($ns, 'email');
            if ($authorRow['email'] !== null && $authorRow['email'] !== '') {
                $email->appendChild($doc->createTextNode((string) $authorRow['email']));
            }
            $author->appendChild($email);
            if ($authorRow['url']) {
                $appendText($author, 'url', (string) $authorRow['url']);
            }
            if ($authorRow['orcid']) {
                $appendText($author, 'orcid', (string) $authorRow['orcid']);
            }
            $appendLocalized($author, 'biography', $authorRow['bio'] === null ? null : (string) $authorRow['bio']);
        }

        $galleySeq = 0;
        foreach ($publicationRows as $fileRow) {
            $galley = $doc->createElementNS($ns, 'article_galley');
            $galley->setAttribute('locale', 'en_US');
            $galley->setAttribute('approved', 'true');
            $publication->appendChild($galley);
            $appendLocalized($galley, 'name', (string) $fileRow['label']);
            $appendText($galley, 'seq', (string) ++$galleySeq);
            $fileRef = $doc->createElementNS($ns, 'submission_file_ref');
            $fileRef->setAttribute('id', (string) $fileRow['file_id']);
            $galley->appendChild($fileRef);
        }

        if ($articleData['references_text']) {
            $citations = $doc->createElementNS($ns, 'citations');
            $citations->setAttribute('locale', 'en_US');
            $publication->appendChild($citations);
            $appendText($citations, 'citation', (string) $articleData['references_text']);
        }

        $issueIdentification = $doc->createElementNS($ns, 'issue_identification');
        $publication->appendChild($issueIdentification);
        foreach (['volume', 'number', 'year'] as $field) {
            if ($issueRow[$field] !== null && $issueRow[$field] !== '') {
                $appendText($issueIdentification, $field, (string) $issueRow[$field]);
            }
        }
        if ($issueRow['title'] !== null && $issueRow['title'] !== '') {
            $appendLocalized($issueIdentification, 'title', (string) $issueRow['title']);
        }
        $appendText($publication, 'pages', $articleData['pages'] === null ? null : (string) $articleData['pages']);
        $articleCount++;
    }
}

if (!$doc->save($outputDir . DIRECTORY_SEPARATOR . 'import.xml')) {
    throw new RuntimeException('Could not write the OJS Native XML import file.');
}

printf(
    "Exported %d published issues, %d published articles, and %d PDF files to %s\n",
    count($issues),
    $articleCount,
    $fileCount,
    $outputDir
);
