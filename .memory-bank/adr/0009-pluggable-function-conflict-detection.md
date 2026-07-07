# ADR 0009: Pluggable Function Conflict Detection

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
WordPress allows plugins to override core pluggable functions like `wp_mail()`. If another plugin does so, our plugin is completely bypassed and cannot configure PHPMailer or intercept mails.

## Decision
Use PHP's `ReflectionFunction` API to fetch the absolute filename where `wp_mail` is declared.

## Consequences
- Displays a clear admin warning block if a conflict is detected.
- Prevents silent failures of GNN SMTPMail.

## Evidence
- Implemented in [class-gnn-smtpmail-admin.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/includes/class-gnn-smtpmail-admin.php#L502-L510).
