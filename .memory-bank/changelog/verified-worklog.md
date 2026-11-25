# Verified Worklog

This document tracks all completed modifications, releases, and validation results.

## 🏁 Completed Milestones

### MB — Foundation & Infrastructure
- **MB-001:** Initialize project structure as a WordPress Plugin. (Verified)
- **MB-002:** Set up GitHub Actions for automated `.zip` releases. (Verified)
- **MB-003:** Create specialized Memory Bank for AI context persistence. (Verified)
- **MB-004:** Implement GitHub Updater for automatic core updates. (Verified)

### SEC — Security & Hardening
- **SEC-001:** Implement Nonce verification for form actions. (Verified)
- **SEC-002:** Implement `defined('ABSPATH') || exit;` guard in all PHP files. (Verified)
- **SEC-003:** Apply comprehensive SQL preparation using `$wpdb->prepare()`. (Verified)
- **SEC-004:** Add administrative hardening security headers (nosniff, sameorigin). (Verified)
- **SEC-005:** Escaped table names, path traversal checks for attachments, and secure transient cache key generation (v1.5.2). (Verified)

### UI — Premium Design System
- **UI-001:** Refine admin pages with unified grid layout and badges. (Verified)
- **UI-002:** Add custom plugin action links to the plugins table. (Verified)
- **UI-003:** Implement dynamic JS settings section toggle for mailer types. (Verified)

### FEAT — Multi-Channel Email Sending
- **FEAT-001:** Custom SMTP sending via PHPMailer init. (Verified)
- **FEAT-002:** Brevo API sending via `pre_wp_mail` filter using `wp_remote_post`. (Verified)

### SENTINEL — Agent Memory Bank
- **SENTINEL-MIGRATE:** Migrate documentation and AI rules to Sentinel Agent Memory Bank structure. (Verified)

## 🚀 Recent Releases

### v1.6.4 — Brevo False-Positive Elimination (2026-11-25)
- Enforced `null !== $null` short-circuit respect and `Content-Type: text/plain` overrides in `pre_wp_mail_handler()`.
- Verified JSON `messageId`/`messageIds` on HTTP `201`/`202` and logged `Message ID` plus any skipped/blocked attachment names.
- Added shared `get_brevo_senders_status()` helper to eliminate false `Aktif` badges and silent empty-sender states across `Genel Bakış`, `Ayarlar`, and `E-posta Testi`.
- Unified `is_wp_mail_conflict()` to prevent false-positive conflict notices when `get_wp_mail_source()` returns `''`; updated `languages/gnn-smtpmail.pot` (127 strings).

### v1.6.3 — Modern Card UI, SMTP Presets & Log Export (2026-11-25)
- Unified all 4 admin screens (`Genel Bakış`, `Ayarlar`, `E-posta Testi`, `Loglar`) with tabbed navigation, card row layouts, and flow-safe `role="switch"` toggles.
- Added `Genel Bakış` System Status summary (`GNN_SMTPMail_Logger::get_stats()`), one-click SMTP provider presets, dynamic auth fields, and Brevo sender refresh button.
- Added `HTML` / `Özel E-posta` toggles on `E-posta Testi` and CSV/JSON log export (`FEAT-EXPORT`) with CSV formula-injection protection and clear confirmation.
- Preserved Brevo sender metadata when saving Custom SMTP settings and updated `languages/gnn-smtpmail.pot` (120 strings).

### v1.6.2 — Unified GNN Updater Standard (2026-11-21)
- Unified `inc/updater.php` to the shared GNN GitHub Updater standard with API + HTML/HEAD web fallback and SSRF allowlist.

### v1.6.1 — Security, Updater, WCAG & WP 7.1.5 Hardening (2026-11-20)
- Removed plaintext SMTP password / Brevo API key echoing from HTML `value` attributes; added preserve-on-empty and explicit clear checkboxes.
- Restricted Brevo attachment validation to `WP_CONTENT_DIR` and `wp_upload_dir()` with `.php`/`.phtml`/`wp-config.php` blocking.
- Added `api.github.com` and `release-assets.githubusercontent.com` to Updater HTTPS allowlist, plus `$args->slug` and `WP_Filesystem()` guards.
- Fixed `prune_old_logs()` timezone mismatch (`wp_date()`), `SMTPAutoTLS` for `none` encryption, port `1–65535` clamping, and `Referrer-Policy` header.
- Populated `languages/gnn-smtpmail.pot` (90 strings), moved inline styles to `assets/admin.css`, fixed WCAG markup, and updated `Tested up to: 7.1.5`.

### v1.6.0 — Bulletproof Hardening & WP 7.1.4 Compatibility (2026-11-18)
- Eliminated per-request `SHOW TABLES` query and global `upgrade.php` loading; added lazy DB version check.
- Added Brevo API v3 `Cc:` and `Bcc:` parsing, daily WP-Cron log pruning, and `wp-config.php` constant overrides.
- Fixed GitHub Updater error transient caching, `init` (priority 1) textdomain loading, Vanilla JS migration, and multisite uninstall.

### v1.5.2 — Full Security Audit Hardening (2026-07-06)
- Escaped logger queries table names with `esc_sql()` and backticks.
- Path traversal verification for email attachments.
- Hardened transient cache key generation using `wp_hash()`.
- Fixed SMTP password saving special characters issues.

### v1.5.1 — Leftover catch Block Fix (2026-07-05)
- Fixed fatal compile-time syntax error inside admin notices conflict check.

### v1.5.0 — Security & Hardening Sprint (2026-07-04)
- Wrapped all `error_log` statements in `WP_DEBUG` conditionals.
- Escaped table name in admin raw SQL query count.
- Added semver validation and domain whitelist verification in GitHub Updater.
