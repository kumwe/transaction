<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Tests\TestCase;

/**
 * Proves the documents a consumer reads are complete and internally linked.
 *
 * @since  0.1.0
 */
final class DocumentationTest extends TestCase
{
    /**
     * Documents whose relative links must resolve inside the repository.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array DOCUMENTS = [
        'CHANGELOG.md',
        'CHARTER.md',
        'README.md',
        'docs/architecture.md',
        'docs/integration.md',
        'docs/public-api.md',
        'docs/releasing.md',
        'docs/security.md',
        'examples/README.md',
    ];

    /**
     * Headings the README owes a consumer so the capability is discoverable without reading the source.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array README_SECTIONS = [
        '## Responsibility and non-goals',
        '## Installation and supported platforms',
        '## Canonical namespace',
        '## Five-minute example',
        '## Dependency injection: no provider, by design',
        '## Public surface',
        '## Guarantees',
        '## Extension and replacement points',
        '## Migration from Kumwe App',
        '## Testing and clean-consumer commands',
        '## Release, compatibility, security and license',
    ];

    /**
     * Every relative link in every document resolves to a file in the repository.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testEveryRelativeLinkResolves(): void
    {
        $documents = self::DOCUMENTS;
        if (is_file($this->root() . '/MIGRATION-HANDOFF.md')) {
            $documents[] = 'MIGRATION-HANDOFF.md';
        }
        foreach ($documents as $document) {
            preg_match_all('/\]\(([^)\s]+)\)/', $this->read($document), $matches);
            foreach ($matches[1] as $target) {
                if (preg_match('/^(https?:|mailto:|#)/', $target) === 1) {
                    continue;
                }
                $path = explode('#', $target, 2)[0];
                $resolved = dirname($this->root() . '/' . $document) . '/' . $path;
                $this->assertTrue(is_file($resolved), $document . ' links ' . $target . ', which does not exist.');
            }
        }
    }

    /**
     * The README carries every section the package standard requires, in order.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheReadmeCarriesEveryRequiredSectionInOrder(): void
    {
        $readme = $this->read('README.md');
        $offset = 0;
        foreach (self::README_SECTIONS as $heading) {
            $position = strpos($readme, "\n" . $heading . "\n", $offset);
            $this->assertTrue($position !== false, 'README.md must carry "' . $heading . '" after the previous one.');
            $offset = $position === false ? $offset : $position + 1;
        }
    }

    /**
     * The charter opens with the one-paragraph responsibility the Kumwe App capability index reads.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheCharterOpensWithTheResponsibilityParagraph(): void
    {
        $lines = explode("\n", $this->read('CHARTER.md'));
        $paragraph = [];
        $afterHeading = false;
        foreach ($lines as $line) {
            if (!$afterHeading) {
                $afterHeading = str_starts_with($line, '# ');
                continue;
            }
            if (trim($line) === '') {
                if ($paragraph !== []) {
                    break;
                }
                continue;
            }
            $paragraph[] = trim($line);
        }
        $summary = implode(' ', $paragraph);

        $this->assertStringContains(
            'storage-neutral transaction port',
            $summary,
            'The summary states the responsibility.',
        );
        $this->assertStringContains(
            'ships no transaction implementation',
            $summary,
            'The summary states the non-goal.',
        );
    }
}
