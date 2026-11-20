<?php

declare(strict_types=1);

$ojsRoot = dirname(__DIR__);
$bootstrapPath = $ojsRoot . '/tools/bootstrap.inc.php';
if (!is_file($bootstrapPath)) {
    fwrite(STDERR, "Run this script from an OJS installation after copying it into OJS's tools directory.\n");
    exit(1);
}

$importPath = $argv[1] ?? '';
$journalPath = $argv[2] ?? '';
if ($importPath === '' || $journalPath === '') {
    fwrite(STDERR, "Usage: php tools/ojs_post_import.php <import.xml> <journalPath> [doiPrefix]\n");
    exit(1);
}
$requestedDoiPrefix = $argv[3] ?? '';

chdir($ojsRoot);
require $bootstrapPath;

$xmlPath = realpath($importPath);
if ($xmlPath === false || !is_file($xmlPath)) {
    throw new RuntimeException("Import XML file was not found: {$importPath}");
}

$document = new DOMDocument();
if (!$document->load($xmlPath, LIBXML_NONET)) {
    throw new RuntimeException("Could not read import XML: {$xmlPath}");
}

$namespace = 'http://pkp.sfu.ca';
$childText = static function (DOMElement $parent, string $name) use ($namespace): ?string {
    foreach ($parent->childNodes as $child) {
        if ($child instanceof DOMElement && $child->namespaceURI === $namespace && $child->localName === $name) {
            return trim($child->textContent);
        }
    }

    return null;
};

$journalDao = DAORegistry::getDAO('JournalDAO');
$journal = $journalDao->getByPath($journalPath);
if (!$journal) {
    throw new RuntimeException("OJS journal path was not found: {$journalPath}");
}
$journalId = (int) $journal->getId();

$currentIssueData = null;
$doiRecords = [];
$issueNodes = $document->getElementsByTagNameNS($namespace, 'issue');
foreach ($issueNodes as $issueNode) {
    if (!$issueNode instanceof DOMElement) {
        continue;
    }

    if ($issueNode->getAttribute('current') === '1') {
        $identification = null;
        foreach ($issueNode->childNodes as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === $namespace && $child->localName === 'issue_identification') {
                $identification = $child;
                break;
            }
        }
        if (!$identification) {
            throw new RuntimeException('The current issue has no issue_identification element.');
        }
        if ($currentIssueData !== null) {
            throw new RuntimeException('The import XML marks more than one issue as current.');
        }
        $currentIssueData = [
            'volume' => $childText($identification, 'volume'),
            'number' => $childText($identification, 'number'),
            'year' => $childText($identification, 'year'),
            'title' => $childText($identification, 'title'),
        ];
    }

    foreach ($issueNode->getElementsByTagNameNS($namespace, 'publication') as $publicationNode) {
        if (!$publicationNode instanceof DOMElement) {
            continue;
        }
        $doi = null;
        foreach ($publicationNode->childNodes as $child) {
            if (
                $child instanceof DOMElement
                && $child->namespaceURI === $namespace
                && $child->localName === 'id'
                && $child->getAttribute('type') === 'doi'
            ) {
                $doi = trim($child->textContent);
                break;
            }
        }
        if (!$doi) {
            continue;
        }

        $title = $childText($publicationNode, 'title');
        if (!$title) {
            throw new RuntimeException("A publication with DOI {$doi} has no title.");
        }

        $doi = preg_replace('#^https?://doi\.org/#i', '', $doi);
        $prefix = explode('/', $doi, 2)[0];
        if (!preg_match('/^10\.\d{4,7}$/', $prefix)) {
            throw new RuntimeException("Invalid DOI prefix in identifier: {$doi}");
        }
        $doiRecords[] = ['title' => $title, 'doi' => $doi, 'prefix' => $prefix];
    }
}

