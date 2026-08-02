# Changelog

All notable changes to this project will be documented in this file.

## [1.5.3] - 2026-08-02

### Added
- Registered GNN Product Family admin menu position slot `'79.101'` in `add_menu_page()`.
- Created `.memory-bank/adr/0010-gnn-admin-menu-position-registry.md` and updated engineering constitution specifications.

## [1.5.2] - 2026-07-06

### Fixed
- [YÜK-01] Escaped table names using `esc_sql()` and backticks in logger database queries (`CREATE TABLE`, `TRUNCATE TABLE`, `DROP TABLE`).
- [YÜK-02] Escaped table name in `uninstall.php` using `esc_sql()` and backticks.
- [ORT-04] Added path traversal verification to `file_get_contents()` for mail attachments to restrict them to `ABSPATH`.
- [ORT-03] Hardened transient cache key generation using `wp_hash()` instead of simple MD5.
- [DÜŞ-01] Fixed SMTP password saving issue by removing `sanitize_text_field()` which stripped special characters from passwords.
- [DÜŞ-03] Escaped admin action links using `esc_url()` in `gnn-smtpmail.php`.

## [1.5.1] - 2026-07-05

### Fixed
- Fixed fatal syntax error caused by a leftover catch block without a matching try block in the `check_mail_conflict_notice` function.

## [1.5.0] - 2026-07-04

### Fixed
- [KRİTİK-01] Wrapped all error_log() calls in WP_DEBUG guard to prevent sensitive data exposure in production logs.
- [GÜV-01] Escaped table name in admin raw SQL query using esc_sql().
- [GÜV-02] Added semver validation and GitHub domain whitelist to updater download URL.
- [KAL-02] Fixed empty $params edge case in get_logs() prepare calls.
- [KAL-03] Eliminated duplicate ReflectionFunction code via get_wp_mail_source() private method.

## [1.4.4] - 2026-06-23

### Changed
- Refactored diagnostics: System information box is now completely hidden unless there is an active table error, database insert failure, or wp_mail() hook conflict.
- Removed DB manual log writing test buttons and utilities from settings and log views.

## [1.4.3] - 2026-06-23

### Added
- Integrated dynamic conflict checking mechanism. If another plugin or custom code overrides the pluggable `wp_mail()` function, GNN SMTPMail will display a prominent admin notice on all admin pages to notify the administrator.

## [1.4.2] - 2026-06-23

### Added
- Integrated Reflection-based wp_mail() function definition source checker to alert users of plugin/theme conflicts hijacking email flows.
- Added comprehensive error_log debug traces across all handlers to assist in deep-inspection of mailing pipelines.

## [1.4.1] - 2026-06-23

### Fixed
- Fixed log visibility issue by making table existence verification case-insensitive.
- Bypassed global action hook reliance under Brevo API mode, logging outcomes directly.
- Added a manual database log write test utility to diagnostics block for easy validation.
- Ensured migration/table creation runs safely on updates without overwriting existing tables.

## [1.4.0] - 2026-06-23

### Added
- Integrated Brevo Senders API. Removed manual sender name/email input text fields in favor of a dynamic select dropdown displaying only active, verified senders linked to the configured API key.

## [1.3.4] - 2026-06-23

### Added
- Added a diagnostic warning/status block to the top of the Email Logs page to show active DB table status, row counts, and the last database errors.

## [1.3.3] - 2026-06-23

### Changed
- Updated plugin header author metadata to `BigDesigner` and set developer profile/repository URLs.

## [1.3.2] - 2026-06-23

### Fixed
- Fixed dbDelta SQL syntax parser bugs by converting SQL field types to all lowercase as strictly required by WordPress `dbDelta` standards.
- Strengthened db auto-migration by checking physical database table existence rather than just relying on version strings.

## [1.3.1] - 2026-06-23

### Fixed
- Database auto-migration logic: Fixed missing `dbDelta` dependency and added checks on constructor initialization to ensure custom log table is created dynamically even when deactivation/reactivation is skipped during updates.

## [1.3.0] - 2026-06-23

### Added
- Integrated Brevo HTTP API for sending emails, providing a reliable alternative to standard SMTP.
- Added dynamic fields to the settings page, toggling dynamically between Custom SMTP and Brevo API options.

## [1.2.1] - 2026-06-23

### Fixed
- Security update: Excluded local `.vscode` editor config and other developer metadata files from the repository tracking and release builds.

## [1.2.0] - 2026-06-23

### Added
- Restored `.gitignore`, release workflows, contributing guidelines, documentation, and graphic assets from the repository.

### Changed
- Unified the repository codebase with the local 1.1.0 codebase (retaining the clean, Custom-only SMTP architecture).
- Cleaned up PHP definitions and functions.

## [1.1.0]

### Changed
- Removed Brevo integration (switched to custom-only SMTP for a clean architecture).

## [1.0.0] - 2026-05-08

### Added
- Initial release of GNN SMTPMail.
- Reliable SMTP email delivery for WordPress.
- Simple admin configuration interface.
- Logging system for email debugging.
- Support for various SMTP providers.
