# Examples

Runnable, dependency-free demonstrations of the adoption path. Each runs from a git checkout with no
`composer install` and from the installed release archive alike; the suite replays them, and the
clean-consumer gate runs them inside the extracted archive.

## `typed-consumer.php`

```sh
php examples/typed-consumer.php
```

A `LedgerPosting` service typed against `TransactionManager` and `TransactionState`, exactly as a host's
application service would be, run against the shipped test double `ImmediateTransactionManager` and a
one-line `TransactionState` that reports no open scope. The output is deterministic:

```text
posted INV-1
refused: The entry INV-0 was refused.
write INV-1
notify INV-1
```

What it shows, line by line:

- `posted INV-1` — the operation's return value comes straight back out of `transactional()`.
- `refused: …` — the exception the operation threw reaches the caller as the same instance; nothing was
  written, notified or compensated for `INV-0`, because the operation refused before registering a hook.
- `write INV-1`, `notify INV-1` — the write and its commit hook, in order. Against the host's real adapter
  `notify` would run only after the physical commit; the double runs it inline, which is what a unit test
  wants. No `compensate` line appears because the double never discards a scope.

**This is a demonstration, never a production wiring.** The double is test support; a host binds its own
adapter to the two contracts, as [`docs/integration.md`](../docs/integration.md) describes.
