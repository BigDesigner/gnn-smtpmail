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

## 4. Multi-channel Architecture (Custom SMTP & Brevo API)
- **Decision:** Offer a dynamic choice between Custom SMTP (via standard PHPMailer) and Brevo API (via HTTP calls).
- **Rationale:** While SMTP is highly compatible, HTTP APIs (like Brevo) bypass SMTP port blocks on strict hosting providers, increase reliability, and provide superior speed for transactional mail.

## 5. HTTP Sending Implementation (Brevo API)
- **Decision:** Hook into `pre_wp_mail` filter to intercept mail delivery when Brevo is selected.
- **Rationale:** By returning a non-null value from `pre_wp_mail`, we short-circuit the default WordPress mailing pipeline and dispatch the request to Brevo API using `wp_remote_post`. This guarantees security, intercepts all outgoing emails sitewide, and integrates nicely with existing logger actions.

## 6. Plugin Updates
- **Decision:** GitHub-based manual/automatic updater.
- **Rationale:** Allows for direct distribution and version control without the constraints of the official WordPress.org repository, while maintaining a seamless update experience for users.
