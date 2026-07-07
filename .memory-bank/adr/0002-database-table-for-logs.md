# ADR 0002: Database Table for Logs

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
Logging all email delivery attempts is a core feature. We need to decide where to store success and failure entries.

## Decision
Create a custom SQL table (`wp_gnn_smtpmail_logs`) instead of relying on default WordPress post types (`wp_posts`) or options (`wp_options`).

## Consequences
- High-speed insertion operations.
- Keeps default WordPress core tables lightweight.
- Simplifies truncation and cleanup without interfering with posts.

## Evidence
- Checked in [class-gnn-smtpmail-logger.php](file:///c:/Users/bigde/.antigravity/gnn-smtpmail/includes/class-gnn-smtpmail-logger.php).

