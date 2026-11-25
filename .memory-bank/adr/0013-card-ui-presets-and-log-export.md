# ADR 0013: Tabbed Card Admin UI, SMTP Presets, and Formula-Safe Log Export

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-11-25
- **Category**: UI Architecture & Data Export
- **Supersedes**: None
- **Superseded By**: None

## Context
The plugin admin pages (`page_welcome`, `page_custom`, `page_test`, `page_logs`) used legacy WordPress `form-table` layouts with standard checkboxes, lacked an overview summary of delivery health, had no one-click SMTP provider presets or Brevo sender cache refresh control, and did not yet support log export (`FEAT-EXPORT`). Additionally, saving Custom SMTP settings when the Brevo sender dropdown was absent from `$_POST` cleared previously stored Brevo sender metadata.

## Decision
1. **Zero-Conflict Tabbed Card Layout & Flow-Safe Toggle Switches**:
   - Render a unified top navigation bar (`render_tabs()`) and structured `.gnn-card` / `.gnn-form-row` sections across all four admin screens.
   - Implement accessible `role="switch"` toggle switches (`render_switch()`) in `assets/admin.css` using `appearance: none` in normal document flow (avoiding `position: absolute`/`fixed` and hardcoded dark/light colors) paired with WordPress theme CSS variables.
2. **Overview Diagnostics, SMTP Presets & Test Mail Modes**:
   - Add `GNN_SMTPMail_Logger::get_stats()` (with table existence guard) to power the `Genel Bakış` summary card.
   - Add one-click provider preset buttons (`Gmail`, `Outlook / M365`, `Yandex`, `Zoho`, `Amazon SES`), dynamic `#gnn_smtp_auth_fields` toggling, and nonce-protected Brevo sender cache refresh (`gnn_refresh_brevo_senders`), while preserving Brevo sender fields when absent from `$_POST`.
   - Add `HTML` and `Özel E-posta` switches on `E-posta Testi`.
3. **CSV & JSON Log Export (`FEAT-EXPORT`)**:
   - Add `GNN_SMTPMail_Logger::get_logs_for_export()` with `$wpdb->prepare()` and bounded limits (`1–10000`).
   - Stream UTF-8 BOM CSV (mitigating spreadsheet formula injection via `escape_csv_cell()`) and `wp_json_encode()` JSON downloads guarded by `manage_options` and `gnn_export_logs_nonce`, plus a confirmation prompt before `gnn_clear_logs`.

## Evidence
- Implemented in commits `ffa7239` and `5ced8bb` (`v1.6.3`).
