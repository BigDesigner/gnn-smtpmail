# Project Snapshot: GNN SMTPMail

## Project Status
- **Current Version:** 1.2.0
- **Last Sync:** 2026-06-23
- **Status:** Production Ready / GitHub Updater Integration Sprint Completed

## Core Functionality
- Custom SMTP configuration (Host, Port, SMTP Auth, Username, Password, SMTPSecure).
- Sender name and sender email address filtering.
- Test mail delivery panel with result feedback.
- Logging system storing delivery records (succeeded and failed) in a custom DB table.
- Log management panel with pagination, filtering by status, and truncate functionality.
- GitHub Updater integration for automatic updates via GitHub releases.

## Active Components
- `gnn-smtpmail.php`: Main plugin loader, core activation hooks, and plugin action links.
- `includes/class-gnn-smtpmail.php`: Main plugin class. Handles PHPMailer hooks, sender filters, and mail result hooks.
- `includes/class-gnn-smtpmail-logger.php`: Handles logging table creation, insert operations, retrieval, and truncation.
- `includes/class-gnn-smtpmail-admin.php`: Handles admin menu, submenus, settings rendering, forms saving, and test mail dispatch.
- `inc/updater.php`: GitHub update checking and directory recovery installation logic.
- `assets/admin.css`: Admin styling with grid layout, alignment utilities, and badge styles.
- `assets/admin.js`: Reserved scripts for future UI enhancement.

## Recent Changes
- **v1.2.0:** Integrated GitHub Updater, added plugins list action links, restored project resources/workflows, and bumped version.
- **v1.1.0:** Switched to Custom-only SMTP architecture (removed Brevo).
- **v1.0.0:** Initial Release (Brevo + Custom SMTP).

## Next Steps
- Implement advanced log export functionality (CSV/JSON).
- Add support for attachment logging details (without storing raw files).
