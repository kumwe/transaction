<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Tests\TestCase;

/**
 * Holds the three package manifests to the package's own claims and to the verifier that regenerates them.
 *
 * @since  0.1.0
 */
final class ManifestsTest extends TestCase
{
    /**
     * The canonical public symbols, and nothing else.
     *
     * @var    list<string>
     * @since  0.1.0
     */
    private const array SYMBOLS = [
        'Kumwe\\Transaction\\Contract\\TransactionManager',
        'Kumwe\\Transaction\\Contract\\TransactionState',
        'Kumwe\\Transaction\\Testing\\ImmediateTransactionManager',
    ];

    /**
     * The public API manifest pins exactly the three canonical symbols under the canonical namespace.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testThePublicApiManifestPinsExactlyTheCanonicalSymbols(): void
    {
        $manifest = $this->json('resources/public-api/v1.json');
        $symbols = $manifest['symbols'] ?? null;
        $names = is_array($symbols) ? array_keys($symbols) : [];
        sort($names);

        $this->assertSame('kumwe-package-public-api/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertSame('kumwe/transaction', $manifest['package'] ?? null, 'The package coordinate is pinned.');
        $this->assertSame('Kumwe\\Transaction\\', $manifest['namespace'] ?? null, 'The canonical namespace.');
        $this->assertSame(self::SYMBOLS, $names, 'Exactly the three canonical symbols are exported.');
        $this->assertSame(
            ['Kumwe\\Transaction\\Contract\\TransactionManager', 'Kumwe\\Transaction\\Contract\\TransactionState'],
            $manifest['extension_points'] ?? null,
            'The two contracts are the extension points; the double is not.',
        );
        $this->assertSame('src', $manifest['digest_of'] ?? null, 'The manifest digests the source tree.');
    }

    /**
     * The three manifests agree with each other and with the newest changelog heading on the release.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheManifestsAgreeWithTheChangelogOnTheRelease(): void
    {
        $heading = null;
        foreach (explode("\n", $this->read('CHANGELOG.md')) as $line) {
            if (str_starts_with($line, '## ')) {
                $heading = $line;
                break;
            }
        }
        $this->assertTrue(is_string($heading), 'The changelog carries a second-level heading.');
        $this->assertTrue(
            preg_match('/^## [0-9]+\.[0-9]+\.[0-9]+$/', (string) $heading) === 1,
            'The newest changelog heading is a release record, which release-on-record publishes.',
        );
        $release = substr((string) $heading, 3);

        foreach (['public-api', 'capabilities', 'service-map'] as $manifest) {
            $this->assertSame(
                $release,
                $this->json('resources/' . $manifest . '/v1.json')['release'] ?? null,
                'resources/' . $manifest . '/v1.json records the changelog release.',
            );
        }
    }

    /**
     * Every capability names exported symbols, and the testing capability is confined to the test double.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testCapabilitiesCoverTheSymbolsAndConfineTestSupport(): void
    {
        $manifest = $this->json('resources/capabilities/v1.json');
        $capabilities = $manifest['capabilities'] ?? null;
        $byId = [];
        foreach (is_array($capabilities) ? $capabilities : [] as $capability) {
            if (is_array($capability) && is_string($capability['id'] ?? null)) {
                $byId[$capability['id']] = $capability;
            }
        }
        ksort($byId);

        $this->assertSame('kumwe-package-capabilities/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertSame(
            ['transaction.boundary', 'transaction.state', 'transaction.testing'],
            array_keys($byId),
            'The package declares exactly three capabilities.',
        );
        $this->assertSame(
            ['Kumwe\\Transaction\\Contract\\TransactionManager'],
            $byId['transaction.boundary']['symbols'] ?? null,
            'The boundary capability is the manager contract alone.',
        );
        $this->assertSame(
            ['Kumwe\\Transaction\\Contract\\TransactionState'],
            $byId['transaction.state']['symbols'] ?? null,
            'The state capability is the state contract alone.',
        );
        $this->assertSame(
            ['Kumwe\\Transaction\\Testing\\ImmediateTransactionManager'],
            $byId['transaction.testing']['symbols'] ?? null,
            'The testing capability is the double alone.',
        );
        $description = $byId['transaction.testing']['description'] ?? null;
        $description = is_string($description) ? $description : '';
        $this->assertTrue($description !== '', 'The testing capability is described.');
        $this->assertStringContains('test suites', $description, 'It says who it is for.');
        $this->assertStringContains('never', $description, 'It says it is never a production implementation.');
        $this->assertTrue(
            array_key_exists('native_requirements', $manifest) && $manifest['native_requirements'] === null,
            'No native extension is required.',
        );
        $this->assertSame([], $manifest['deprecations'] ?? null, 'Nothing is deprecated in the first release.');
    }

    /**
     * The service map declares no provider, no factory and no alias, and records why.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheServiceMapDeclaresNoProviderWithAReason(): void
    {
        $manifest = $this->json('resources/service-map/v1.json');
        $reason = $manifest['provider_absence_reason'] ?? null;
        $reason = is_string($reason) ? trim($reason) : '';

        $this->assertSame('kumwe-package-service-map/v1', $manifest['schema'] ?? null, 'Version 2 schema.');
        $this->assertTrue(array_key_exists('config_provider', $manifest), 'The provider decision is recorded.');
        $this->assertSame(null, $manifest['config_provider'], 'A contracts package ships no ConfigProvider.');
        $this->assertTrue($reason !== '', 'The absence carries its reason.');
        $this->assertStringContains('binds', $reason, 'The reason says the host binds its adapter.');
        $this->assertSame([], $manifest['factories'] ?? null, 'No factory.');
        $this->assertSame([], $manifest['aliases'] ?? null, 'No alias.');
        $this->assertSame([], $manifest['delegators'] ?? null, 'No delegator.');
        $this->assertSame([], $manifest['configuration_keys'] ?? null, 'Nothing to configure.');
    }

    /**
     * The verifier regenerates the public API manifest byte for byte and accepts the hand-authored manifests.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheVerifierAcceptsTheRecordedManifests(): void
    {
        $result = $this->runScript(['tools/verify-manifests.php']);

        $this->assertSame(0, $result['status'], "The manifest verifier must accept the record.\n" . $result['output']);
        $this->assertStringContains('3 public symbols', $result['output'], 'The full surface is verified.');
        $this->assertStringContains('3 capabilities', $result['output'], 'Every capability is verified.');
        $this->assertStringContains('no provider (reason recorded)', $result['output'], 'The DI decision is verified.');
    }

    /**
     * No manifest carries a host, driver or historical name.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheManifestsCarryNoHostDriverOrHistoricalName(): void
    {
        foreach (['public-api', 'capabilities', 'service-map'] as $manifest) {
            $bytes = $this->read('resources/' . $manifest . '/v1.json');
            $this->assertStringExcludes('Kumwe\\\\App\\\\', $bytes, 'No App namespace survives extraction.');
            $this->assertStringExcludes('Doctrine', $bytes, 'No driver is named by a contracts manifest.');
        }
    }
}
