# Changelog

All notable changes to this project will be documented in this file.

## [1.6.3] - 2026-11-25

### Added
- Modern tabbed card layout, row dividers, and accessible `role="switch"` toggle switches across all admin screens (`Genel Bakış`, `Ayarlar`, `E-posta Testi`, `Loglar`).
- System Status Overview card on `Genel Bakış` displaying active mailer channel, total/success/error log counts (`GNN_SMTPMail_Logger::get_stats()`), `wp_mail()` conflict status, and manual update check shortcut.
- One-click SMTP provider presets (`Gmail`, `Outlook / M365`, `Yandex`, `Zoho`, `Amazon SES`) and dynamic `SMTP Kimlik Doğrulama` field toggling.
- Nonce-protected **Göndericileri Yenile** button to refresh the Brevo verified senders cache on demand.
- `HTML` and `Özel E-posta` toggle switches on `E-posta Testi` supporting both predefined and custom test templates.
- CSV and JSON log export (`GNN_SMTPMail_Logger::get_logs_for_export()`) with CSV formula-injection mitigation, UTF-8 BOM, and confirmation dialog on log clearing.

### Fixed
- Preserved previously selected Brevo sender metadata when saving Custom SMTP settings without the Brevo sender dropdown present in `$_POST`.
- Replaced `position: fixed` switch input hiding in `assets/admin.css` with zero-conflict flow-safe `appearance: none` styling and direct-child row border selectors.
- Updated `languages/gnn-smtpmail.pot` with all new admin UI strings.

## [1.6.2] - 2026-11-21

### Changed
- Unified `inc/updater.php` to the shared GNN GitHub Updater standard.

## [1.6.1] - 2026-11-20

### Security
- Removed plaintext echoing of stored SMTP password and Brevo API key into HTML `value` attributes; empty submissions preserve existing secrets, with optional explicit clear checkboxes.
- Restricted Brevo attachment path validation to `WP_CONTENT_DIR` and `wp_upload_dir()` (removing `ABSPATH` root) and explicitly blocked `.php`, `.phtml`, and `wp-config.php` files.
- Updated `Referrer-Policy` header in `admin_hardening_headers()` from `no-referrer-when-downgrade` to `strict-origin-when-cross-origin` to avoid downgrading WordPress core policy.
- Added explicit `sanitize_email()` / `sanitize_text_field()` sanitization for parsed Brevo sender fields and wrapped `paginate_links()` output with `wp_kses_post()`.

### Fixed
- Added `api.github.com` and `release-assets.githubusercontent.com` to Updater HTTPS host allowlist so `zipball_url` fallback and GitHub release asset CDN redirects succeed; added `$args->slug` and `$wp_filesystem` guards.
- Fixed timezone mismatch in `GNN_SMTPMail_Logger::prune_old_logs()` by using `wp_date()` consistently with `current_time( 'mysql' )`.
- Disabled `SMTPAutoTLS` when `smtp_secure` is `none` and clamped SMTP port values to `1-65535`.
- Fixed invalid nested `<p>` HTML markup in `page_test()`, heading hierarchy (`h2`) in `page_custom()`, and CRLF stripping in `.github/workflows/release.yml`.

### Changed
- Scoped `ensure_cron_scheduled()` to `is_admin()` to eliminate unnecessary frontend `init` checks.
- Moved inline admin styles to `assets/admin.css`, expanded `:focus-visible` coverage, localized all diagnostic/pagination strings, and populated `languages/gnn-smtpmail.pot`.
- Updated `Tested up to` metadata to WordPress `7.1.5` and refreshed `readme.txt` feature summary.

## [1.6.0] - 2026-11-18

### Added
- Brevo API v3 `Cc:` and `Bcc:` header parsing into structured JSON `cc`/`bcc` arrays.
- Automated daily WP-Cron log pruning (`gnn_smtpmail_daily_cleanup`, default 30 days via `gnn_smtpmail_log_retention_days` filter).
- `wp-config.php` constant overrides (`GNN_SMTPMAIL_SMTP_PASS`, `GNN_SMTPMAIL_BREVO_KEY`) with admin UI indicators.
- `load_plugin_textdomain()` on `init` (priority 1) and `languages/gnn-smtpmail.pot` translation template.
- Multisite-aware `uninstall.php` cleanup for all plugin options, transients, cron hooks, and log tables.
- PHP syntax lint step (`php -l`) in `.github/workflows/release.yml`.

### Changed
- Eliminated per-request `SHOW TABLES` query and global `upgrade.php` loading; schema checks now run lazily on activation/version change.
- Expanded attachment path validation to normalized `ABSPATH`, `WP_CONTENT_DIR`, and `wp_upload_dir()` roots.
- Replaced jQuery dependency in `assets/admin.js` with Vanilla JS; added WCAG `<label for>`, `scope="col"`, `:focus-visible`, and responsive table wrapper.
- Updated `Requires at least` (`6.2`), `Tested up to` (`7.1.4`), `Requires PHP` (`7.4`), and `actions/github-script@v9`.

### Fixed
- Fixed GitHub Updater error transient caching (`'error'` sentinel instead of `false`), `get_plugin_data(..., false, false)`, HTTPS scheme check, `objects.githubusercontent.com` host allowlist, and `$wp_filesystem->move(..., true)` overwrite flag.
- Added `rel="noopener noreferrer"` to external Donate link and capability guards to all admin page renderers.

## [1.5.4] - 2026-08-05

### Changed
- Repository restructured: plugin files moved from the repo root into a `gnn-smtpmail/` subdirectory so the repo mirrors the shipped plugin layout.
- Simplified `release.yml` to zip the `gnn-smtpmail/` directory directly instead of rsyncing excluded paths into a temporary build folder.

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
