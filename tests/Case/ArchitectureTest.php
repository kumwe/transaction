<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Proves the package boundary from the outside: no host, driver or container reaches the source tree, the
 * contract layer stays independent of test support, the runtime requirement is PHP alone, and the release
 * archive ships what the Kumwe App adoption gate reads.
 *
 * @since  0.1.0
 */
final class ArchitectureTest extends TestCase
{
    /**
     * Paths the App adoption and clean-consumer gates read from the release archive.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array SHIPPED = [
        'CHANGELOG.md',
        'CHARTER.md',
        'LICENSE',
        'MIGRATION-HANDOFF.md',
        'README.md',
        'composer.json',
        'docs',
        'examples',
        'resources',
        'src',
    ];

    /**
     * The architecture verifier accepts the source tree.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheArchitectureVerifierAcceptsTheSourceTree(): void
    {
        $result = $this->runScript(['tools/verify-architecture.php']);

        $this->assertSame(0, $result['status'], "The architecture verifier must pass.\n" . $result['output']);
        $this->assertStringContains('3 source files', $result['output'], 'Every source file was inspected.');
    }

    /**
     * No source file names a driver, the host, a container or a framework, in code or in documentation.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testNoSourceFileNamesAHostDriverContainerOrFramework(): void
    {
        foreach ($this->sourceFiles() as $relative => $code) {
            foreach (['Doctrine', 'Kumwe\\App', 'Psr\\', 'Laminas', 'Mezzio', 'PDO', 'Symfony'] as $forbidden) {
                $this->assertStringExcludes($forbidden, $code, $relative . ' must stay storage-neutral.');
            }
            $this->assertStringContains('declare(strict_types=1);', $code, $relative . ' enables strict types.');
        }
    }

    /**
     * The contract layer never depends on the test-support layer.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheContractLayerDoesNotDependOnTestSupport(): void
    {
        foreach ($this->sourceFiles() as $relative => $code) {
            if (str_starts_with($relative, 'src/Contract/')) {
                $this->assertStringExcludes('Testing', $code, $relative . ' must not know the test double.');
            }
        }
    }

    /**
     * The runtime requirement is PHP alone and the license is the repository's Apache-2.0.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheRuntimeRequirementIsPhpAlone(): void
    {
        $composer = $this->json('composer.json');
        $require = $composer['require'] ?? null;

        $this->assertSame('kumwe/transaction', $composer['name'] ?? null, 'The Composer coordinate.');
        $this->assertSame('Apache-2.0', $composer['license'] ?? null, 'The repository license is preserved.');
        $this->assertSame(['php'], is_array($require) ? array_keys($require) : null, 'PHP alone.');
        $this->assertSame('^8.5', is_array($require) ? ($require['php'] ?? null) : null, 'The supported range.');
        $this->assertSame(
            ['psr-4' => ['Kumwe\\Transaction\\' => 'src/']],
            $composer['autoload'] ?? null,
            'One canonical PSR-4 root and no alias root.',
        );
    }

    /**
     * The release archive ships every path the adoption gate reads and hides every development path.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheReleaseArchiveShipsWhatTheAdoptionGateReads(): void
    {
        $ignored = [];
        foreach (explode("\n", $this->read('.gitattributes')) as $line) {
            if (preg_match('#^/(\S+) export-ignore$#', trim($line), $match) === 1) {
                $ignored[] = $match[1];
            }
        }

        foreach (self::SHIPPED as $path) {
            $this->assertFalse(in_array($path, $ignored, true), $path . ' must ship in the release archive.');
        }
        foreach (['tests', 'tools', '.github', 'phpcs.xml', 'phpstan.neon', 'vendor', 'composer.lock'] as $path) {
            $this->assertTrue(in_array($path, $ignored, true), $path . ' must stay out of the release archive.');
        }
    }

    /**
     * Every source file with its repository-relative path.
     *
     * @return  array<string, string>  Path under the repository mapped to the file contents.
     *
     * @since   0.1.0
     */
    private function sourceFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root() . '/src', RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $relative = substr($file->getPathname(), strlen($this->root()) + 1);
                $files[$relative] = $this->read($relative);
            }
        }
        ksort($files);
        $this->assertSame(3, count($files), 'The source tree holds exactly the three canonical files.');

        return $files;
    }
}
