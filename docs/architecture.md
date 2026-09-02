# Architecture and engineering standard

This document says how the package is built and what "good" means in this repository, so quality is a
stated, checkable expectation. The [charter](../CHARTER.md) says what Transaction is; this says how it is
kept that way. It descends from the Kumwe App coding standard and the Kumwe Version 2 package standard,
adapted to a package whose whole subject is one boundary.

## Layers

Transaction is two layers with one dependency direction. A layer may use the layers listed for it and
never the others; `composer architecture` reads the token stream of every source file and refuses a
crossing, a driver, a host, a container or a framework name.

| Layer | Namespace | Owns | May use |
| --- | --- | --- | --- |
| Contract | `Kumwe\Transaction\Contract` | the two ports and their documented semantics | Contract, PHP |
| Testing | `Kumwe\Transaction\Testing` | the explicitly test-scoped double | Contract, Testing, PHP |

Rules that keep the boundary honest:

1. **The extracted behaviour is the API.** This package is a drop-in replacement for a port Kumwe App
   already declared and every App consumer already composes over. A public shape here matches the shape
   the App published — the same method names, the same `callable` parameter, the same `mixed`/`void`
   returns, the same `@template T` contract, the same documented semantics. An improvement that moves
   behaviour is new work with a new version, never part of extraction.
2. **A port carries no implementation.** No class in `Contract` has a body; no class in the package opens,
   commits or rolls back anything. The only concrete class is the test double, and its whole behaviour is
   "run it now".
3. **Nothing is selected at runtime.** No `class_exists()`, `extension_loaded()`, `class_alias()` or
   fallback of any kind appears in `src/`; the architecture gate refuses them.
4. **Determinism.** The package reads no clock, no environment and no randomness.
5. **One owner, one name.** `Kumwe\Transaction\` is the only namespace. No historical App name survives
   anywhere in the tree, and the suite refuses one in the manifests and the source.

## Code

- `declare(strict_types=1)` in every file; `final` classes by default and `final readonly` wherever the
  instance carries no state; native types on every parameter and return; one class-like declaration per
  file, named after the file, autoloaded PSR-4 from `Kumwe\Transaction\`.
- No runtime Composer dependency, ever (`php` alone), and no extension: a host adopts a contract, not a
  dependency tree.
- Every line of every tracked file — code, documentation, manifests, workflows — stays at or below 120
  columns; `composer lint` enforces it.
- No timestamp, commit hash or machine path is committed into a generated artifact; the same source
  produces identical manifests on every supported runtime.

## Documentation blocks

Every documentable member — class-like declaration, method, non-promoted property, class constant, enum
case — carries a documentation block, enforced by `composer docs`. The format is the Kumwe App standard:

1. A summary sentence stating what the member does, then optionally a paragraph on when to reach for it —
   the guarantee it makes and which collaborator owns the parts it does not.
2. Aligned, ordered tags: `@template` first where present, then `@param` entries in signature order
   (promoted constructor properties included), then `@return` (except constructors), then `@throws` with
   the condition each entry is raised under, then the trailing group. Within a block, tag values start in
   one column: two spaces after the longest tag name in the block.
3. `@since` is always last and always present; every member of this package records `0.1.0`, the release
   that introduced it here, and it is never rewritten.
4. Types are precise — `callable(): T`, `list<string>`, `array<string, mixed>` — never a bare `array` or
   `callable` in a documentation block, and an existing documented type is never widened or dropped.

## Testing

The suite exists to prove intended outcomes, and only that:

1. **A test asserts an observable contract.** The interface shape, the generic return contract, the
   documented semantics, the double's exact behaviour, the manifests against the source, the example's
   output — never an implementation detail.
2. **Non-promises are pinned as firmly as promises.** The double is proven to run a commit hook before a
   failure and to discard every rollback hook, because a consumer test that assumes otherwise would pass
   for the wrong reason.
3. **Nothing frivolous.** A test that cannot fail for a reason a consumer would care about is not written.
4. **Dependency-free and deterministic.** `php tests/run.php` runs on a clean clone with no composer
   install, touches no network and no clock, and passes in any order. Discovery fails closed.

Test ownership across the boundary: this package owns the contract shape, the documented semantics, the
double's behaviour and the manifests. A host owns everything that needs a store — commit durability,
rollback residue, driver-failure classification, retryable contention, nested-scope fate, audit and outbox
atomicity, and the composition that binds its adapter — and proves it on the engines it supports.

## The check lane

After `composer install`, `composer check` is the whole standard, executable, in this order:

| Step | Command | Proves |
| --- | --- | --- |
| Composer metadata | `composer validate --strict` | the package coordinate is publishable |
| Lint | `php tools/lint.php` | every PHP file parses; every tracked line fits 120 columns |
| Documentation | `php tools/check-docblocks.php` | every member of `src/` and `examples/` is documented |
| Architecture | `php tools/verify-architecture.php` | the layer rules, no coupling, PHP-only runtime |
| Manifests | `php tools/verify-manifests.php` | the three manifests agree with source, changelog, docs |
| Autoload smoke | `php resources/toolchain/autoload-smoke.php` | Composer loads every exported symbol |
| Example | `php examples/typed-consumer.php` | the shipped example runs |
| Style | `phpcs -q` | PSR-12 with a hard 120-column limit |
| Static analysis | `phpstan analyse` | level max with strict and deprecation rules, source and tooling |
| Suite | `php tests/run.php` | the dependency-free behavioural and manifest suite |
| Clean consumer | `php tools/verify-clean-consumer.php` | the built archive installs and runs no-dev |

Every commit passes it. CI runs it on every supported PHP version and then removes the development
packages, rebuilds an authoritative production autoloader, and runs the smoke and the example again. A
release re-proves the same lane on the merged commit before a tag exists.

## Versioning and drift

The public API manifest is the compatibility pin: `composer manifests` regenerates it from reflection and
refuses any difference. A reviewed change records the new surface with `composer manifests:record` and a
changelog entry; routine changes never rewrite the evidence to make the gate green. A change a consumer
must act on is a new major. Newer portable behaviour discovered in a consumer after extraction is routed
here as a successor release before that consumer adopts it; see `MIGRATION-HANDOFF.md`.
