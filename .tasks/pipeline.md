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

## 🏃 Active Sprint Tasks (v1.6.0-bulletproof)
- [x] **TASK-BP-01 (Brevo CC/BCC):** Implement RFC & Brevo v3 JSON payload parsing for `Cc:` and `Bcc:` headers in `pre_wp_mail_handler`.
- [x] **TASK-BP-02 (Resilient Attachments):** Implement multi-root (`ABSPATH`, `WP_CONTENT_DIR`, uploads) path validation with `wp_normalize_path()`.
- [x] **TASK-BP-03 (Performance / Lazy DB):** Eliminate `SHOW TABLES` on every request, optimize schema check to version change/activation, and remove global `upgrade.php` load.
- [x] **TASK-BP-04 (Automated Log Rotation):** Implement scheduled WP-Cron daily log pruning for entries older than 30 days.
- [x] **TASK-BP-05 (wp-config.php Constants):** Implement `GNN_SMTPMAIL_SMTP_PASS` & `GNN_SMTPMAIL_BREVO_KEY` credential overrides with admin UI indicator.
- [x] **TASK-BP-06 (Multisite & Cleanup):** Ensure uninstall and deactivation cleanly clean up all options and support network installs.
- [ ] **TEST-UPDATER:** Manually verify that the plugin's Check Updates action works as expected in a WordPress admin context.

---

## 📂 Backlog
- [ ] **FEAT-EXPORT:** Implement CSV/JSON log export mechanism.
- [ ] **UI-GLOW:** Implement GNN Premium glassmorphism design system to the admin settings page.
