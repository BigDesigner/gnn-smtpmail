# ADR 0003: Mail System Hook

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
When Custom SMTP is selected, we need to modify the PHPMailer configuration before mail is sent.

## Decision
Hook into the native WordPress `phpmailer_init` action.

## Consequences
- Allows clean configuration of the PHPMailer object (Host, Port, SMTPAuth, SMTPSecure).
- Follows native WordPress pathways without overriding core mail processes.

## Evidence
- Configured in [class-gnn-smtpmail.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/includes/class-gnn-smtpmail.php#L74).
