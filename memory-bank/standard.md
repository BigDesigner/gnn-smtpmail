# Project Standards & Linting Protocols

## 1. Naming Conventions
- **Files:** `lowercase-kebab-case.php`
- **Classes:** `PascalCase` (prefixed with `GNN_SMTPMail_`)
- **Functions:** `snake_case` (prefixed with `gnn_smtpmail_`)
- **Variables:** `snake_case`
- **CSS Classes:** BEM methodology (`gnn-smtpmail__element--modifier`)
- **Text Domain:** `gnn-smtpmail`

## 2. Code Quality
- **PHP:** PSR-12 compliance.
- **JS:** Standard JS with ES6+ features.
- **CSS:** Vanilla CSS with custom properties (CSS variables). **Priority:** Zero-conflict theme compatibility (Dark/Light mode support via inheritance and semi-transparent layers).

## 3. Documentation
- All functions MUST have PHPDoc blocks.
- Major logic blocks require inline comments explaining the "why", not just the "how".
- API integrations and PHPMailer hooks must document expected behavior and error handling.

## 4. Plugin Architecture
- **Safety:** All files must include `if ( ! defined( 'ABSPATH' ) ) exit;` guard.
- **Hooks:** Use appropriate hooks (`init`, `admin_init`, `phpmailer_init`, `wp_mail_failed`, `wp_mail_succeeded`, etc.).
- **Global Namespace:** Avoid polluting the global namespace; use prefixes for EVERYTHING.
- **Deactivation/Uninstall:** Clean up custom tables and options on uninstall (via `uninstall.php`).

## 5. Settings & UX Standards
- **Settings API:** Use the native WordPress Settings API or secure options management for all plugin options.
- **Validation:** All settings MUST be sanitized before saving.
- **UI/UX:** Settings pages should follow WordPress Admin UI patterns but can use "GNN Premium" styling (clean layouts, subtle badges) for a better feel.

## 6. Security & Documentation Research
- **Official Sources:** The WordPress Developer Resources (Plugin Handbook) are the primary sources of truth.
- **Security Protocols:**
    - All input must be sanitized (`sanitize_text_field`, `sanitize_email`, etc.).
    - All output must be escaped (`esc_html`, `esc_attr`, `esc_url`).
    - Nonces MUST be used for all form submissions and AJAX requests.
    - Permission checks (`current_user_can('manage_options')`) must be performed before any admin action.
    - SQL queries MUST use `$wpdb->prepare()`.

## 7. Consultative & Mentorship Approach
- **Proactive Suggestions:** Evaluate if a better, more modern, or more user-friendly way exists.
- **Decision Support:** Present better ways to the USER before implementation.
- **Educational Context:** Explain "why" for WordPress plugin development best practices.

## 8. Performance & Optimization
- **Caching:** Use Transients API or custom caching where applicable.
- **Conditional Loading:** Only enqueue scripts and styles on pages where they are needed.
- **No Dependencies:** Avoid 3rd party libraries unless absolutely necessary.

## 9. Development Integrity & Verification
- **Pre-Commit Check:** No code shall be committed without verification.
- **Verification Methods:** PHP lint (`php -l`), CSS validation, and functional testing.
- **Atomic Commits:** Each commit must represent a single, verified change.

## 10. Localization (i18n)
- All user-facing strings MUST be translatable using `__()`, `_e()`, etc.
- Text domain `gnn-smtpmail` must be used consistently.

## 11. Changelog Management
- **Format:** Follow the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) standard.
- **Update Rule:** Every version bump in the main plugin file header MUST have a corresponding entry in `CHANGELOG.md`.
