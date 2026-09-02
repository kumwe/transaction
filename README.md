# Kumwe Transaction

**The storage-neutral transaction port. Services type against the contract; the host binds its adapter.**

Kumwe Transaction defines the transaction boundary an application service in the
[Kumwe](https://github.com/kumwe) family composes over: `TransactionManager`, which runs work atomically
and defers side effects until that work is durable, and `TransactionState`, the read-only view of whether
a transaction is already open. The package ships **no transaction implementation of any kind**: a Doctrine
DBAL adapter, its nesting and retry policy, its logging and its audit and outbox coordination all stay in
the host that owns the connection.

## Responsibility and non-goals

This package owns:

- the two contracts and their semantics — nesting invisible to the caller, commit hooks deferred to the
  outermost commit, rollback hooks fired when the registering scope is discarded, exceptions propagated
  unchanged;
- their complete documentation and the three machine-readable manifests;
- the contract tests that pin the shapes and the documented semantics;
- one explicitly test-scoped double, `Testing\ImmediateTransactionManager`, for consumer test suites.

It does not own, and will refuse:

- any transaction implementation, driver adapter, connection, savepoint or isolation-level handling;
- nested-transaction, retry, deadlock or timeout policy;
- logging, audit, outbox or event coordination around a transaction;
- container registration: there is no `ConfigProvider`, factory or alias here (see below);
- real-database evidence, which the host proves on the engines it supports.

## Installation and supported platforms

```bash
composer require kumwe/transaction
```

PHP `^8.5` and nothing else: the runtime requirement is `php` alone, with no extension and no Composer
dependency. While the package is pre-1.0 a consumer pins an exact version, as
[`docs/releasing.md`](docs/releasing.md) explains.

## Canonical namespace

`Kumwe\Transaction\` is the one canonical root, autoloaded PSR-4 from `src/`. Contracts live under
`Kumwe\Transaction\Contract\`, test support under `Kumwe\Transaction\Testing\`. There is no alias,
historical namespace or compatibility root.

## Five-minute example

```php
<?php

declare(strict_types=1);

use Kumwe\Transaction\Contract\TransactionManager;
use Kumwe\Transaction\Contract\TransactionState;

final readonly class LedgerPosting
{
    public function __construct(
        private TransactionManager $transactions,
        private TransactionState $state,
        private LedgerRepository $ledger,
        private Notifier $notifier,
    ) {
    }

    public function post(Entry $entry): Receipt
    {
        if ($this->state->isActive()) {
            throw new LogicException('A posting must open its own transaction scope.');
        }

        return $this->transactions->transactional(function () use ($entry): Receipt {
            $receipt = $this->ledger->append($entry);
            $this->transactions->afterCommit(fn () => $this->notifier->posted($receipt));
            $this->transactions->afterRollback(fn () => $this->notifier->withdrawn($entry));

            return $receipt;
        });
    }
}
```

The service never sees a connection. When `append()` throws, the scope is discarded, the withdrawal notice
fires, the posted notice never does, and the exception reaches the caller unchanged. When it returns, the
receipt is durable before the posted notice runs. A runnable version against the shipped test double is
[`examples/typed-consumer.php`](examples/typed-consumer.php).

## Dependency injection: no provider, by design

This is a contracts package. It exports two interfaces and one test double and no runtime service with
collaborators, so it ships no Laminas/Mezzio `ConfigProvider`, no factory and no alias: an empty provider
would be ceremony, not integration. The host constructs its adapter and binds it to the interface
identifiers itself, as Kumwe App does in its composition root:

```php
$container->share(TransactionManager::class, static fn (Container $c): TransactionManager =>
    new DoctrineTransactionManager($c->get(Connection::class)));
$container->share(TransactionState::class, static fn (Container $c): TransactionState =>
    new DoctrineTransactionState($c->get(Connection::class)));
```

Both bindings are **request- or operation-supplied** in the package standard's vocabulary: an adapter is
bound to one connection and must never be shared across threads or processes.
[`resources/service-map/v1.json`](resources/service-map/v1.json) records the absence and its reason;
[`docs/integration.md`](docs/integration.md) walks through the binding and what the host must prove.

## Public surface

| Symbol | Kind | Capability | Lifetime |
| --- | --- | --- | --- |
| `Kumwe\Transaction\Contract\TransactionManager` | interface | `transaction.boundary` | host-supplied per connection |
| `Kumwe\Transaction\Contract\TransactionState` | interface | `transaction.state` | host-supplied per connection |
| `Kumwe\Transaction\Testing\ImmediateTransactionManager` | final readonly class | `transaction.testing` | tests only |

No factories, no aliases, no configuration keys, no defaults: there is nothing to configure. Every symbol
and member is documented in [`docs/public-api.md`](docs/public-api.md) and pinned in
[`resources/public-api/v1.json`](resources/public-api/v1.json); `composer manifests` refuses drift.
Capabilities are recorded in [`resources/capabilities/v1.json`](resources/capabilities/v1.json).

## Guarantees

- **Errors and refusals.** The port defines no exception of its own. Whatever the operation throws reaches
  the caller as the same instance; an adapter surfaces its driver's failures in the driver's own
  classification and never wraps a domain failure. The test double propagates every `Throwable`.
- **Mutability.** The contracts declare no state; an adapter keeps per-scope state and holds none between
  nests. `ImmediateTransactionManager` is `readonly` and holds nothing.
- **Determinism.** The port reads no clock, no environment and no randomness. The double is fully
  deterministic: same calls, same effects, same order.
- **Precision and canonicalization.** Not applicable: the port carries callables and one boolean.
- **Transaction semantics.** Nesting is invisible; only the outermost scope commits; a failure anywhere
  discards the whole nest; a commit hook waits for the outermost commit and runs inline when no scope is
  open; a rollback hook fires when its scope is discarded and is a no-op when no scope is open.
- **Concurrency and process safety.** An adapter is bound to one connection and is not safe to share
  across threads, coroutines or processes. Neither contract promises anything about what another
  connection observes before commit.

## Extension and replacement points

Both interfaces are the extension points: a host implements them once per connection technology. A
consumer that needs a scoped in-memory double for its own tests writes one against the contract; the
shipped double is deliberately minimal and stateless.

## Migration from Kumwe App

`Kumwe\App\Application\Persistence\TransactionManager` becomes
`Kumwe\Transaction\Contract\TransactionManager` and `Kumwe\App\Application\Persistence\TransactionState`
becomes `Kumwe\Transaction\Contract\TransactionState`, behaviour unchanged. The App's test support
`Kumwe\App\Tests\Support\ImmediateTransactionManager` becomes
`Kumwe\Transaction\Testing\ImmediateTransactionManager`. `DoctrineTransactionManager`,
`DoctrineTransactionState`, the kernel bindings and every database test stay in the App. The complete
adoption record, with file-level Phase 2 instructions, is `MIGRATION-HANDOFF.md` in this repository.

## Testing and clean-consumer commands

```bash
composer install
composer check                    # the complete lane, ending in the built-archive clean-consumer gate
php tests/run.php                 # the dependency-free suite alone; no composer install needed
php examples/typed-consumer.php   # the shipped example; no composer install needed
```

The lane is described in [`docs/architecture.md`](docs/architecture.md); the archive and clean-consumer
proof in [`docs/releasing.md`](docs/releasing.md).

## Release, compatibility, security and license

Releases are on the record: the newest `## X.Y.Z` heading in [`CHANGELOG.md`](CHANGELOG.md) is the release
a merge to `main` publishes, as [`docs/releasing.md`](docs/releasing.md) describes. Compatibility follows
semantic versioning with exact consumer pins while pre-1.0. Security policy and scope are in
[`docs/security.md`](docs/security.md). Licensed under the [Apache License, Version 2.0](LICENSE).
