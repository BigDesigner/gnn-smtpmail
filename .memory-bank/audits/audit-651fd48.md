# Security & Compatibility Audit Report (v1.6.0 -> v1.6.1)

- **Audit Date:** 2026-11-20
- **Baseline Commit:** `86b1046` (v1.6.0)
- **Remediation Commits:** `6f01a97`, `651fd48` (v1.6.1, tag `v1.6.1`)
- **Target Platform:** WordPress 7.1.5 (current stable) / 7.2 RC1, PHP 7.4+ (recommended 8.3–8.5)

## CVE & Security Posture Summary
- **CVE-2026-4020 (Gravity SMTP unauthenticated REST report):** Not affected (no REST endpoints or `wp_ajax_nopriv_` hooks).
- **CVE-2025-24000 (Post SMTP log disclosure / ATO):** Not affected (logs require `manage_options`; email bodies are not stored).
- **CVE-2025-0521 / CVE-2026-75962 (Post SMTP stored XSS):** Not affected (`sanitize_textarea_field()` on insert, `esc_html()` on render).

## Remediated Findings
1. **DOM Secret Exposure:** Removed plaintext `value` echoing for SMTP password and Brevo API key in `class-gnn-smtpmail-admin.php`.
2. **Attachment Root Scope:** Removed `ABSPATH` from default allowed attachment roots and blocked `.php`/`.phtml`/`wp-config.php` in `class-gnn-smtpmail.php`.
3. **Updater Allowlist & Guards:** Added `api.github.com` and `release-assets.githubusercontent.com` to `updater.php` allowlist; added `$args->slug` and `WP_Filesystem()` guards; updated `Tested up to` to `7.1.5`.
4. **Timezone & Cron:** Switched `prune_old_logs()` to `wp_date()` and scoped `ensure_cron_scheduled()` to `is_admin()`.
5. **Headers, SMTP & Sanitization:** Updated `Referrer-Policy` to `strict-origin-when-cross-origin`, disabled `SMTPAutoTLS` for `none`, clamped port `1–65535`, sanitized parsed Brevo sender fields, and wrapped `paginate_links()` in `wp_kses_post()`.
6. **WCAG, CSS & i18n:** Fixed nested `<p>` and heading hierarchy, moved inline styles to `assets/admin.css`, localized diagnostic/pagination strings, and generated `languages/gnn-smtpmail.pot` (90 strings).
7. **CI/CD:** Added `tr -d '\r'` CRLF guard in `.github/workflows/release.yml`.

## Verification
- `php -l` passed across all 8 PHP files with 0 syntax errors after final edits.
