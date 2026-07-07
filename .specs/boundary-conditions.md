# Boundary Conditions & Constraints

This document details the security bounds, permission rules, and execution constraints of GNN SMTPMail.

## 1. Security & Authentication Constraints
- **Admin Access Check:** Every settings page rendering and forms saving handler MUST explicitly verify `current_user_can('manage_options')` to restrict access to administrators only.
- **CSRF Mitigations:** Every POST form action and data mutation request MUST verify nonces via `check_admin_referer()` or `wp_verify_nonce()`.
  - Nonce action tokens:
    - Settings save: `gnn_custom_save_action` (nonce key: `gnn_custom_nonce`)
    - Test send: `gnn_test_send_action` (nonce key: `gnn_test_nonce`)
    - Clear logs: `gnn_clear_logs_action` (nonce key: `gnn_clear_logs_nonce`)
- **Direct File Access:** Every PHP file in the repository must begin with an absolute directory check `defined('ABSPATH') || exit;` to prevent direct scripting execution.

---

## 2. Data Storage & Output Validation
- **SQL Preparation:** All SQL calls involving parameters must use `$wpdb->prepare()`. Table names must be escaped using `esc_sql()` and wrapped in backticks (`` ` ``).
  - Placeholders: Use `%d` for integers, `%s` for strings, and `%f` for floats.
  - Raw Query Delimiters: In queries where `$wpdb->prepare` cannot bind table parameters (e.g. `TRUNCATE`, `DROP`), table names must be explicitly escaped with `esc_sql()` and wrapped in backticks.
- **Transient Caching:** Brevo sender list caching must use `wp_hash()` to salt dynamic transient cache keys, preventing API token leakage.
- **Log Retention Policy:** Log table entries can grow large on high-volume sites. Logs should be cleared manually or pruned periodically.
- **Privacy & PII Limits:** The logs table records recipient email addresses and subject lines. No raw email body content is logged to prevent leaking sensitive PII.

---

## 3. WordPress Sanitization and Escaping
Every variable and data input or output must be handled via specific safety APIs.

### Input Sanitization
- **Input text:** `sanitize_text_field( $value )`
- **Input email:** `sanitize_email( $value )`
- **Input textarea:** `sanitize_textarea_field( $value )`
- **Array values:** Sanitization must be mapped over each index recursively.
- **Password inputs:** Do not use `sanitize_text_field` to avoid breaking complex special characters. Use `wp_unslash` followed by string casting.

### Output Escaping
- **Escaping plain text HTML:** `esc_html( $value )`
- **Escaping HTML attributes:** `esc_attr( $value )`
- **Escaping URLs:** `esc_url( $value )`
- **Escaping Rich Text / HTML markup:** `wp_kses_post( $value )`

---

## 4. Safe File Reading & Attachment Validation
- To prevent Arbitrary File Read and path traversal vulnerabilities through attachments, all file pathways sent to `file_get_contents()` must be validated.
- **Rules:**
  1. Resolve symlinks using `realpath()`.
  2. Verify that the resolved path is located within the WordPress installation directory (`ABSPATH`).
