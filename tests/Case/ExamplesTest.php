<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Tests\TestCase;

/**
 * Proves the shipped example is runnable and truthful.
 *
 * @since  0.1.0
 */
final class ExamplesTest extends TestCase
{
    /**
     * The typed consumer runs against the test double with the exact output its README describes.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheTypedConsumerRunsAgainstTheTestDouble(): void
    {
        $result = $this->runScript(['examples/typed-consumer.php']);

        $this->assertSame(0, $result['status'], "The example must run.\n" . $result['output']);
        $this->assertSame(
            "posted INV-1\nrefused: The entry INV-0 was refused.\nwrite INV-1\nnotify INV-1",
            $result['output'],
            'The example output is deterministic and matches its README.',
        );
    }

    /**
     * The example types against the port, never against a driver or the host.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheExampleTypesAgainstThePortAlone(): void
    {
        $code = $this->read('examples/typed-consumer.php');

        $this->assertStringContains('private TransactionManager $transactions', $code, 'Typed against the port.');
        $this->assertStringContains('private TransactionState $state', $code, 'Typed against the state view.');
        $this->assertStringExcludes('Doctrine', $code, 'No driver.');
        $this->assertStringExcludes('Kumwe\\App', $code, 'No host.');
    }
}
