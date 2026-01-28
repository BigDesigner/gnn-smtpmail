<?php
if (!defined('ABSPATH')) {
    exit;
}

class GNN_SMTPMail
{

    /**
     * Option name for settings.
     */
    const OPTION_NAME = 'gnn_smtp_options';

    /**
     * Initialize the plugin classes and hooks.
     */
    public function run()
    {
        // Register Logger CPT on every page load
        if (class_exists('GNN_SMTP_Logger')) {
            add_action('init', array('GNN_SMTP_Logger', 'register_post_type'));
        }

        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
        add_action('wp_mail_failed', array($this, 'log_failed_email'));
        add_action('wp_mail_succeeded', array($this, 'log_sent_email'));
    }

    /**
     * Plugin activation tasks.
     */
    public static function activate()
    {
        // Default options
        if (!get_option(self::OPTION_NAME)) {
            update_option(self::OPTION_NAME, array(
                'from_email' => get_option('admin_email'),
                'from_name' => get_option('blogname'),
                'mailer' => 'smtp',
                'smtp_host' => '',
                'smtp_port' => '587',
                'smtp_auth' => true,
                'smtp_user' => '',
                'smtp_pass' => '',
                'smtp_secure' => 'tls',
                'timeout' => 10,
            ));
        }
    }

    /**
     * Configure PHPMailer properties.
     *
     * @param PHPMailer $phpmailer The PHPMailer instance.
     */
    public function configure_phpmailer($phpmailer)
    {
        $options = get_option(self::OPTION_NAME, array());

        // 1. Force CharSet and Encoding
        $phpmailer->CharSet = 'UTF-8';
        $phpmailer->Encoding = 'base64';

        // 2. Auto-generate AltBody if missing
        if (!empty($phpmailer->Body) && empty($phpmailer->AltBody)) {
            $phpmailer->AltBody = wp_strip_all_tags($phpmailer->Body);
        }

        // 3. Set From address if valid
        if (!empty($options['from_email']) && is_email($options['from_email'])) {
            $from_name = !empty($options['from_name']) ? $options['from_name'] : __('WordPress', 'gnn-smtpmail');
            $phpmailer->setFrom($options['from_email'], $from_name);
            $phpmailer->Sender = $options['from_email']; // Return-Path
        }

        // 4. Configure SMTP if enabled
        if (isset($options['mailer']) && 'smtp' === $options['mailer']) {
            $phpmailer->isSMTP();
            $phpmailer->Host = isset($options['smtp_host']) ? $options['smtp_host'] : 'localhost';
            $phpmailer->Port = isset($options['smtp_port']) ? (int) $options['smtp_port'] : 25;
            $phpmailer->SMTPSecure = isset($options['smtp_secure']) ? $options['smtp_secure'] : '';
            $phpmailer->SMTPAuth = !empty($options['smtp_auth']);

            if ($phpmailer->SMTPAuth) {
                $phpmailer->Username = isset($options['smtp_user']) ? $options['smtp_user'] : '';
                $phpmailer->Password = isset($options['smtp_pass']) ? $options['smtp_pass'] : '';
            }

            // Custom timeout
            if (!empty($options['timeout'])) {
                $phpmailer->Timeout = (int) $options['timeout'];
            }

            // Enable debug mode for troubleshooting (0 = off, 1 = client, 2 = server)
            $phpmailer->SMTPDebug = 0; // Set to 2 for debugging

            // SSL/TLS options for better compatibility
            $phpmailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false,
                ),
            );

            // Keep connection alive
            $phpmailer->SMTPKeepAlive = false;
        }
    }

    /**
     * Log failed sending attempts.
     *
     * @param WP_Error $error The error object.
     */
    public function log_failed_email($error)
    {
        if (!class_exists('GNN_SMTP_Logger')) {
            return;
        }

        $data = $error->get_error_data();
        // Extract details if available from PHPMailer data usually passed in wp_mail_failed
        $recipient = isset($data['to']) ? $data['to'] : 'unknown';
        $subject = isset($data['subject']) ? $data['subject'] : 'unknown';
        $message = $error->get_error_message();

        GNN_SMTP_Logger::add(
            'smtp',
            $recipient,
            $subject,
            'failed',
            $message,
            $data
        );
    }

    /**
     * Log successful sending (if hook exists or custom implementation fires it).
     *
     * @param array $mail_data The mail data array (to, subject, message, headers, attachments).
     */
    public function log_sent_email($mail_data)
    {
        if (!class_exists('GNN_SMTP_Logger')) {
            return;
        }

        $recipient = isset($mail_data['to']) ? $mail_data['to'] : 'unknown';
        $subject = isset($mail_data['subject']) ? $mail_data['subject'] : '';

        GNN_SMTP_Logger::add(
            'smtp',
            $recipient,
            $subject,
            'sent',
            __('Email sent successfully.', 'gnn-smtpmail'),
            $mail_data
        );
    }
}
