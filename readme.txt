=== GNN SMTPMail ===
Contributors: gnnteam
Tags: smtp, mail, email, phpmailer, logs
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight, reliable SMTP plugin for WordPress that ensures your emails hit the inbox, not the spam folder.

== Description ==

GNN SMTPMail replaces the default WordPress `wp_mail()` function to use SMTP (Simple Mail Transfer Protocol). This improves email deliverability and reliability by allowing you to send emails via a dedicated email service provider (like Gmail, Outlook, Amazon SES, SendGrid, etc.) instead of your web host's often unreliable mail server.

**Features:**
*   **Easy Configuration:** Set up SMTP Host, Port, Authentication, and Encryption in minutes.
*   **Email Logging:** Keep track of every email sent from your site, including status and errors.
*   **Test Email:** Verify your settings instantly with the built-in test email tool.
*   **Auto AltBody:** Automatically generates plain-text versions of HTML emails to improve spam scores.
*   **Secure:** Supports SSL and TLS encryption.

== Installation ==

1. Upload the `gnn-smtpmail` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to `GNN SMTPMail > Settings` to configure your SMTP credentials.
4. Use the `Test Mail` tab to verify that emails are sending correctly.

== Frequently Asked Questions ==

= Why should I use SMTP instead of the default mail? =
Default PHP `mail()` often fails because hosting providers limit sending or mark messages as spam. SMTP uses authenticated servers, ensuring better deliverability.

= Does this plugin improve email deliverability? =
Yes. By using an authenticated SMTP server and automatically generating `AltBody` (plain-text version) for HTML emails, spam filters are less likely to flag your messages.

= My emails are still going to spam. Why? =
Ensure your DNS records (SPF, DKIM, DMARC) are correctly set up for your sending domain. Also, check that your `From Email` matches the domain you are sending from.

= How do I clear the email logs? =
Go to `GNN SMTPMail > Logs` and click the "Clear All Logs" button.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Added SMTP configuration.
*   Added Email Logging.
*   Added Test Email functionality.
