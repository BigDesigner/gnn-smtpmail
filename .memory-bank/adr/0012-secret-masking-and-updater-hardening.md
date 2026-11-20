# ADR 0012: Secret Masking, Updater Host Allowlist, and WP 7.1.5+ Compliance

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-11-20
- **Category**: Security & Maintainability
- **Supersedes**: None
- **Superseded By**: None

## Context
A comprehensive security, compatibility, and standards audit conducted on 2026-11-20 against WordPress 7.1.5 (and upcoming 7.2 RC1) identified several remaining hardening gaps in v1.6.0:
1. Stored SMTP passwords and Brevo API keys were rendered in plain text inside HTML `<input type="password" value="...">` attributes when `wp-config.php` constants were not used.
2. Brevo attachment validation included `ABSPATH` as an allowed root, risking exposure of core or configuration files if passed to `wp_mail()`.
3. `GNN_SMTPMail_Updater` fell back to `$body->zipball_url` (`api.github.com`) when no `.zip` asset was present, yet `api.github.com` and `release-assets.githubusercontent.com` were absent from the HTTPS host allowlist.
4. `GNN_SMTPMail_Logger::prune_old_logs()` compared `gmdate()` (UTC) against `logged_at` stored with `current_time( 'mysql' )` (site local time).
5. `admin_hardening_headers()` sent `Referrer-Policy: no-referrer-when-downgrade`, downgrading WordPress core's stricter `strict-origin-when-cross-origin` header.
6. `gnn-smtpmail.pot` was empty, diagnostic strings were unlocalized, `paginate_links()` lacked `wp_kses_post()`, and inline styles remained in admin templates.

## Decision
1. **DOM Secret Masking**: Leave password/API key inputs empty on render when stored in `wp_options`, show a masked placeholder (`••••••••`), preserve existing secrets on empty form submissions, and provide explicit clear checkboxes (`clear_smtp_password`, `clear_brevo_api_key`).
2. **Attachment Root Tightening**: Remove `ABSPATH` from default attachment roots; allow only `WP_CONTENT_DIR` and `wp_upload_dir()['basedir']` (filterable via `gnn_smtpmail_allowed_attachment_roots`) and block `.php`, `.phtml`, and `wp-config.php`.
3. **Updater Allowlist & Guards**: Add `api.github.com` and `release-assets.githubusercontent.com` to the HTTPS host allowlist, check `! empty( $args->slug )`, initialize `WP_Filesystem()` if unset, and include `requires`/`tested`/`requires_php` in `check_for_update()`.
4. **Timezone & Cron Alignment**: Use `wp_date()` in `prune_old_logs()` to match `current_time( 'mysql' )`, and scope `ensure_cron_scheduled()` to `is_admin()`.
5. **Admin Hardening, WCAG & i18n**: Set `Referrer-Policy: strict-origin-when-cross-origin`, disable `SMTPAutoTLS` when `smtp_secure` is `none`, clamp SMTP port to `1–65535`, wrap `paginate_links()` with `wp_kses_post()`, move inline styles to `assets/admin.css`, fix heading hierarchy (`h2`) and invalid nested `<p>` markup, and populate `languages/gnn-smtpmail.pot`.

## Evidence
- Implemented in commits `6f01a97` and `651fd48` (`v1.6.1`).
