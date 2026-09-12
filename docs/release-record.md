---
schema: kumwe-package-release-record/v1
artifact_kind: framework_php
migration_id: KUMWE-MIG-2026-001
change_set: KUMWE-CS-2026-001
source:
  app:
    repository: https://github.com/kumwe/app
    baseline_commit: 6f9e42cb59a84ba3ca523a70475cf4d7263c68e7
    examined_paths:
      - src/Application/Persistence/TransactionManager.php
      - src/Application/Persistence/TransactionState.php
      - tests/Support/ImmediateTransactionManager.php
      - src/Infrastructure/Persistence/DoctrineTransactionManager.php
      - src/Infrastructure/Persistence/DoctrineTransactionState.php
    old_namespace_roots:
      - Kumwe\App\Application\Persistence\
    capability_index_sha256: 87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39
  semantic_inputs: []
  examined_dependencies:
    - Runtime requires PHP ^8.5 only; no Composer package or extension dependency.
target:
  repository: https://github.com/kumwe/transaction
  artifact_identity: kumwe/transaction (Composer library)
  canonical_namespace_or_abi: Kumwe\Transaction
ownership:
  responsibility: "Storage-neutral transaction port: atomic scopes, settlement hooks and an open-transaction view."
  non_responsibilities:
    - "Any transaction implementation: driver adapters, connections, savepoints and isolation levels stay in
      the host."
    - Nested-transaction, retry, deadlock and timeout policy.
    - Logging, audit, outbox and event coordination around a transaction.
    - "Container registration: the host binds its adapter to the contract identifiers; there is no provider."
    - Real-database evidence, which the host proves on the engines it supports.
  allowed_dependency_ceiling: []
  implementation_owner: kumwe/transaction
  next_consumer: kumwe/app
  public_manifests:
    - path: resources/public-api/v1.json
      sha256: f9589717354cc969ecb87e18d27c3e7f9e501f551bce80cadb698c3989cf2b75
    - path: resources/capabilities/v1.json
      sha256: 4e3fc6c4f014183eafad06b39e5c34a10c5e889c305ae09836f813d67cf5795c
    - path: resources/service-map/v1.json
      sha256: ec72f2fbda46e326380795664c62c4d7cf38ec557c7e2a9bac40a905ee62d5f5
  intentionally_excluded:
    - DoctrineTransactionManager stays in App; it owns the DBAL connection and the nesting policy
    - DoctrineTransactionState stays in App; it reads the DBAL connection
    - The two share() bindings in src/Kernel/ContainerFactory.php stay in App; composition is host authority
    - The App's inline scope-recording test doubles stay in App; they are test-local probes, not package
      behaviour
    - Retry, deadlock, timeout, logging, audit and outbox coordination stay in App; they are policy around the
      port
framework_php:
  composer_package: kumwe/transaction
  canonical_namespace: Kumwe\Transaction
  public_api_manifest: resources/public-api/v1.json
  capability_manifest: resources/capabilities/v1.json
  service_map: resources/service-map/v1.json
  extracted_symbols:
    - old_fqcn: Kumwe\App\Application\Persistence\TransactionManager
      new_fqcn: Kumwe\Transaction\Contract\TransactionManager
      source_path: src/Application/Persistence/TransactionManager.php
      target_path: src/Contract/TransactionManager.php
      kind: interface
      public_methods:
        - transactional
        - afterCommit
        - afterRollback
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: preserved
    - old_fqcn: Kumwe\App\Application\Persistence\TransactionState
      new_fqcn: Kumwe\Transaction\Contract\TransactionState
      source_path: src/Application/Persistence/TransactionState.php
      target_path: src/Contract/TransactionState.php
      kind: interface
      public_methods:
        - isActive
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: preserved
    - old_fqcn: Kumwe\App\Tests\Support\ImmediateTransactionManager
      new_fqcn: Kumwe\Transaction\Testing\ImmediateTransactionManager
      source_path: tests/Support/ImmediateTransactionManager.php
      target_path: src/Testing/ImmediateTransactionManager.php
      kind: class
      public_methods:
        - transactional
        - afterCommit
        - afterRollback
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: preserved; declared final readonly instead of final, no behavioural change
  consumers:
    app_code:
      - src/Infrastructure/Persistence/DoctrineTransactionManager.php
      - src/Infrastructure/Persistence/DoctrineTransactionState.php
    configuration_and_di:
      - src/Kernel/ContainerFactory.php
    reflection_and_string_references: []
    fixtures_and_examples: &a1
      - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
      - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
      - tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php
      - tests/Architecture/TransactionSeamBoundaryTest.php
    external: []
  dependency_injection:
    mode: direct
    provider: null
    factories: []
    aliases: []
    service_lifetimes:
      - "Kumwe\\Transaction\\Contract\\TransactionManager: request-supplied (bound to one connection by the
        host)"
      - "Kumwe\\Transaction\\Contract\\TransactionState: request-supplied (bound to one connection by the
        host)"
    configuration_keys: []
    provider_absence_reason: "Contracts only, no runtime service: the host binds its adapter to the contract FQCNs."
native_cpp: null
php_extension: null
tests:
  moved_or_added:
    - tests/Case/TransactionManagerContractTest.php
    - tests/Case/TransactionStateContractTest.php
    - tests/Case/ImmediateTransactionManagerTest.php
    - tests/Case/ManifestsTest.php
    - tests/Case/ArchitectureTest.php
    - tests/Case/ExamplesTest.php
    - tests/Case/DocumentationTest.php
  remain_in_app_or_consumer: *a1
  split_tests:
    - Package tests own portable interface shapes and test-double behavior; Core retains adapter and
      composition tests.
  prohibited_duplicates:
    - Host copies of the canonical interfaces or package test double.
  corpora: []
