<?php

/**
 * A consumer service typed against the transaction port, run against the shipped test double.
 *
 * The service knows nothing about a store: it asks the port to run its work atomically, registers the
 * effect that must wait for durability and the compensation that must follow a discard, and refuses to run
 * inside a scope it does not own. The script needs no `composer install`: it loads the package from
 * `vendor/autoload.php` when that exists (the installed archive) and from `src/` otherwise (a checkout).
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace Kumwe\Transaction\Examples;

use Closure;
use Kumwe\Transaction\Contract\TransactionManager;
use Kumwe\Transaction\Contract\TransactionState;
use Kumwe\Transaction\Testing\ImmediateTransactionManager;
use LogicException;
use RuntimeException;

$root = dirname(__DIR__);
if (is_file($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'Kumwe\\Transaction\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $path = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

/**
 * Posts a ledger entry atomically and notifies only once the entry is durable.
 *
 * @since  0.1.0
 */
final readonly class LedgerPosting
{
    /**
     * Bind the posting to the port the host supplies and to the log the example observes.
     *
     * @param   TransactionManager      $transactions  Port the host binds to its own adapter.
     * @param   TransactionState        $state         Whether a scope is already open on the connection.
     * @param   Closure(string): void   $log           Where the example records what happened, in order.
     *
     * @since   0.1.0
     */
    public function __construct(
        private TransactionManager $transactions,
        private TransactionState $state,
        private Closure $log,
    ) {
    }

    /**
     * Post one entry: the write, the notification and the compensation share the entry's fate.
     *
     * @param   string  $reference  Entry identifier; `INV-0` is refused, to show the failure path.
     *
     * @return  string  Confirmation the caller can show.
     *
     * @throws  LogicException    When a scope is already open: a posting must own its own boundary.
     * @throws  RuntimeException  When the entry is refused; the scope is discarded and nothing was notified.
     *
     * @since   0.1.0
     */
    public function post(string $reference): string
    {
        if ($this->state->isActive()) {
            throw new LogicException('A ledger posting must open its own transaction scope.');
        }

        return $this->transactions->transactional(function () use ($reference): string {
            if ($reference === 'INV-0') {
                throw new RuntimeException('The entry ' . $reference . ' was refused.');
            }
            ($this->log)('write ' . $reference);
            $this->transactions->afterCommit(function () use ($reference): void {
                ($this->log)('notify ' . $reference);
            });
            $this->transactions->afterRollback(function () use ($reference): void {
                ($this->log)('compensate ' . $reference);
            });

            return 'posted ' . $reference;
        });
    }
}

/** @var list<string> $events */
$events = [];
$state = new class implements TransactionState {
    /**
     * Report that no transaction is open, which is what a service outside any scope observes.
     *
     * @return  bool  Always false.
     *
     * @since   0.1.0
     */
    public function isActive(): bool
    {
        return false;
    }
};
$service = new LedgerPosting(
    new ImmediateTransactionManager(),
    $state,
    static function (string $event) use (&$events): void {
        $events[] = $event;
    },
);

echo $service->post('INV-1'), "\n";
try {
    $service->post('INV-0');
} catch (RuntimeException $refusal) {
    echo 'refused: ', $refusal->getMessage(), "\n";
}
foreach ($events as $event) {
    echo $event, "\n";
}
