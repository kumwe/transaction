# Releasing the transaction package

Follow the [Package release standard](package-release-standard.md) for the shared
quality gate, changelog parsing, publication and retry behavior. Complete the
[repository release setup](repository-release-setup.md) with an administrator
session before merging a release record:

```bash
bash tools/configure-release-repositories.sh --check kumwe/transaction
bash tools/configure-release-repositories.sh --apply kumwe/transaction
```

The required CI check is **Package gate**. Maintainers rebase reviewed PRs into the
repository's current default branch; the release workflow reruns the same quality
gate on the resulting commit and derives its release identity from that run.
A release intention in CHANGELOG.md is not evidence that publication occurred.
Keep work that is not ready for publication under `## Unreleased`.

The package versions independently under semantic versioning; alignment with a
consumer travels through its exact pin, never through matching version numbers.

## Version policy

- **Patch** — a change that keeps every exported shape and every documented semantic identical: wording,
  tooling, evidence.
- **Minor** — an additive surface no existing consumer must act on.
- **Major** — a change a consumer must act on: a renamed or re-typed member, a moved namespace, a changed
  promise.
- While the package is `0.x`, consumers pin exactly, and any observable difference is treated as a major
  in review even though the number only moves the minor.

`composer manifests` is the compatibility gate: it regenerates `resources/public-api/v1.json` from
reflection and refuses any difference. A reviewed change records the new surface with
`composer manifests:record` in the same pull request as its changelog entry.

## What a release ships

The Composer archive is the release artifact. `.gitattributes` keeps development state out of it and keeps
in it exactly what a consumer and the Kumwe App adoption gate read: `CHARTER.md`, `README.md`,
`CHANGELOG.md`, `MIGRATION-HANDOFF.md`, `LICENSE`, `composer.json`, `docs/`, `examples/`, `resources/`
and `src/`. `tools/verify-archive.php` holds an extracted archive to that exact file set and refuses a
stray or a missing file; the archive is not release-ready without the handoff.

## Clean-consumer verification

`composer clean-consumer` (part of `composer check`) builds the archive from the checkout,
extracts it, verifies the file set, validates the archived Composer metadata, and installs that exact ZIP
as a dependency of a fresh Composer project with `--no-dev --classmap-authoritative --no-plugins --no-scripts`.
The local package repository points only at the built ZIP, and Packagist is disabled for this dependency-free
package. The installed file set is checked again; no App, package test class or development dependency may
enter the consumer classmap. The shipped smoke and example receive the consumer's autoloader explicitly.
CI repeats the no-dev
proof in the checkout as well. Unit tests in the package checkout do not replace this gate, because the
development toolchain can conceal a broken consumer artifact.

`composer check` also runs `composer audit --abandoned=fail` and executable changelog parser
regressions. The same parser reads the pushed commit and existing tag, including an optional Unreleased
section; a malformed newest release never falls through to an older version.

## Rollback and advisories

A released version is immutable: it is never re-tagged, moved or deleted. A defect is fixed forward in a
new version with a changelog entry that names the affected versions. A security defect additionally
follows [`security.md`](security.md). A consumer rolls back by re-pinning the previous exact version.

## Compatibility

Supported platform: PHP `^8.5`, no extension, no Composer dependency. CI proves every supported PHP
version with fail-fast disabled; the declared range is the tested range, never wider.

## Publication evidence and recovery

The maintainer performs the initial Packagist submission. Its GitHub integration
then follows tags without a registry credential in CI. Before dependent publication
or App adoption, a fresh independent verifier must bind the exact published
source/tag, archive digest, manifests, registry coordinate, license/security and
clean-consumer results in an external RELEASE-ATTESTATION.yaml. The artifact and
handoff must not invent their own final commit, checksum or publication evidence.

Use the current release workflow on the default branch to retry after correcting
repository settings. Historical mutable releases remain unchanged: enabling
immutability affects future publications, so a mutable version requires an unused
successor. Never move or delete a published tag or replace a released artifact.
An unpublished tag can be completed only on the exact commit tested by the retry.
A green PR does not replace the default-branch release result or independent
verification. Administrator credentials do not belong in Actions.
