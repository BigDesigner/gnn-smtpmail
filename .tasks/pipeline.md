# Task Pipeline & Roadmap

This document manages active milestones, releases, and upcoming backlog items.

## 🏁 Completed Milestones

### MB — Foundation & Infrastructure
- [x] **MB-001:** Initialize project structure as a WordPress Plugin.
- [x] **MB-002:** Set up GitHub Actions for automated `.zip` releases.
- [x] **MB-003:** Create specialized Memory Bank for AI context persistence.
- [x] **MB-004:** Implement GitHub Updater for automatic core updates.
- [x] **MB-005:** Add automatic and manual GitHub Actions cache cleanup workflow.

### SEC — Security & Hardening
- [x] **SEC-001:** Implement Nonce verification for form actions (settings saving, test sending, clearing logs).
- [x] **SEC-002:** Implement `defined('ABSPATH') || exit;` guard in all PHP files.
- [x] **SEC-003:** Apply comprehensive SQL preparation using `$wpdb->prepare()`.
- [x] **SEC-004:** Add administrative hardening security headers (nosniff, sameorigin).
- [x] **SEC-005:** Escaped table names, path traversal checks for attachments, and secure transient cache key generation (v1.5.2).

### UI — Premium Design System
- [x] **UI-001:** Refine admin pages with unified grid layout and badges.
- [x] **UI-002:** Add custom plugin action links (Donate, Settings, Check Updates) to the plugins table.
- [x] **UI-003:** Implement dynamic JS settings section toggle for mailer types.
- [x] **UI-004:** Assign GNN Admin Menu Position Registry slot ('79.101').

### FEAT — Multi-Channel Email Sending
- [x] **FEAT-001:** Custom SMTP sending via PHPMailer init.
- [x] **FEAT-002:** Brevo API sending via `pre_wp_mail` filter using `wp_remote_post`.

---

## 🚀 Recent Releases

### v1.6.4 — Brevo False-Positive Elimination (2026-11-25)
- Enforced `null !== $null` short-circuit check, `Content-Type: text/plain` override, JSON `messageId`/`messageIds` validation, and skipped attachment logging in `pre_wp_mail_handler()`.
- Added shared `get_brevo_senders_status()` and `is_wp_mail_conflict()` helpers to remove false `Aktif` badges, silent empty-sender states, and empty-path conflict warnings; updated `.pot` (127 strings).

### v1.6.3 — Modern Card UI, SMTP Presets & Log Export (2026-11-25)
- Added top tab navigation, `.gnn-card` / `.gnn-form-row` layout, and accessible `role="switch"` toggles (`AÇIK`/`KAPALI`) across all admin pages.
- Added Overview stats summary, one-click SMTP provider presets, dynamic auth toggling, Brevo sender refresh button, and `HTML`/`Özel E-posta` test switches.
- Implemented CSV/JSON log export (`FEAT-EXPORT`) with CSV formula-injection mitigation and confirmation prompt on log clearing; updated `.pot` (120 strings).

### v1.6.2 — Unified GNN Updater Standard (2026-11-21)
- Unified `inc/updater.php` to the shared GNN GitHub Updater standard.

### v1.6.1 — Security, Updater, WCAG & WP 7.1.5 Hardening (2026-11-20)
- Masked stored SMTP password / Brevo API key in admin HTML inputs with preserve-on-empty and explicit clear checkboxes.
- Restricted Brevo attachment validation to `WP_CONTENT_DIR` / `uploads` and blocked `.php`/`.phtml`/`wp-config.php`.
- Fixed GitHub Updater `zipball_url` / CDN host allowlist, `$args->slug`, and `WP_Filesystem()` initialization.
- Fixed `prune_old_logs()` timezone (`wp_date()`), `SMTPAutoTLS` for `none`, `Referrer-Policy`, WCAG markup, CSS classes, `.pot` extraction (90 strings), and `Tested up to: 7.1.5`.

### v1.6.0 — Bulletproof Hardening & WP 7.1.4 Compatibility (2026-11-18)
- Eliminated per-request `SHOW TABLES` query and global `upgrade.php` loading.
- Added Brevo API v3 `Cc:` and `Bcc:` header parsing and multi-root attachment path validation.
- Added automated daily WP-Cron log pruning (`gnn_smtpmail_daily_cleanup`) and `wp-config.php` constant overrides.
- Fixed GitHub Updater error transient caching, HTTPS/CDN validation, `$overwrite` flag, and WP 6.7+ `init` textdomain loading.
- Removed jQuery dependency (Vanilla JS), added WCAG accessibility attributes, and updated `cleanup-cache.yml` to `actions/github-script@v9`.

