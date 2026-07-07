# ADR 0001: Initial Tech Stack Selection

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-07-08

## Context
We need to build a secure, lightweight, and customizable WordPress plugin for configuring mail delivery via Custom SMTP or the Brevo HTTP API.

## Decision
Select the following baseline technologies:
- **Core language:** PHP (complying with WordPress plugin requirements).
- **Styling & UI:** Vanilla CSS using custom properties (CSS variables) for maximum theme compatibility (Dark/Light modes).
- **Core modules:** WordPress pluggable hooks, custom DB logs, and PHPMailer.

## Consequences
- Ensure clean code isolation through namespace prefixes.
- Minimize dependencies to prevent vendor conflicts.

## Evidence
- Found in main file [gnn-smtpmail.php](file:///c:/Users/bigde/Documents/Project/gnn-smtpmail/gnn-smtpmail.php).
