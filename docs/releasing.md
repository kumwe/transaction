# Releasing the transaction package

The package versions independently under semantic versioning; alignment with a consumer travels through
that consumer's exact pin, never through matching version numbers.

Releasing is merging. Every Kumwe PHP library delivers the same way:

1. Land the work on `main` with its `CHANGELOG.md` section for the version — the newest heading
   `## X.Y.Z` is the release record. Future work stays under `## Unreleased`, which the automation skips.
2. The `Release on record` workflow runs on every push to `main`: it installs the validated development
   toolchain, re-proves the complete check lane on the pushed commit, reads the newest recorded version,
   and identifies the release that commit represents. When the version has no tag yet it creates `vX.Y.Z`
   at that exact commit through the repository API and publishes the GitHub release. When the tag already
   exists it must resolve to a commit in the pushed `main` history whose newest changelog record is that
   same version, or the run fails. Nobody pushes or moves a tag by hand; a push that records no new version
   is a verification-only run. Runs are serialized so two pushes cannot race past the tag check.
3. Packagist follows tags through its GitHub integration — the maintainer submits `kumwe/transaction`
   once at packagist.org and every later release appears without a credential in this repository.

The agent that prepares a release opens a pull request and stops. The human merge is the release trigger.
A Version 2 release is not consumable until a separate verification session has independently checked the
published artifact and produced its external release attestation.

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

`composer clean-consumer` (the last step of `composer check`) builds the archive from the checkout,
extracts it, verifies the file set, validates the archived Composer metadata, installs it with
`--no-dev --classmap-authoritative` into its own directory, proves no development package survived, and
runs the shipped autoload smoke and the shipped example from inside the archive. CI repeats the no-dev
proof in the checkout as well. Unit tests in the package checkout do not replace this gate, because the
development toolchain can conceal a broken consumer artifact.

Running the lane on a PHP older than the declared `^8.5` range (for a local check only) requires
`composer install --ignore-platform-req=php` and, for the archive install inside the gate,
`KUMWE_CLEAN_CONSUMER_COMPOSER_ARGS=--ignore-platform-req=php`. CI never sets either; it runs the real
supported version.

## Rollback and advisories

A released version is immutable: it is never re-tagged, moved or deleted. A defect is fixed forward in a
new version with a changelog entry that names the affected versions. A security defect additionally
follows [`security.md`](security.md). A consumer rolls back by re-pinning the previous exact version.

## Compatibility

Supported platform: PHP `^8.5`, no extension, no Composer dependency. CI proves every supported PHP
version with fail-fast disabled; the declared range is the tested range, never wider.
