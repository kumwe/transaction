<?php

declare(strict_types=1);

namespace Kumwe\Transaction\Testing;

use Kumwe\Transaction\Contract\TransactionManager;

/**
 * Executes consumer test-suite transactions inline, without persistence and without ever rolling back.
 *
 * This is test support for the suites of packages and hosts that type against `TransactionManager`, so a
 * service under unit test can be constructed without a store. It is not a transaction implementation and
 * must never be registered in a container or reached by production code: nothing is durable, an operation
 * runs the moment it is handed over, a commit hook runs the moment it is registered — inside a scope as
 * well as outside one — and a rollback hook is discarded, because no scope of this double is ever
 * discarded. A test that needs the nesting, deferral or compensation rules of the contract needs a scoped
 * double of its own; this one deliberately has no state to observe.
 *
 * @since  0.1.0
 */
final readonly class ImmediateTransactionManager implements TransactionManager
{
    /**
     * Run the operation at once and hand its result straight back.
     *
     * Whatever the operation throws reaches the caller unchanged, exactly as the contract promises for a
     * real adapter; nothing is caught, wrapped or reclassified.
     *
     * @template T
     *
     * @param   callable(): T  $operation  Work to perform.
     *
     * @return  T  Whatever the operation returned, passed straight back.
     *
     * @since   0.1.0
     */
    public function transactional(callable $operation): mixed
    {
        return $operation();
    }

    /**
     * Run the commit hook at once, whether or not a scope is open.
     *
     * A unit test wants the effect now, not after a commit that never happens here; a test that must
     * prove deferral until the outermost commit needs a scoped double instead.
     *
     * @param   callable(): void  $operation  Side effect the service registered.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function afterCommit(callable $operation): void
    {
        $operation();
    }

    /**
     * Discard the rollback hook, because this double never discards a scope.
     *
     * @param   callable(): void  $operation  Compensating action that will never be needed here.
     *
     * @return  void
     *
     * @since   0.1.0
     */
    public function afterRollback(callable $operation): void
    {
    }
}
