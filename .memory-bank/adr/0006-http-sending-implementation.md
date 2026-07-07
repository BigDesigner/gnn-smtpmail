# ADR 0006: HTTP Sending Implementation (Brevo API)

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
When Brevo API is selected, we need to bypass the default PHPMailer pipeline and send via HTTPS.

## Decision
Hook into the `pre_wp_mail` filter to intercept outbound mail calls.

## Consequences
- Intercepts all outgoing mail sitewide.
- Dispatch request via standard WordPress HTTP client (`wp_remote_post`).
- Returns a boolean result to short-circuit the default mail dispatcher.

## Evidence
- Configured in [class-gnn-smtpmail.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/includes/class-gnn-smtpmail.php#L77).
