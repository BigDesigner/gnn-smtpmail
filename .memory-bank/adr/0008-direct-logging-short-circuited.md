# ADR 0008: Direct Logging in Short-Circuited Deliveries

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
Brevo API short-circuits the core WordPress mail delivery path via the `pre_wp_mail` filter. This bypasses standard mail hooks on success.

## Decision
Log the delivery result directly inside `pre_wp_mail_handler` for Brevo API, and suppress duplicate logging on subsequent standard hook triggers.

## Consequences
- Guaranteed database logs for Brevo sent items even when standard hooks are bypassed.
- Avoids duplicated log rows.

## Evidence
- Implemented in [class-gnn-smtpmail.php](file:///c:/Users/bigde/.antigravity/gnn-smtpmail/includes/class-gnn-smtpmail.php#L338).

