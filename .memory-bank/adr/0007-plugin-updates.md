# ADR 0007: Plugin Updates

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
We distribute this plugin directly (GitHub), bypassing the official WordPress.org repository. We need a way to support automatic updates.

## Decision
Integrate a GitHub Releases API-based updater class that hooks into core WordPress update mechanisms.

## Consequences
- Resolves update packages from GitHub Release Assets.
- Verifies semver and filters download domains to prevent SSRF or malicious redirects.

## Evidence
- Implemented in [updater.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/inc/updater.php).
