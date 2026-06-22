# Architectural Decisions: GNN SMTPMail

## 1. Database Table for Logs
- **Decision:** Use a custom table (`wp_gnn_smtpmail_logs`) instead of custom post types or options.
- **Rationale:** Log entries can grow rapidly on active sites. A custom table ensures high-speed insertions, keeps `wp_posts` and `wp_options` lightweight, and simplifies log cleanup/truncation.

## 2. Mail System Hook
- **Decision:** Hook into `phpmailer_init` to apply SMTP configuration.
- **Rationale:** This is the standard, secure, and native WordPress method to configure the PHPMailer object before an email is sent, replacing the default mail delivery route.

## 3. Log Success and Failure Hooks
- **Decision:** Use `wp_mail_succeeded` and `wp_mail_failed` actions.
- **Rationale:** WordPress core fires these hooks upon successful or failed execution of `wp_mail()`. This allows us to cleanly log the recipient, subject, status, and error messages without modifying core files or wrapping individual send actions.

## 4. Simplified Architecture (Custom-only)
- **Decision:** Focus exclusively on Custom SMTP and deprecate third-party API providers like Brevo.
- **Rationale:** Reduces code bloat, dependencies, and complex API credential checks, keeping the plugin simple, fast, and highly secure.

## 5. Plugin Updates
- **Decision:** GitHub-based manual/automatic updater.
- **Rationale:** Allows for direct distribution and version control without the constraints of the official WordPress.org repository, while maintaining a seamless update experience for users.
