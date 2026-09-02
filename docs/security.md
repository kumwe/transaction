# Security policy

## Scope

`kumwe/transaction` is a contracts package. It performs no I/O, opens no connection, reads no input,
stores nothing and requires no extension and no Composer dependency. Its attack surface is the code a
consumer executes through it: the callables a service hands to an adapter. The package never invokes a
callable except in the explicitly test-scoped double, which a host must never bind in production; that
rule is documented on the class, in the charter and in the capability manifest.

## Supply chain

- The runtime requirement is `php` alone; `composer audit --abandoned=fail` runs in CI over the
  development toolchain.
- Every third-party GitHub Action is pinned to a reviewed commit SHA; workflow permissions default to
  read-only and are widened only in the release job that creates the tag and the release.
- Releases are built from the merged `main` commit by automation; no human pushes a tag, and no registry
  credential enters this repository.
- The release archive is held to an exact reviewed file set and installed as a clean consumer before it
  is published.

## Reporting a vulnerability

Report privately through the GitHub security advisory form of the repository
(`https://github.com/kumwe/transaction/security/advisories/new`) rather than in a public issue. Include
the affected version, a description and, where possible, a reproduction. A fix ships as a new version
with a changelog entry naming the affected versions; released versions are never modified.

## What this package cannot promise

Durability, isolation, atomicity and correct rollback are properties of the host's adapter and store, not
of this package. A host proves them with its own database evidence; this package only states what an
adapter owes.
