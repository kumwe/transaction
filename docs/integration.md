# Integration: adopting the port in a host

This document is for the host that owns a connection — Kumwe App first — and for any package that types
against the boundary. It says how to install the package, what an adapter owes, how to bind it, how to use
the contracts in a service and how to test against them. It never asks the host to copy anything from
this package.

## 1. Install and pin

```bash
composer require kumwe/transaction:0.1.0
```

While the package is pre-1.0 the host pins an exact version, never a range, and a re-pin is a deliberate,
reviewed change with its own evidence. There is no `ConfigProvider` to register and nothing to configure.

## 2. Type against the contracts

```php
use Kumwe\Transaction\Contract\TransactionManager;
use Kumwe\Transaction\Contract\TransactionState;
```

A service takes `TransactionManager` where it needs atomicity and `TransactionState` only where it must
refuse to run inside an open scope. It never takes a connection. `docs/public-api.md` documents every
member; the five-minute example in the README shows the shape.

## 3. Implement the adapter (host only)

The host implements each contract once per connection technology. An adapter must honour, and the host's
integration tests must prove on every engine the host supports:

| Promise | What the host proves |
| --- | --- |
| Only the outermost scope commits | a nested call runs at nesting level one; no savepoint is stacked |
| A failure anywhere discards the whole nest | a caught nested failure still rolls back the outer writes |
| Commit hooks wait for the outermost commit | a hook reads its own write through a second connection |
| Rollback hooks fire when their scope is discarded | inner then outer hooks run on an outer failure |
| Hooks travel outward when a scope returns | an inner rollback hook still fires on a later outer failure |
| Exceptions propagate unchanged | the same instance leaves the boundary; drivers keep their own classes |
| A failing rollback hook cannot mask the operation failure | the original failure reaches the caller |
| `isActive()` covers every route | a transaction begun by a migration or a repository is reported |

Kumwe App's `DoctrineTransactionManager` keeps a stack of completion frames over one physical
`Connection::transactional()` call and a retained rollback cause; `DoctrineTransactionState` reads
`Connection::isTransactionActive()`. Both stay in the App, together with the App's database evidence on
MariaDB, MySQL and PostgreSQL. A second host writes its own adapter against the same table.

## 4. Bind the adapter

The contract FQCNs are the service identifiers. Bind the host's adapter to them; never alias a historical
name, never register a fallback, never register the test double.

Kumwe App's composition root:

```php
$container->share(TransactionManager::class, static fn (Container $c): TransactionManager =>
    new DoctrineTransactionManager(self::service($c, Connection::class)), true);
$container->share(TransactionState::class, static fn (Container $c): TransactionState =>
    new DoctrineTransactionState(self::service($c, Connection::class)), true);
```

A Laminas ServiceManager host:

```php
'factories' => [
    DoctrineTransactionManager::class => DoctrineTransactionManagerFactory::class,
    DoctrineTransactionState::class => DoctrineTransactionStateFactory::class,
],
'aliases' => [
    TransactionManager::class => DoctrineTransactionManager::class,
    TransactionState::class => DoctrineTransactionState::class,
],
```

Lifetime: both adapters are bound to one connection, so they live exactly as long as that connection —
shared per request or per worker, never across threads or processes, never as a global.

## 5. Test against the contracts

For a unit test of a service that only needs the port to exist, construct the shipped double:

```php
use Kumwe\Transaction\Testing\ImmediateTransactionManager;

$service = new LedgerPosting(new ImmediateTransactionManager(), $state, $ledger, $notifier);
```

It runs the operation inline, runs a commit hook the moment it is registered and discards rollback hooks;
the package suite pins exactly that. For a test that must observe deferral, compensation or nesting, write
a scoped double against the contract in the host's own test support; do not extend this package's double
(it is final) and do not promote any double to production.

For the adapter itself, write integration tests against a real database — the table in section 3 is the
checklist — and keep them in the host; this package cannot prove durability and does not try to.

## 6. What not to do

- Do not keep a copy of either contract under a host namespace, and do not alias the host's old names to
  these: nothing resolves a retired name.
- Do not depend on `Kumwe\Transaction\Testing\` from production code, and do not bind it in a container.
- Do not catch and wrap an operation's exception inside an adapter, and do not let a compensation replace
  the failure that triggered it.
- Do not implement retry, deadlock or nesting policy here or ask this package to; those are host decisions
  around the boundary, not the boundary.
