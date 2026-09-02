# Public API

Every stable public symbol of `kumwe/transaction`, member by member. The machine-readable form is
[`resources/public-api/v1.json`](../resources/public-api/v1.json), generated from reflection over `src/`
and held to this document by `composer manifests`: a symbol or public member that is exported but not
documented here fails the lane. The canonical namespace is `Kumwe\Transaction\`; no type here has any
other name.

Vocabulary used below:

- **Scope** — one `transactional()` call, from the moment its operation starts until it returns or throws.
- **Nest** — a scope together with every scope opened inside it; the outermost scope owns the physical
  transaction.
- **Durable** — committed by the host's store, so that another connection can observe the work.
- **Adapter** — the host's implementation of a contract, bound to one connection.

## `Kumwe\Transaction\Contract\TransactionManager`

| | |
| --- | --- |
| Kind | interface; extension point — the host implements it once per connection technology |
| Stability | stable since 0.1.0 |
| File | `src/Contract/TransactionManager.php` |
| Capability | `transaction.boundary` |
| State | none declared; an adapter keeps per-scope state and holds none between nests |
| Concurrency | an adapter is bound to one connection and is not shared across threads or processes |

**Responsibility.** Run work atomically and defer side effects until that work is durable. An application
service takes this port rather than a connection, which is what lets use cases compose: calling one
service from inside another's transaction joins the scope already open instead of starting a second one.

**Invariants an adapter owes.**

1. Nesting is invisible to the caller: a nested call joins the enclosing scope; only the outermost scope
   begins and commits the physical transaction.
2. A failure anywhere in the nest discards the whole nest, including nested scopes that already returned,
   even when an enclosing operation caught the failure.
3. A commit hook registered anywhere in the nest runs only after the outermost commit, and runs inline
   when no scope is open.
4. A rollback hook runs as soon as the scope that registered it is discarded, and is dropped when
   registered outside any scope.
5. Whatever an operation throws reaches the caller as the same instance.

### `transactional(callable $operation): mixed`

**Responsibility.** Run `$operation` inside a transaction scope: commit when it returns, roll back when
it throws.

**Parameters.**

- `$operation` — `callable(): T`, required. Work to perform inside the scope. It receives no argument; a
  service captures what it needs. It may call `transactional()` again, which opens a nested scope, and it
  may register hooks through the same instance.

**Returns.** `T` — whatever the operation returned, the same value unmodified: `null`, `false`, a scalar,
an array or an object (the same instance) all pass straight through. The native return type is `mixed`;
the `@template T` annotation is the load-bearing contract for static analysis and is pinned by the suite.

**Exceptions.** Anything the operation throws reaches the caller as the same instance: no wrapping, no
reclassification, no substitution. An adapter surfaces its own store failures — begin, commit or rollback
refused, contention, connection loss — in its driver's classification and documents them; it never turns
a domain failure into a driver failure or the reverse. This package declares no exception type.

**Side effects and state mutation.** Opens a scope. On return, closes it; if it was the outermost scope,
commits, then runs the commit hooks registered anywhere in the nest in registration order. On failure,
closes the scope, runs the rollback hooks that scope registered, and rethrows; when the outermost scope
fails, the physical transaction is rolled back and every hook of the nest is discarded except the rollback
hooks, which have already run scope by scope. Between nests an adapter holds no scope state.

**Nullability.** `$operation` is required. The return is `null` exactly when the operation returned `null`.

**Precision and canonicalization.** Not applicable.

**Transaction expectations.** A caller outside any scope gets a new outermost scope and may assume the
work is durable when the call returns. A caller inside a scope — its own or another service's — joins it
and must not assume its return made anything durable.

**Concurrency and process safety.** Reentrancy from within the operation (nesting) is required to work.
One adapter instance serves one connection; it is not safe to share across threads, coroutines or
processes, and the port makes no promise about what another connection observes before commit.

**Example.**

```php
$receipt = $transactions->transactional(function () use ($ledger, $entry, $transactions): Receipt {
    $receipt = $ledger->append($entry);                            // joins an open scope, or opens one
    $transactions->afterCommit(fn () => $this->notify($receipt)); // waits for the outermost commit

    return $receipt;                                               // T is Receipt
});
```

### `afterCommit(callable $operation): void`

**Responsibility.** Register a side effect that runs after the outermost transaction commits, or run it
immediately when no transaction is active. This is where an effect that cannot be undone belongs — cache
eviction, a queue write, filesystem publication — so it never fires for work a later failure discards.

**Parameters.**

- `$operation` — `callable(): void`, required. The side effect. Its return value is ignored.

**Returns.** `void`.

**Exceptions.** Registration throws nothing. When the hook itself throws while running after a commit, the
exception reaches the caller of the outermost `transactional()`; the commit has already happened and the
remaining hooks of that nest are abandoned, so a hook that can fail should contain its own failure.

**Side effects and state mutation.** Inside a scope, appends the hook to that scope; when the scope
returns normally the hook travels to the enclosing scope, and it runs after the outermost commit in
registration order. Outside any scope, runs the hook at once and stores nothing.

**Nullability.** `$operation` is required.

**Precision and canonicalization.** Not applicable.

**Transaction expectations.** By the time the hook runs, every write of the nest is durable.

**Concurrency and process safety.** As for the adapter: one connection, one thread.

**Example.**

```php
$transactions->afterCommit(function () use ($cache, $key): void {
    $cache->evict($key); // runs only once the invalidating write is durable
});
```

### `afterRollback(callable $operation): void`

**Responsibility.** Register a compensating action that runs if the scope that registered it rolls back.
Registering while no transaction is active is a no-op, since there is nothing left to compensate.

**Parameters.**

- `$operation` — `callable(): void`, required. The compensation. Its return value is ignored.

**Returns.** `void`.

**Exceptions.** Registration throws nothing. A compensation that throws must not replace the operation
failure that caused the rollback: an adapter contains the compensation's failure and lets the original
failure reach the caller.

**Side effects and state mutation.** Inside a scope, appends the hook to that scope. When that scope is
discarded the hook runs at once, before the failure leaves the scope. When the scope returns normally the
hook travels to the enclosing scope, so it still fires if an outer scope fails afterwards. Outside any
scope, the hook is dropped.

**Nullability.** `$operation` is required.

**Precision and canonicalization.** Not applicable.

**Transaction expectations.** The hook runs while the physical transaction may still be open (a nested
scope was discarded) or after it was rolled back (the outermost scope failed); it must not rely on either.

**Concurrency and process safety.** As for the adapter: one connection, one thread.

**Example.**

```php
$transactions->afterRollback(function () use ($storage, $path): void {
    $storage->delete($path); // the file was written before the database refused the record
});
```

## `Kumwe\Transaction\Contract\TransactionState`

| | |
| --- | --- |
| Kind | interface; extension point — the host implements it once per connection technology |
| Stability | stable since 0.1.0 |
| File | `src/Contract/TransactionState.php` |
| Capability | `transaction.state` |
| State | none declared; an adapter reads the connection and keeps nothing |
| Concurrency | an adapter is bound to one connection and is not shared across threads or processes |

**Responsibility.** Answer whether the connection a use case would run on already holds an open
transaction — without handing the application layer a connection. `TransactionManager` deliberately makes
nesting invisible; a few boundaries cannot be composed that way (one that recovers from a lost
unique-index race has to observe the losing statement's rollback, which never happens while an enclosing
transaction is open) and those have to know, before they begin, that no scope is active.

### `isActive(): bool`

**Responsibility.** Report whether a transaction is currently open on the underlying connection.

**Parameters.** None.

**Returns.** `bool` — true while any transaction scope, however deeply nested, remains open; false
otherwise. The answer covers every route a transaction can be opened by — the manager, a repository, a
migration or driver code — because what matters to the caller is whether the connection is inside a
transaction, not who opened it.

**Exceptions.** None. An adapter reads the connection's own nesting state; it does not query the store.

**Side effects and state mutation.** None; the query is read-only and idempotent.

**Nullability.** Never null.

**Precision and canonicalization.** Not applicable.

**Transaction expectations.** The answer is valid at the instant of the call on this connection only; a
caller that needs "no transaction" to still hold must open its own scope immediately.

**Concurrency and process safety.** As for the adapter: one connection, one thread.

**Example.**

```php
if ($this->state->isActive()) {
    throw new LogicException('Recovery from a lost race must observe the rollback; run it outside a scope.');
}
```

## `Kumwe\Transaction\Testing\ImmediateTransactionManager`

| | |
| --- | --- |
| Kind | final readonly class implementing `TransactionManager`; **test support only**, not an extension point |
| Stability | stable since 0.1.0 |
| File | `src/Testing/ImmediateTransactionManager.php` |
| Capability | `transaction.testing` |
| State | none; the class declares no property |
| Concurrency | stateless, so shareable; never bound in a container, never reached by production code |

**Responsibility.** Let a consumer's unit test construct a service that types against the port without a
store. It is explicitly not a transaction implementation: nothing is durable, nothing defers, nothing
rolls back. A test that needs the nesting, deferral or compensation rules writes a scoped double of its
own.

### `transactional(callable $operation): mixed`

**Responsibility.** Run the operation at once and hand its result straight back.

**Parameters.** `$operation` — `callable(): T`, required.

**Returns.** `T` — the operation's own result, unmodified.

**Exceptions.** Whatever the operation throws, as the same instance; nothing is caught.

**Side effects and state mutation.** None of its own. A nested call runs inline exactly like an outer one.

**Nullability, precision, transaction expectations.** As the contract, minus durability: the return means
only that the operation returned.

**Concurrency and process safety.** Stateless.

### `afterCommit(callable $operation): void`

**Responsibility.** Run the commit hook at once, whether or not a scope is open, so a unit test observes
the effect immediately. This is deliberately not the deferral the contract promises of an adapter, and the
suite pins the difference: a hook registered before a failure has already run.

**Parameters.** `$operation` — `callable(): void`, required. **Returns.** `void`. **Exceptions.** Whatever
the hook throws. **Side effects.** The hook's own. **Concurrency.** Stateless.

### `afterRollback(callable $operation): void`

**Responsibility.** Discard the rollback hook, because no scope of this double is ever discarded.

**Parameters.** `$operation` — `callable(): void`, required; never invoked. **Returns.** `void`.
**Exceptions.** None. **Side effects.** None. **Concurrency.** Stateless.
