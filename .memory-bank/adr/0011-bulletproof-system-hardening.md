# ADR 0011: Hardening Architecture for Production Resiliency (Bulletproof Mail & Database Operations)

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-10-15
- **Category**: Structural Topology
- **Supersedes**: None
- **Superseded By**: None

## Context
A critical architectural review of GNN SMTPMail in high-traffic, production-grade WordPress environments identified several architectural vulnerabilities and operational bottlenecks:
1. **Brevo CC & BCC Delivery Failure**: Outgoing emails containing `Cc:` and `Bcc:` headers (e.g. WooCommerce notifications, Gravity Forms, Contact Form 7) placed these addresses into generic custom headers. The Brevo API v3 (`/v3/smtp/email`) ignores raw header copies and requires structured `"cc"` and `"bcc"` arrays in the JSON request body.
2. **Silent Attachment Drop in Non-Standard Roots**: The attachment path traversal guard strictly evaluated `0 === strpos($real_path, realpath(ABSPATH))`. In modern decoupled or symlinked setups (Roots Bedrock, custom `WP_CONTENT_DIR`, or Windows drive-letter case discrepancies), attachment checks evaluate to false, silently stripping valid file attachments.
3. **High-Frequency Database Overhead**: The plugin constructor executed `SHOW TABLES LIKE 'wp_gnn_smtpmail_logs'` on every single request through `plugins_loaded`, generating redundant database overhead on high-traffic sites. Additionally, `wp-admin/includes/upgrade.php` was loaded globally on all frontend requests.
4. **Unbounded Log Table Growth**: The custom logging table (`wp_gnn_smtpmail_logs`) lacked lifecycle rotation, leading to table bloat and administrative UI performance degradation over extended operation.
5. **Plaintext Credential Exposure**: SMTP passwords and Brevo API keys resided solely in `wp_options` as plaintext, without an environmental override mechanism via `wp-config.php`.

## Decision
We establish 5 architectural hardening standards across the plugin pipeline:

1. **RFC & Brevo Payload Compliance for CC/BCC**:
   - `pre_wp_mail_handler` must explicitly extract `Cc:` and `Bcc:` headers, validate emails, and structure them into Brevo v3 JSON payloads under `cc` and `bcc` keys (`[{"email": "...", "name": "..."}]`).
2. **Multi-Root Normalized Attachment Path Validation**:
   - Validate attachments using `wp_normalize_path(realpath($attachment))` against safe content/upload roots (`WP_CONTENT_DIR` and `wp_upload_dir()['basedir']`, filterable via `gnn_smtpmail_allowed_attachment_roots`), while explicitly blocking `.php`, `.phtml`, and `wp-config.php` files (refined in v1.6.1 to exclude `ABSPATH` root).
3. **Zero-Query Frontend Execution & Lazy DB Delta**:
   - Eliminate runtime `SHOW TABLES` checks from `plugins_loaded`.
   - Restrict table verification to plugin activation hooks and version upgrade events (`get_option('gnn_smtpmail_db_version') !== GNN_SMTPMAIL_VERSION`).
   - Move `require_once ABSPATH . 'wp-admin/includes/upgrade.php'` exclusively into `GNN_SMTPMail_Logger::create_table()`.
4. **Automated WP-Cron Log Pruning**:
   - Implement a daily recurring cron job (`gnn_smtpmail_daily_cleanup`) executing a pruned deletion query for log entries older than 30 days (filterable via `gnn_smtpmail_log_retention_days`).
5. **Environment Configuration Constants**:
   - Support `GNN_SMTPMAIL_SMTP_PASS` and `GNN_SMTPMAIL_BREVO_KEY` defined in `wp-config.php`. When defined, these constants override `wp_options` values and display a secure "Defined in wp-config.php" indicator in the admin UI.

## Consequences
- **Positive**: Complete delivery fidelity for CC/BCC recipients on Brevo.
- **Positive**: Robust compatibility across Roots Bedrock, containerized environments, and Windows hosts.
- **Positive**: Significant database load reduction on frontend and API endpoints.
- **Positive**: Automated database hygiene preventing storage exhaustion.
- **Positive**: Enterprise-level security compliance for credential management.
- **Trade-offs**: Small operational footprint from daily WP-Cron scheduling.

## Evidence
- Implemented across `gnn-smtpmail/includes/class-gnn-smtpmail.php`, `gnn-smtpmail/includes/class-gnn-smtpmail-logger.php`, and `gnn-smtpmail/includes/class-gnn-smtpmail-admin.php`.
