# Project Snapshot: GNN SMTPMail

## Project Status
- **Current Version:** 1.4.4
- **Last Sync:** 2026-06-23
- **Status:** Production Ready / Conditional Diagnostics Completed

## Core Functionality
- Dual sending channels: Custom SMTP or Brevo API (dynamic selection).
- Custom SMTP configuration (Host, Port, SMTP Auth, Username, Password, SMTPSecure).
- Brevo API integration via HTTP endpoint (`https://api.brevo.com/v3/smtp/email`) using `wp_remote_post`.
- Sender name and sender email address filtering.
- Test mail delivery panel with result feedback.
- Logging system storing delivery records (succeeded and failed) in a custom DB table, tracking the active channel (Custom vs. Brevo).
- Log management panel with pagination, filtering by status, and truncate functionality.
- GitHub Updater integration for automatic updates via GitHub releases.

## Active Components
- `gnn-smtpmail.php`: Main plugin loader, core activation hooks, and plugin action links.
- `includes/class-gnn-smtpmail.php`: Main plugin class. Handles PHPMailer hooks, Brevo API integration (`pre_wp_mail` hook), sender filters, and mail result hooks.
- `includes/class-gnn-smtpmail-logger.php`: Handles logging table creation, insert operations, retrieval, and truncation.
- `includes/class-gnn-smtpmail-admin.php`: Handles admin menu, settings rendering with dynamic JS toggles, forms saving, and test mail dispatch.
- `inc/updater.php`: GitHub update checking and directory recovery installation logic.
- `assets/admin.css`: Admin styling with grid layout, alignment utilities, and badge styles.
- `assets/admin.js`: Dynamic settings section toggling between Custom SMTP and Brevo.

## Recent Changes
- **v1.4.4:** Refactored diagnostic notice box to display conditionally only when issues/conflicts are present. Removed manual DB log test functionalities.
- **v1.4.3:** Added global admin notices for wp_mail conflicts.
- **v1.4.2:** Added Reflection-based wp_mail() conflict detector and detailed error_log tracing across all mail flow functions.
- **v1.4.1:** Fixed log visibility issues with case-insensitive table existence check and direct Brevo sending logging. Added manual DB test logging action on diagnostic bar.
- **v1.4.0:** Integrated Brevo Senders API to display verified senders dropdown, avoiding manual sender inputs.
- **v1.3.4:** Added diagnostic warning/status block to Email Logs page.
- **v1.3.2:** Fixed dbDelta SQL syntax casing and improved auto-migration logic.
- **v1.3.1:** Fixed database logging and auto-migration helper class inclusions.
- **v1.3.0:** Added Brevo API integration (switched using `pre_wp_mail` filter), updated admin options forms and JS dynamic sections.
- **v1.2.1:** Security update (excluded `.vscode` from releases and git index).
- **v1.2.0:** Integrated GitHub Updater, added plugins list action links, restored project resources/workflows, and bumped version.
- **v1.1.0:** Switched to Custom-only SMTP architecture (removed Brevo).
- **v1.0.0:** Initial Release (Brevo + Custom SMTP).

## Next Steps
- Implement advanced log export functionality (CSV/JSON).
- Add support for attachment logging details (without storing raw files).
