<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Tests\Case;

use Kumwe\Transaction\Contract\TransactionManager;
use Kumwe\Transaction\Testing\ImmediateTransactionManager;
use Kumwe\Transaction\Tests\TestCase;
use ReflectionClass;
use RuntimeException;
use stdClass;
use TypeError;

/**
 * Proves the consumer test double behaves exactly as documented: inline, stateless, never rolling back.
 *
 * These are the promises a consumer's unit tests rely on when they construct a service around the double,
 * including the deliberate non-promises — no deferral, no compensation — that distinguish it from a real
 * adapter and from a scoped double.
 *
 * @since  0.1.0
 */
final class ImmediateTransactionManagerTest extends TestCase
{
    /**
     * The double answers the port, is final and readonly, and holds no state at all.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testItIsTheFinalReadonlyStatelessDoubleOfThePort(): void
    {
        $reflection = new ReflectionClass(ImmediateTransactionManager::class);

        $this->assertTrue($reflection->implementsInterface(TransactionManager::class), 'It answers the port.');
        $this->assertTrue($reflection->isFinal(), 'A test double is not an extension point.');
        $this->assertTrue($reflection->isReadOnly(), 'The double is declared readonly.');
        $this->assertSame([], $reflection->getProperties(), 'There is no state to observe.');
        $this->assertSame('Kumwe\\Transaction\\Testing', $reflection->getNamespaceName(), 'Test support lives apart.');
    }

    /**
     * Whatever the operation returns comes straight back, the same value and the same instance.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testTheOperationResultIsReturnedUnchanged(): void
    {
        $double = new ImmediateTransactionManager();
        $object = new stdClass();

        $this->assertSame($object, $double->transactional(static fn (): stdClass => $object), 'Same instance.');
        $this->assertSame(null, $double->transactional(static fn (): mixed => null), 'null passes through.');
        $this->assertSame(false, $double->transactional(static fn (): bool => false), 'false passes through.');
        $this->assertSame(0, $double->transactional(static fn (): int => 0), 'Zero passes through.');
        $this->assertSame('', $double->transactional(static fn (): string => ''), 'An empty string passes through.');
        $this->assertSame(['k' => 1], $double->transactional(static fn (): array => ['k' => 1]), 'Arrays pass.');
    }

    /**
     * A failure reaches the caller as the very instance the operation threw, whatever its hierarchy.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testAFailureReachesTheCallerAsTheSameInstance(): void
    {
        $double = new ImmediateTransactionManager();
        $refusal = new RuntimeException('the posting was refused');
        $engine = new TypeError('the engine failed');

        $caughtRefusal = $this->assertThrows(
            static fn (): mixed => $double->transactional(static function () use ($refusal): never {
                throw $refusal;
            }),
            RuntimeException::class,
            'An exception must leave the double.',
        );
        $caughtEngine = $this->assertThrows(
            static fn (): mixed => $double->transactional(static function () use ($engine): never {
                throw $engine;
            }),
            TypeError::class,
            'An error must leave the double too.',
        );

        $this->assertSame($refusal, $caughtRefusal, 'The exception is neither wrapped nor replaced.');
        $this->assertSame($engine, $caughtEngine, 'The error is neither wrapped nor replaced.');
    }

    /**
     * A commit hook runs the moment it is registered, inside a scope as well as outside one.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testACommitHookRunsAtOnceInsideAndOutsideAScope(): void
    {
        $double = new ImmediateTransactionManager();
        /** @var list<string> $events */
        $events = [];

        $double->transactional(function () use ($double, &$events): void {
            $events[] = 'write';
            $double->afterCommit(static function () use (&$events): void {
                $events[] = 'commit-hook';
            });
            $events[] = 'after-registration';
        });
        $events[] = 'returned';
        $this->assertSame(['write', 'commit-hook', 'after-registration', 'returned'], $events, 'Inline inside.');

        $events = [];
        $double->afterCommit(static function () use (&$events): void {
            $events[] = 'commit-hook';
        });
        $this->assertSame(['commit-hook'], $events, 'Inline outside a scope, as the contract itself promises.');
    }

    /**
     * A rollback hook is discarded whether registered outside a scope, in one that succeeds, or in one that fails.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testARollbackHookIsAlwaysDiscarded(): void
    {
        $double = new ImmediateTransactionManager();
        /** @var list<string> $events */
        $events = [];
        $compensate = static function () use (&$events): void {
            $events[] = 'compensated';
        };

        $double->afterRollback($compensate);
        $double->transactional(static function () use ($double, $compensate): void {
            $double->afterRollback($compensate);
        });
        $this->assertThrows(
            static fn (): mixed => $double->transactional(static function () use ($double, $compensate): never {
                $double->afterRollback($compensate);
                throw new RuntimeException('refused');
            }),
            RuntimeException::class,
            'The failing scope must still fail.',
        );

        $this->assertSame([], $events, 'No scope of the double is ever discarded, so nothing is compensated.');
    }

    /**
     * Nested calls run inline, each returning its own value, with no bookkeeping in between.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testNestedCallsRunInlineAndReturnTheirOwnValues(): void
    {
        $double = new ImmediateTransactionManager();

        $outer = $double->transactional(static function () use ($double): string {
            $inner = $double->transactional(static fn (): string => 'inner');

            return $inner . '+outer';
        });

        $this->assertSame('inner+outer', $outer, 'Each scope hands back its own operation result.');
    }

    /**
     * The double defers nothing: a commit hook registered before a failure has already run.
     *
     * A scoped adapter would never let that hook fire; a consumer test that needs the deferral rule must
     * use a scoped double, which is why this non-promise is pinned rather than left implicit.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function testACommitHookRegisteredBeforeAFailureHasAlreadyRun(): void
    {
        $double = new ImmediateTransactionManager();
        /** @var list<string> $events */
        $events = [];

        $operation = static function () use ($double, &$events): never {
            $double->afterCommit(static function () use (&$events): void {
                $events[] = 'notified';
            });
            throw new RuntimeException('refused after registering the hook');
        };
        $this->assertThrows(
            static fn (): mixed => $double->transactional($operation),
            RuntimeException::class,
            'The failing scope must still fail.',
        );

        $this->assertSame(['notified'], $events, 'The hook ran inline; the double does not defer to a commit.');
    }
}
