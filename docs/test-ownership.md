# Package test ownership

The portable behavior, boundary and conformance suites belong to kumwe/transaction.

`tests/ownership.json` maps every exported public type to concrete tests discovered by the package's real runner.
Interfaces and errors may be exercised through a consumer path; those mappings identify the tests that actually
exercise the contract. The record also identifies semantic corpus ownership and the exact App test transfers at
source baseline `960ce8ec00cf724a7cae03e5ba09c4852c9ab54e`. Its format is `tools/test-ownership.schema.json`.

Run `composer test:ownership` to validate the record and its negative cases. This check is part of `composer
check`, alongside actual test execution, architecture/API drift and archive/consumer gates. It rejects unmapped
exports, stale or missing tests, empty behavior/boundary evidence, absent conformance rationale, missing
architecture evidence and floating host baselines. Test discovery uses the actual Composer test runner; arbitrary
inventory commands and symlinked evidence are refused.

This is a traceability gate, not a statement of line coverage or a substitute for reviewing test assertions. A new
public type requires behavior and boundary evidence; a semantic owner must also maintain its conformance cases.
Future changes must update the tests and ownership record together. Method discovery is read-only and never
executes the test callbacks.

## Host boundary

- Doctrine transactions, nesting/retry/deadlock policy, database matrix, outbox/audit and application composition.

The exact per-file adoption and split instructions are recorded in tests/ownership.json. Remove a duplicate App
implementation test only when its old production implementation is removed during verified adoption. App retains
its own integration assertions and does not execute package test files from vendor.

## Release integrity

The package-owned release-integrity fixtures run in the complete check lane. Tags and released artifacts are
never moved, replaced or deleted; publication verifies the exact stable version and its source identity.
Independent consumer verification remains separate from publication.

Branch protection and GitHub's immutable-release setting are optional repository hardening, as documented in
[the package release standard](package-release-standard.md). Optional platform settings do not relax the
artifact identity contract or authorize replacement of an existing release.
