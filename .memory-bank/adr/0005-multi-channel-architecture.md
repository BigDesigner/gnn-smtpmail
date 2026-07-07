# ADR 0005: Multi-Channel Architecture

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
Standard SMTP is prone to port blocking on restrictive hosting environments. We need a fallback channel.

## Decision
Design a dynamic multi-channel architecture supporting both Custom SMTP (via native PHPMailer) and Brevo API (via HTTP calls).

## Consequences
- Bypasses SMTP blocks using standard HTTPS outbound calls (port 443).
- Delivers transactional email faster and with higher deliverability.

## Evidence
- Implemented in [class-gnn-smtpmail.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/includes/class-gnn-smtpmail.php#L164-L172).
