# Changelog

All notable changes to this project will be documented in this file.

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
