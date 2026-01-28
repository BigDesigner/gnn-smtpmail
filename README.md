# GNN SMTPMail

**GNN SMTPMail** is a lightweight, reliable SMTP plugin for WordPress that ensures your emails hit the inbox, not the spam folder.

It replaces the default WordPress `wp_mail()` function to use SMTP (Simple Mail Transfer Protocol), allowing you to send emails via a dedicated email service provider (like Gmail, Outlook, Amazon SES, SendGrid, etc.) instead of your web host's often unreliable mail server.

## Features

-   **Easy Configuration:** Set up SMTP Host, Port, Authentication, and Encryption in minutes.
-   **Email Logging:** Keep track of every email sent from your site, including status and errors.
-   **Test Email:** Verify your settings instantly with the built-in test email tool.
-   **Auto AltBody:** Automatically generates plain-text versions of HTML emails to improve spam scores.
-   **Secure:** Supports SSL and TLS encryption.
-   **Universal Support:** Works with contact forms (CF7, Gravity Forms), WooCommerce, and system emails.

## Installation

1.  Download the plugin folder `gnn-smtpmail`.
2.  Upload it to your WordPress site's `/wp-content/plugins/` directory.
3.  Activate the plugin through the 'Plugins' menu in WordPress.
4.  Go to **GNN SMTPMail > Settings** to configure your SMTP credentials.
5.  Use the **Test Mail** tab to verify that emails are sending correctly.

## Configuration Guide

1.  **Mailer Type:** Select 'SMTP'.
2.  **From Email:** Enter the email address you want emails to come from (e.g., `info@yourdomain.com`).
3.  **From Name:** Enter the name you want to appear (e.g., `My Website`).
4.  **SMTP Host:** Your SMTP provider's server address (e.g., `smtp.gmail.com`).
5.  **SMTP Port:** Usually `587` (TLS) or `465` (SSL).
6.  **Authentication:** Check "Enable SMTP Authentication".
7.  **Username/Password:** Your email account credentials.

## Frequently Asked Questions

**Why should I use SMTP instead of the default mail?**
Default PHP `mail()` often fails because hosting providers limit sending or mark messages as spam. SMTP uses authenticated servers, ensuring better deliverability.

**My emails are still going to spam. Why?**
Ensure your DNS records (SPF, DKIM, DMARC) are correctly set up for your sending domain. Also, check that your `From Email` matches the domain you are sending from.

## License

This project is licensed under the GNU General Public License v2 or later. See the [LICENSE](LICENSE) file for details.
