# The Transaction charter

**Kumwe Transaction** is the storage-neutral transaction port of the Kumwe family: the contract an
application service uses to run work atomically and to defer side effects until that work is durable, and
the read-only view of whether a transaction is already open on the connection the service would run on.
It ships no transaction implementation. It is extracted from [Kumwe App](https://github.com/kumwe/app) as
a drop-in replacement, not a mutation: the App adopts this package and every consumer behaves exactly as
it did against the port the App declared itself.

This charter is normative for the repository. A change that contradicts it is a defect, whatever tests it
passes.

## What Transaction is

1. **The boundary, stated once.** Every application service in a Kumwe host that needs atomicity types
   against the same two contracts, so a use case composes: calling one service from inside another's
   scope joins that scope instead of opening a second one. The App declared this port for that reason;
   this package is where the declaration now lives, so a second host, a package that needs a transaction
   boundary (`kumwe/approval` is the first in the catalog) and the App all name one owner.
2. **A port, not an engine.** The package says what a transaction boundary promises — nesting invisible,
   commit hooks deferred to the outermost commit, rollback hooks fired when the registering scope is
   discarded, exceptions propagated unchanged — and says nothing about how a store keeps that promise.
3. **Proven by contract.** The interface shapes and the documented semantics are asserted by the package
   suite; the shipped test double is proven to do exactly what its documentation says and nothing more. A
   capability the suite does not prove is not claimed.

## What Transaction contains

- **`Kumwe\Transaction\Contract\TransactionManager`** — run an operation inside a transaction scope,
  register a side effect for after the outermost commit, register a compensation for a discarded scope.
- **`Kumwe\Transaction\Contract\TransactionState`** — whether any transaction scope, however deeply
  nested and whoever opened it, is currently open on the underlying connection.
- **`Kumwe\Transaction\Testing\ImmediateTransactionManager`** — test support for consumer suites: an
  inline, stateless, non-durable `TransactionManager` that never rolls back. It is explicitly test-scoped
  and is never registered in a container.

## What Transaction must never contain

1. **No transaction implementation of any kind.** A Doctrine DBAL adapter, a PDO adapter, a savepoint
   strategy, an isolation-level choice: every one of them is an implementation of a port defined here,
   living in the host that owns the connection, never in this package.
2. **No policy.** Nested-transaction, retry, deadlock, timeout and reconnection policy belong to the host.
3. **No coordination.** Logging, audit, outbox and event coordination around a transaction are the host's.
4. **No container wiring.** There is no `ConfigProvider`, no factory and no alias here; the host binds its
   adapter to the two contract identifiers in its own container. An empty provider would be ceremony.
5. **No runtime Composer dependency.** The package runs on PHP alone, so a host adopts a contract, not a
   dependency tree.
6. **No production fallback.** The test double is test support. Nothing in this package may be bound as a
   transaction manager in production, and no host may keep a copy of the contracts under another name.

## The non-negotiable rule

> **An effect that cannot be undone never runs for work that a later failure discards.**

A commit hook registered anywhere in a nest waits for the outermost commit; a rollback hook fires as soon
as the scope that registered it is discarded; only the outermost scope commits; a failure anywhere in the
nest discards the whole of it. An adapter that lets a hook fire early, swallows an operation's exception,
or commits an inner scope on its own has not implemented this port, whatever interface it declares.

## Drop-in mechanics

- **Canonical namespace here.** Every extracted type lives under `Kumwe\Transaction\` in this repository,
  which is its one canonical home from the first release onward.
- **Canonical names everywhere.** The App's adoption change migrates every reference — imports, FQCN
  strings, reflection assertions and documentation — to the canonical names, deletes its copies and
  retires the historical `Kumwe\App\Application\Persistence\` names in that same change. No compatibility
  layer: nothing resolves a retired name, and nothing has to, because no third party was ever published
  against the historical names. `MIGRATION-HANDOFF.md` is the record of what moved where.
- **Identity proven, not asserted.** The package suite pins the interface shapes and the documented
  semantics; `resources/public-api/v1.json` records the complete reflected surface and the lane refuses
  drift; the App's retained database, boundary and composition tests stay green without rewriting before
  adoption is claimed.

## The boundary in one line

**This package says what a transaction boundary promises. The host says how its store keeps the promise.**

## Relationships

- **With Kumwe App** ([`kumwe/app`](https://github.com/kumwe/app)): the App is Transaction's first
  consumer, never its owner. It pins an exact version, imports the canonical names directly, owns
  `DoctrineTransactionManager` and `DoctrineTransactionState`, owns the container bindings, and proves its
  adapter on MariaDB, MySQL and PostgreSQL.
- **With dependent packages**: a package that needs a transaction boundary depends on this one and types
  against the contracts; it never ships an adapter of its own.

## Governance

Delivered behaviour is recorded in [`CHANGELOG.md`](CHANGELOG.md); a claim states only what the check
lane proves on a clean clone. The check lane is `composer check`: strict Composer metadata, syntax and
column limits, member documentation, the architecture boundary, the three manifests against source and
documentation, the Composer-autoload proof, the runnable example, PSR-12, PHPStan level max with strict
and deprecation rules, the dependency-free suite, and the built-archive clean-consumer gate. Every commit
passes it. The engineering rules live in [`docs/architecture.md`](docs/architecture.md).