$currentMatches = [];
if ($currentIssueData !== null) {
    $issueDao = DAORegistry::getDAO('IssueDAO');
    foreach ($issueDao->getIssues($journalId)->toArray() as $issue) {
        $matches = (string) $issue->getVolume() === (string) ($currentIssueData['volume'] ?? '')
            && (string) $issue->getNumber() === (string) ($currentIssueData['number'] ?? '')
            && (string) $issue->getYear() === (string) ($currentIssueData['year'] ?? '');
        if ($matches && $currentIssueData['title'] !== null && $currentIssueData['title'] !== '') {
            $matches = $issue->getLocalizedTitle() === $currentIssueData['title'];
        }
        if ($matches) {
            $currentMatches[] = $issue;
        }
    }
    if (count($currentMatches) !== 1) {
        throw new RuntimeException('Could not uniquely match the current issue from the import XML.');
    }
}

if ($doiRecords) {
    $prefixes = array_values(array_unique(array_column($doiRecords, 'prefix')));

    import('lib.pkp.classes.site.VersionCheck');
    $versionDao = DAORegistry::getDAO('VersionDAO');
    if (!$versionDao->getCurrentVersion('plugins.pubIds', 'doi', true)) {
        $pluginVersion = VersionCheck::getValidPluginVersionInfo('plugins/pubIds/doi/version.xml');
        if (!$pluginVersion) {
            throw new RuntimeException('Could not read the OJS DOI plugin version descriptor.');
        }
        $versionDao->insertVersion($pluginVersion, true);
    }

    $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
    $pluginName = 'DOIPubIdPlugin';
    $configuredPrefix = $pluginSettingsDao->getSetting($journalId, $pluginName, 'doiPrefix');
    if ($configuredPrefix && $requestedDoiPrefix && $configuredPrefix !== $requestedDoiPrefix) {
        throw new RuntimeException("The OJS DOI prefix {$configuredPrefix} does not match the requested prefix {$requestedDoiPrefix}.");
    }
    $doiPrefix = $requestedDoiPrefix ?: ($configuredPrefix ?: (count($prefixes) === 1 ? $prefixes[0] : ''));
    if (!$doiPrefix) {
        throw new RuntimeException('The import contains multiple DOI prefixes; provide the journal DOI prefix as the third argument.');
    }
    if (!preg_match('/^10\.\d{4,7}$/', $doiPrefix)) {
        throw new RuntimeException("Invalid configured DOI prefix: {$doiPrefix}");
    }
    $pluginSettingsDao->updateSetting($journalId, $pluginName, 'enabled', 1, 'bool');
    $pluginSettingsDao->updateSetting($journalId, $pluginName, 'doiPrefix', $doiPrefix, 'string');
    $pluginSettingsDao->updateSetting($journalId, $pluginName, 'enablePublicationDoi', 1, 'bool');

    $publicationDao = DAORegistry::getDAO('PublicationDAO');
    $seenPublicationIds = [];
    foreach ($doiRecords as $record) {
        $publicationIds = $publicationDao->getIdsBySetting('title', $record['title'], $journalId);
        if (count($publicationIds) !== 1) {
            throw new RuntimeException("Could not uniquely match publication title: {$record['title']}");
        }
        $publicationId = (int) $publicationIds[0];
        if (isset($seenPublicationIds[$publicationId])) {
            throw new RuntimeException("More than one DOI in the import XML matches publication {$publicationId}.");
        }
        $seenPublicationIds[$publicationId] = true;

        $publication = $publicationDao->getById($publicationId);
        $storedDoi = $publication->getStoredPubId('doi');
        if ($storedDoi && $storedDoi !== $record['doi']) {
            throw new RuntimeException("Publication {$publicationId} already has a different DOI.");
        }
        $publicationDao->changePubId($publicationId, 'doi', $record['doi']);
    }
}

if ($currentMatches) {
    $currentIssue = $currentMatches[0];
    $currentIssue->setCurrent(1);
    DAORegistry::getDAO('IssueDAO')->updateCurrent($journalId, $currentIssue);
}

printf(
    "Updated %d publication DOIs and %s current issue for journal %s.\n",
    count($doiRecords),
    $currentMatches ? 'set the' : 'did not change the',
    $journalPath
);