### v1.5.3 — GNN Admin Menu Registry Slot ('79.101') (2026-08-02)
- Assigned GNN Product Family admin menu position slot `'79.101'`.
- Added ADR 0010 and updated engineering constitution specs.

### v1.5.2 — Full Security Audit Hardening (2026-07-06)
- Escaped logger queries table names with `esc_sql()` and backticks (YÜK-01, YÜK-02, ORT-01).
- Path traversal verification for email attachments (ORT-04).
- Hardened transient cache key generation using `wp_hash()` (ORT-03).
- Fixed SMTP password saving special characters issues (DÜŞ-01).

### v1.5.1 — Leftover catch Block Fix (2026-07-05)
- Fixed fatal compile-time syntax error inside admin notices conflict check.

### v1.5.0 — Security & Hardening Sprint (2026-07-04)
- Wrapped all `error_log` statements in `WP_DEBUG` conditionals.
- Escaped table name in admin raw SQL query count.
- Added semver validation and domain whitelist verification in GitHub Updater.

---

## 🏃 Active Sprint Tasks (v1.6.3-ui-export)
- [x] **TASK-UI-01 (Tabbed Card UI & Switches):** Implement unified tab bar, `.gnn-card` rows, flow-safe CSS switches, and `Genel Bakış` stats card.
- [x] **TASK-UI-02 (SMTP Presets & Brevo UX):** Add SMTP provider presets, conditional auth fields, Brevo sender refresh, and preserve Brevo sender on Custom SMTP saves.
- [x] **TASK-UI-03 (Test Mail Toggles & i18n):** Add `HTML` / `Özel E-posta` toggles and extract 120 strings into `languages/gnn-smtpmail.pot`.
- [x] **FEAT-EXPORT:** Implement CSV/JSON log export mechanism with formula-injection protection and clear confirmation.

### Completed Sprint (v1.6.1-audit-hardening)
- [x] **TASK-AUD-01 (Secrets & Attachments):** Remove plaintext secrets from DOM inputs and restrict Brevo attachment roots to `WP_CONTENT_DIR` / `uploads`.
- [x] **TASK-AUD-02 (Updater & CI):** Allow `api.github.com` / `release-assets.githubusercontent.com` in Updater, add `$args->slug` / `WP_Filesystem()` guards, and add CRLF strip in `release.yml`.
- [x] **TASK-AUD-03 (Timezone, SMTP & Headers):** Fix `prune_old_logs()` timezone (`wp_date()`), `SMTPAutoTLS` for `none`, port `1–65535`, and `Referrer-Policy`.
- [x] **TASK-AUD-04 (WCAG, i18n & WP 7.1.5):** Fix nested `<p>` / `<h2>` hierarchy, extract CSS classes, populate `gnn-smtpmail.pot`, and bump `Tested up to: 7.1.5`.

### Completed Sprint (v1.6.0-bulletproof)
- [x] **TASK-BP-01 (Brevo CC/BCC):** Implement RFC & Brevo v3 JSON payload parsing for `Cc:` and `Bcc:` headers in `pre_wp_mail_handler`.
- [x] **TASK-BP-02 (Resilient Attachments):** Implement multi-root (`WP_CONTENT_DIR`, uploads) path validation with `wp_normalize_path()`.
- [x] **TASK-BP-03 (Performance / Lazy DB):** Eliminate `SHOW TABLES` on every request, optimize schema check to version change/activation, and remove global `upgrade.php` load.
- [x] **TASK-BP-04 (Automated Log Rotation):** Implement scheduled WP-Cron daily log pruning for entries older than 30 days.
- [x] **TASK-BP-05 (wp-config.php Constants):** Implement `GNN_SMTPMAIL_SMTP_PASS` & `GNN_SMTPMAIL_BREVO_KEY` credential overrides with admin UI indicator.
- [x] **TASK-BP-06 (Multisite & Cleanup):** Ensure uninstall and deactivation cleanly clean up all options and support network installs.
- [ ] **TEST-UPDATER:** Manually verify that the plugin's Check Updates action works as expected in a WordPress admin context.

---

## 📂 Backlog
- [ ] **UI-GLOW:** Implement GNN Premium glassmorphism design system to the admin settings page.
