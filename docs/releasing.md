# Releasing the transaction package

Follow the [Package release standard](package-release-standard.md) for the shared
quality gate, changelog parsing, publication and retry behavior. Normal publication
does not require administrator setup, active branch protection or a ruleset,
GitHub's immutable-release flag, or external attestations. Existing repository
rules and permissions still apply.

The required CI check is **Package gate**. Maintainers rebase reviewed PRs into the
repository's dynamically discovered default branch. The release workflow reruns
the complete source CI gate on that resulting commit, checks out the event's exact
`github.sha`, and verifies local `HEAD` matches it. A PR SHA is never the promised
future release identity. The newest stable SemVer changelog record selects the
version and must agree with the release manifests. An Unreleased-only changelog
does not publish; keep work that is not ready under `## Unreleased`.

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
`CHANGELOG.md`, `LICENSE`, `composer.json`, `docs/` (including `release-record.md`), `examples/`, `resources/`
and `src/`. `tools/verify-archive.php` holds an extracted archive to that exact file set and refuses a
stray or a missing file; the archive must include the current package release contract record.

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

A released version is never re-tagged, moved or deleted. A defect is fixed forward in a
new version with a changelog entry that names the affected versions. A security defect additionally
follows [`security.md`](security.md). A consumer rolls back by re-pinning the previous exact version.

## Compatibility

Supported platform: PHP `^8.5`, no extension, no Composer dependency. CI proves every supported PHP
version with fail-fast disabled; the declared range is the tested range, never wider.

## Publication evidence and recovery

The maintainer performs the initial Packagist submission. Its GitHub integration
then follows tags without a registry credential in CI. Confirm `package-released`
from the successful default-branch publication run and matching published stable
release, tag and source identity. Publication does not establish `release-verified`.
Before declaring that state or SDK/App adoption, a fresh independent verifier must
bind the exact published source/tag, archive digest, manifests, registry coordinate,
license/security and clean-consumer results in an external RELEASE-ATTESTATION.yaml.
The artifact and release contract record must not invent their own final commit, checksum or
publication evidence. This attestation is separate from normal publication.

Use the current release workflow on the default branch to retry after correcting
the reported failure. Existing tags and releases must match their source identity
and are never moved, deleted or replaced. Later default-branch runs may verify a
published release on an ancestor; an unpublished tag can be completed only on the
exact event commit that passed the full gate. Only a confirmed HTTP 404 permits
creation; authentication, rate-limit and server failures never authorize creation.
A release with GitHub's immutable flag disabled remains platform-mutable; accepting
it for normal publication does not make it immutable. Fix defects with an unused
successor version. A green PR does not prove publication or independent verification.

## Optional administrator hardening

[Repository release setup](repository-release-setup.md) is an explicit optional
administrator action. `--check` only audits; `--apply` changes the managed settings;
adding `--dispatch` requests a release run after setup verification:

```bash
bash tools/configure-release-repositories.sh --check kumwe/transaction
bash tools/configure-release-repositories.sh --apply kumwe/transaction
bash tools/configure-release-repositories.sh --apply --dispatch kumwe/transaction
```

The release workflow does not change repository settings automatically. This helper
requires repository Administration access, and dispatch also needs Actions write
permission. Keep administrator credentials out of Actions. A setup audit or dispatch
is neither a normal publication prerequisite nor proof that publication succeeded.