documentation:
  charter: CHARTER.md
  readme: README.md
  public_api: docs/public-api.md
  architecture: docs/architecture.md
  integration_or_consumer: docs/integration.md
  examples:
    - examples/typed-consumer.php
    - examples/README.md
  changelog_record: "CHANGELOG.md ## 0.1.2"
release_expectations:
  version_policy: Semantic versioning; exact consumer pins while pre-1.0. The newest stable changelog record
    selects publication.
  expected_artifact_types:
    - Composer dist archive from the published immutable semantic version tag.
  required_checks:
    - composer check
    - Production-only classmap-authoritative install, autoload smoke and example
    - Release automation regression suite
    - Transaction CI and the post-rebase Release on record package gate
  required_registry_or_installer: Packagist
  required_external_attestation: true
governance:
  completion_claim: false
decisions:
  - The host binds its adapter directly to canonical interface names; no provider, alias or fallback is
    supplied.
  - ImmediateTransactionManager is explicitly test-scoped and must never be bound in production.
  - The package owns contract semantics; the host owns connection policy and persistence behavior.
  - Independent evidence identifies the final released artifact; this embedded record does not attest to
    itself.
blockers: []
consumer_contract:
  permitted_only_when:
    - The exact stable release has been published and independently verified from its source and artifact
      identities.
    - Core adapter, composition and supported-database tests pass against the selected exact package version.
  consumer_repository: kumwe/app
  dependency_or_native_change: >-
    Pin the independently verified kumwe/transaction version exactly; regenerate composer.lock with Composer.
  namespace_or_api_replacements:
    - Kumwe\App\Application\Persistence\TransactionManager -> Kumwe\Transaction\Contract\TransactionManager
    - Kumwe\App\Application\Persistence\TransactionState -> Kumwe\Transaction\Contract\TransactionState
    - Kumwe\App\Tests\Support\ImmediateTransactionManager ->
      Kumwe\Transaction\Testing\ImmediateTransactionManager
  files_to_update:
    - composer.json
    - composer.lock
    - src/Kernel/ContainerFactory.php
  files_to_remove:
    - src/Application/Persistence/TransactionManager.php
    - src/Application/Persistence/TransactionState.php
    - tests/Support/ImmediateTransactionManager.php
  tests_to_remove: []
  tests_to_retain_or_add: *a1
  di_or_provisioning_changes:
    - keep both share() bindings in src/Kernel/ContainerFactory.php keyed by the package FQCNs; no alias or
      fallback
    - "no ConfigProvider to register: the package ships none"
  capability_index_changes:
    - Track the installed public API, capabilities, service map and release contract record digests in Core.
  changelog_and_evidence_changes:
    - Record the selected exact package version and independent release attestation in the consumer.
  verification_commands:
    - composer qa
    - composer test:unit
    - Run Core database integration tests against MariaDB, MySQL and PostgreSQL.
---

## Package contract

This record describes the portable contract of `kumwe/transaction`. It does not report Core adoption
or claim a completed host integration. Historical source identifiers and namespace mappings in the
front matter preserve compatibility provenance for independent verification.

## Public API and responsibility

The package owns `TransactionManager`, `TransactionState`, and the explicitly test-scoped
`ImmediateTransactionManager` under `Kumwe\Transaction`. See [the public API](public-api.md)
for every method and its semantics. [The charter](../CHARTER.md) defines the package boundary.

Core owns database adapters, connection lifetime, nesting and retry policy, audit/outbox coordination
and container bindings. No runtime implementation or container provider is supplied by this package.

## Dependencies and semantic inputs

PHP `^8.5` is the only runtime requirement. There are no PHP extension or Composer dependencies,
and no external semantic corpus. The three public manifests above are verified against the source,
release version and API documentation by `composer manifests`.

## Consumer contract

Consume the canonical interfaces directly, with an exact independently verified version pin. Bind both
interfaces to adapters for the same connection. Never use `ImmediateTransactionManager` as a production
transaction manager. The [integration guide](integration.md) specifies required behavior and binding.

Core retains its database and composition tests. Source paths and namespace replacements in the front
matter identify compatibility provenance; current consumer work must inspect its own source before
removing any duplicate. Portable changes belong in a reviewed package successor release.

## Test ownership

Package tests cover interface shapes, documented semantics, test-double behavior, architecture,
manifests and consumer examples. Host tests prove commit durability, rollback residue, nested-scope
behavior, driver failures and atomic audit/outbox coordination on every supported database.
[The ownership guide](test-ownership.md) documents the maintained evidence and host boundary.

## Consumer verification

`composer clean-consumer` creates a Composer ZIP, checks its exact file set and installs it into a fresh
project with a production-only authoritative classmap. It then runs the shipped autoload smoke and example
through the consumer autoloader. Source CI does not replace this archive verification.

Independent release verification binds the published version and source commit to artifact, manifest
and release-record digests, package metadata and clean-consumer results. Existing releases are never
replaced. [Release policy](releasing.md) specifies the publication and attestation requirements.

## Compatibility and drift

`resources/public-api/v1.json` is the reflected compatibility record. Any change to public shapes or
transaction promises requires explicit compatibility review and a versioned release. Hosts re-run
adapter and integration tests when changing an exact package pin.

## Validation

Run from a clean checkout on supported PHP:

```bash
composer install
composer check
composer install --no-dev --classmap-authoritative
composer autoload:smoke
composer examples
```

The package and release workflows record results externally. This document does not embed a changing
CI result, predict a final source commit, or supply its own publication attestation.
