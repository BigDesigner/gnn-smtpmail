# ADR 0004: Log Success and Failure Hooks

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
We need to capture delivery success or failure to write records into the database log table.

## Decision
Use `wp_mail_succeeded` and `wp_mail_failed` action hooks.

## Consequences
- Cleanly log recipients, subjects, status, and error messages without hacking core files.
- Automatically handles PHP exception capture propagated by PHPMailer.

## Evidence
- Configured in [class-gnn-smtpmail.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/includes/class-gnn-smtpmail.php#L84-L85).
