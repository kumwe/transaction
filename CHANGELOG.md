# Changelog

Delivered package changes, newest first. A change is recorded here only after its stated proof passes on a
clean clone. The newest `## X.Y.Z` heading is the release record: a merge to `main` that carries it is the
release, published by the `Release on record` workflow ([`docs/releasing.md`](docs/releasing.md)).

## 0.1.0

- **The storage-neutral transaction port.** Extracted from Kumwe App as a drop-in replacement:
  `Kumwe\Transaction\Contract\TransactionManager` (run an operation atomically; register a side effect for
  after the outermost commit; register a compensation for a discarded scope) and
  `Kumwe\Transaction\Contract\TransactionState` (whether any transaction scope is open on the connection),
  with the App's signatures, generic return contract and documented semantics preserved exactly. The
  package ships no implementation, no policy, no coordination and no container wiring; the host binds its
  own adapter to the two contract identifiers.
- **Consumer test double.** `Kumwe\Transaction\Testing\ImmediateTransactionManager`, ported from the App's
  test support unchanged in behaviour: runs the operation inline, runs a commit hook the moment it is
  registered, discards every rollback hook, and propagates every `Throwable` as the same instance. It is
  explicitly test-scoped, declared under its own `transaction.testing` capability, and never a production
  implementation.
- **The contract is executable evidence.** The suite pins both interface shapes, the `@template T` return
  contract and the documented semantics sentence by sentence, proves the double does exactly what it says,
  replays the shipped example, and holds the three Version 2 manifests to the source tree, the changelog
  and the API documentation.
- **Founding.** Charter, README, architecture, integration, releasing and security documents, the complete
  public API document, `resources/public-api/v1.json`, `resources/capabilities/v1.json` and
  `resources/service-map/v1.json` in the Kumwe App governance schemas, and the check lane: strict Composer
  metadata, lint and column limits, member documentation, the architecture boundary, manifests, the
  Composer-autoload proof, the runnable example, PSR-12, PHPStan level max with strict and deprecation
  rules, the dependency-free suite, and the built-archive clean-consumer gate. Continuous integration runs
  the lane on PHP 8.5 with pinned actions and least-authority permissions; release-on-record publishes the
  recorded version after a human merge and never accepts a hand-made tag.
