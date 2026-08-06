# Engineering Constitution & Code Standards

This document establishes coding rules, prefixing guidelines, and verification rules for the GNN SMTPMail repository.

## 1. Global Prefixing & Namespacing (CRITICAL)
To avoid namespace pollution and conflicts with other WordPress themes or plugins:
- All classes MUST begin with `GNN_SMTPMail_` prefix (e.g. `GNN_SMTPMail_Admin`).
- All global functions MUST begin with `gnn_smtpmail_` prefix (e.g. `gnn_smtpmail_plugin_links`).
- All plugin option values and constants MUST be prefixed with `GNN_SMTPMAIL_` or `gnn_smtpmail_`.
- All CSS classes MUST use BEM methodology and begin with `gnn-` prefix (e.g. `.gnn-badge`, `.gnn-grid`).

## 2. Coding Quality Standards
- **PHP:** Compliant with PSR-12 and WordPress PHP Coding Standards (WPCS).
- **CSS:** Zero-conflict vanilla CSS with custom properties (CSS variables). Use semi-transparent layers and relative dimensions for compatibility with dark/light themes.
- **Translation / i18n:** All user-facing strings must use standard WordPress translation functions (`__()`, `_e()`, `esc_html__()`, etc.) with text domain `gnn-smtpmail`.

## 3. Contribution Workflow
- Feature branches use `feature/` prefix (e.g., `feature/amazing-feature`).
- Bug reports should specify reproduction steps, environment details (WP/PHP versions), and relevant logs.
- All contributions must comply with WPCS and repository security constraints.

## 4. Admin Menu Position Registry (CRITICAL)
- All GNN product family items must register their top-level menu position using a quoted 3-digit decimal string literal (e.g., `'79.101'`).
- Themes use `'58.xyz'`–`'59.xyz'` (next to Appearance). Plugins use `'78.xyz'`–`'79.xyz'` (next to Settings).
- GNN SMTPMail is assigned position slot `'79.101'`.
- Refer to `.memory-bank/adr/0010-gnn-admin-menu-position-registry.md` for the complete slot registry.

---

## 5. Pre-Commit Auditing Checklist
AI agents MUST verify the following before proposing commits:
1. **ABSPATH check:** verify the file header guard is present.
2. **Prefix check:** all functions, variables, CSS hooks are correctly prefixed.
3. **Escaping check:** all output is escaped (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
4. **Sanitization check:** all input variables are sanitized (`sanitize_text_field`, `sanitize_email`).
5. **Nonce / Capability checks:** verify permissions and CSRF safety.
6. **Comments preservation:** verify no historical code comments were altered or deleted.
7. **Admin menu position check:** verify `add_menu_page()` uses the assigned quoted string slot (e.g., `'79.101'`).
