# Bug Tracking List

This document logs active and historical bugs in the GNN SMTPMail repository.

## Active Bugs
*No active bugs reported.*

## Resolved Bugs

### 1. Brevo API False-Positive Delivery & Admin Status Checks (v1.6.4)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `pre_wp_mail_handler()` ignored upstream `$null` short-circuits, accepted `201` without validating JSON `messageId`, silently dropped invalid/blocked attachments, and ignored `Content-Type: text/plain` overrides; admin pages reported `Aktif` based solely on non-empty API keys; `page_logs()` flagged an empty `get_wp_mail_source()` string as a plugin conflict.
- **Resolution:** Added `null !== $null` short-circuit guard, `messageId`/`messageIds` validation (`201`/`202`), skipped attachment logging, `get_brevo_senders_status()` validation across admin pages, and unified `is_wp_mail_conflict()` check.

### 2. Updater `zipball_url` Fallback Rejected by Host Allowlist (v1.6.1)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `GNN_SMTPMail_Updater::get_remote_release()` fell back to `$body->zipball_url` (`api.github.com`) when no `.zip` asset existed, which was rejected by the HTTPS host allowlist.
- **Resolution:** Added `api.github.com` and `release-assets.githubusercontent.com` to the allowlist, plus `$args->slug` and `WP_Filesystem()` guards.

### 2. Log Pruning Timezone Mismatch (v1.6.1)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `prune_old_logs()` calculated cutoff timestamps with `gmdate()` (UTC) while logs were stored using `current_time( 'mysql' )` (site timezone).
- **Resolution:** Replaced `gmdate()` with `wp_date()` for timezone-consistent retention pruning.

### 3. `SMTPAutoTLS` Forced On When Encryption Set to `none` (v1.6.1)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `$phpmailer->SMTPAutoTLS = true` remained active even when `smtp_secure` was set to `none`, breaking local/self-signed SMTP servers.
- **Resolution:** Set `$phpmailer->SMTPAutoTLS = ( $secure !== 'none' )` and clamped ports to `1–65535`.

### 4. Unescaped Table Names in Logger Queries (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** SQL query structures like `CREATE TABLE`, `TRUNCATE TABLE`, and `DROP TABLE` inserted raw table strings without `esc_sql()` and backticks, posing SQL injection risks.
- **Resolution:** Added `esc_sql()` escaping and backtick delimiters to all logger queries.

### 2. File Path Traversal in Email Attachments (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** `file_get_contents()` read email attachments directly without validation, making it vulnerable to local file inclusion (LFI).
- **Resolution:** Used `realpath()` and verified that the file path starts within `ABSPATH`.

### 3. Special Characters Stripped from SMTP Password (v1.5.2)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** SMTP password used `sanitize_text_field()` which stripped critical special characters, breaking valid credentials.
- **Resolution:** Replaced sanitization with `wp_unslash` and string type casting.

### 4. Leftover catch Block Syntax Error (v1.5.1)
- **Status:** Resolved
- **Confidence:** Verified
- **Issue:** A leftover `catch` block without a matching `try` block caused a fatal syntax crash upon loading.
- **Resolution:** Removed the orphaned block and restored linear checking.
