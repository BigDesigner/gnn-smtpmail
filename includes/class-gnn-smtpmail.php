<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GNN_SMTPMail {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate() {
        $defaults = array(
            'mailer_type' => 'custom', // custom|brevo
            'custom' => array(
                'enabled'     => 0,
                'host'        => '',
                'port'        => 587,
                'smtp_secure' => 'tls', // tls|ssl|none
                'auth'        => 1,
                'username'    => '',
                'password'    => '',
                'from_email'  => '',
                'from_name'   => '',
            ),
            'brevo' => array(
                'api_key'    => '',
                'from_email' => '',
                'from_name'  => '',
            ),
        );
        $current = get_option( GNN_SMTPMAIL_OPTION );
        if ( ! is_array( $current ) ) {
            add_option( GNN_SMTPMAIL_OPTION, $defaults, '', 'no' );
        } else {
            // Migrate: merge existing settings with defaults
            $migrated = array(
                'mailer_type' => isset($current['mailer_type']) ? $current['mailer_type'] : 'custom',
                'custom'      => isset($current['custom']) && is_array($current['custom']) ? array_merge($defaults['custom'], $current['custom']) : $defaults['custom'],
                'brevo'       => isset($current['brevo']) && is_array($current['brevo']) ? array_merge($defaults['brevo'], $current['brevo']) : $defaults['brevo'],
            );
            update_option( GNN_SMTPMAIL_OPTION, $migrated );
        }
        GNN_SMTPMail_Logger::create_table();
    }

    public static function deactivate() {
        // keep data until uninstall
    }

    public function __construct() {
        // Auto-run DB creation if version changed or table is missing (e.g. plugin updated via git/updater)
        global $wpdb;
        $table = $wpdb->prefix . GNN_SMTPMAIL_TABLE;
        $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;

        $db_version = get_option( 'gnn_smtpmail_db_version', '' );
        if ( ! $table_exists || $db_version !== GNN_SMTPMAIL_VERSION ) {
            GNN_SMTPMail_Logger::create_table();
            update_option( 'gnn_smtpmail_db_version', GNN_SMTPMAIL_VERSION );
        }

        // Admin UI
        if ( is_admin() ) {
            require_once GNN_SMTPMAIL_DIR . 'includes/class-gnn-smtpmail-admin.php';
            new GNN_SMTPMail_Admin();
        }

        // Hook PHPMailer for Custom SMTP
        add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );

        // Short-circuit wp_mail for Brevo API
        add_filter( 'pre_wp_mail', array( $this, 'pre_wp_mail_handler' ), 10, 2 );

        // From filters
        add_filter( 'wp_mail_from', array( $this, 'filter_mail_from' ) );
        add_filter( 'wp_mail_from_name', array( $this, 'filter_mail_from_name' ) );

        // Logging hooks
        add_action( 'wp_mail_failed', array( $this, 'on_mail_failed' ), 10, 1 );
        add_action( 'wp_mail_succeeded', array( $this, 'on_mail_succeeded' ), 10, 1 );

        // Basic hardening headers for admin pages
        add_action( 'admin_init', array( $this, 'admin_hardening_headers' ) );
    }

    public function admin_hardening_headers() {
        if ( ! headers_sent() ) {
            header( 'X-Content-Type-Options: nosniff' );
            header( 'X-Frame-Options: sameorigin' );
            header( 'Referrer-Policy: no-referrer-when-downgrade' );
        }
    }

    private function get_settings() {
        $s = get_option( GNN_SMTPMAIL_OPTION, array() );
        return $s;
    }

    private function get_from_email_name() {
        $s = $this->get_settings();
        $mailer_type = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        
        if ( $mailer_type === 'brevo' && isset( $s['brevo'] ) ) {
            $from_email = isset( $s['brevo']['from_email'] ) ? $s['brevo']['from_email'] : '';
            $from_name  = isset( $s['brevo']['from_name'] ) ? $s['brevo']['from_name'] : '';
        } else {
            $c = isset( $s['custom'] ) ? $s['custom'] : array();
            $from_email = isset( $c['from_email'] ) ? $c['from_email'] : '';
            $from_name  = isset( $c['from_name'] ) ? $c['from_name'] : '';
        }
        return array( $from_email, $from_name );
    }

    public function filter_mail_from( $email ) {
        list( $from_email, ) = $this->get_from_email_name();
        if ( is_email( $from_email ) ) {
            return $from_email;
        }
        return $email;
    }

    public function filter_mail_from_name( $name ) {
        list( , $from_name ) = $this->get_from_email_name();
        if ( ! empty( $from_name ) ) {
            return $from_name;
        }
        return $name;
    }

    public function configure_phpmailer( $phpmailer ) {
        $s = $this->get_settings();
        $mailer_type = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        if ( $mailer_type !== 'custom' ) {
            return; // Only apply PHPMailer SMTP if mailer_type is custom
        }

        $c = isset( $s['custom'] ) ? $s['custom'] : array();
        if ( empty( $c['enabled'] ) ) {
            return; // disabled => WP default transport
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = sanitize_text_field( $c['host'] );
        $phpmailer->SMTPAuth   = ! empty( $c['auth'] );
        $phpmailer->Port       = intval( $c['port'] );
        $secure = isset( $c['smtp_secure'] ) ? $c['smtp_secure'] : 'tls';
        if ( $secure === 'ssl' || $secure === 'tls' ) {
            $phpmailer->SMTPSecure = $secure;
        } else {
            $phpmailer->SMTPSecure = '';
        }
        if ( $phpmailer->SMTPAuth ) {
            $phpmailer->Username = (string) ( $c['username'] ?? '' );
            $phpmailer->Password = (string) ( $c['password'] ?? '' );
        }
        $phpmailer->SMTPAutoTLS = true;
    }

    public function pre_wp_mail_handler( $null, $atts ) {
        $s = $this->get_settings();
        $mailer_type = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        if ( $mailer_type !== 'brevo' ) {
            return $null; // Use default / PHPMailer SMTP
        }

        $brevo = isset( $s['brevo'] ) ? $s['brevo'] : array();
        $api_key = isset( $brevo['api_key'] ) ? $brevo['api_key'] : '';

        if ( empty( $api_key ) ) {
            $error = new WP_Error( 'brevo_missing_api_key', __( 'Brevo API Key is missing.', 'gnn-smtpmail' ) );
            do_action( 'wp_mail_failed', $error );
            return false;
        }

        $to          = isset( $atts['to'] ) ? $atts['to'] : '';
        $subject     = isset( $atts['subject'] ) ? $atts['subject'] : '';
        $message     = isset( $atts['message'] ) ? $atts['message'] : '';
        $headers     = isset( $atts['headers'] ) ? $atts['headers'] : array();
        $attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

        // Build To array
        $to_emails = array();
        if ( is_array( $to ) ) {
            foreach ( $to as $email ) {
                if ( is_email( $email ) ) {
                    $to_emails[] = array( 'email' => trim($email) );
                }
            }
        } else {
            $emails = explode( ',', $to );
            foreach ( $emails as $email ) {
                if ( is_email( trim($email) ) ) {
                    $to_emails[] = array( 'email' => trim($email) );
                }
            }
        }

        if ( empty( $to_emails ) ) {
            $error = new WP_Error( 'brevo_invalid_recipient', __( 'Invalid or empty recipient email address.', 'gnn-smtpmail' ) );
            do_action( 'wp_mail_failed', $error );
            return false;
        }

        // Get sender details
        list( $from_email, $from_name ) = $this->get_from_email_name();
        if ( ! is_email( $from_email ) ) {
            $from_email = get_option( 'admin_email' );
        }
        if ( empty( $from_name ) ) {
            $from_name = get_option( 'blogname' );
        }

        // Parse headers
        $content_type = apply_filters( 'wp_mail_content_type', 'text/plain' );
        $reply_to = '';
        $custom_headers = array();
        if ( ! empty( $headers ) ) {
            $headers_arr = is_array( $headers ) ? $headers : explode( "\n", str_replace( "\r", '', $headers ) );
            foreach ( $headers_arr as $header ) {
                if ( empty( $header ) || strpos( $header, ':' ) === false ) {
                    continue;
                }
                list( $name, $value ) = explode( ':', $header, 2 );
                $name  = trim( $name );
                $value = trim( $value );
                if ( strcasecmp( $name, 'Reply-To' ) === 0 ) {
                    if ( preg_match( '/<([^>]+)>/', $value, $matches ) ) {
                        $reply_to = trim( $matches[1] );
                    } else {
                        $reply_to = $value;
                    }
                } elseif ( strcasecmp( $name, 'Content-Type' ) === 0 ) {
                    if ( strpos( strtolower( $value ), 'text/html' ) !== false ) {
                        $content_type = 'text/html';
                    }
                } else {
                    $custom_headers[$name] = $value;
                }
            }
        }

        // Prepare JSON body
        $body = array(
            'sender' => array(
                'name'  => $from_name,
                'email' => $from_email,
            ),
            'to'      => $to_emails,
            'subject' => $subject,
        );

        if ( $content_type === 'text/html' ) {
            $body['htmlContent'] = $message;
            $body['textContent'] = wp_strip_all_tags( $message );
        } else {
            $body['textContent'] = $message;
            $body['htmlContent'] = wpautop( esc_html( $message ) );
        }

        if ( ! empty( $reply_to ) && is_email( $reply_to ) ) {
            $body['replyTo'] = array( 'email' => $reply_to );
        }

        if ( ! empty( $custom_headers ) ) {
            $body['headers'] = $custom_headers;
        }

        // Attachments
        if ( ! empty( $attachments ) ) {
            $brevo_attachments = array();
            foreach ( $attachments as $attachment ) {
                if ( file_exists( $attachment ) ) {
                    $file_content = file_get_contents( $attachment );
                    $brevo_attachments[] = array(
                        'content' => base64_encode( $file_content ),
                        'name'    => basename( $attachment ),
                    );
                }
            }
            if ( ! empty( $brevo_attachments ) ) {
                $body['attachment'] = $brevo_attachments;
            }
        }

        // Send API Request
        $api_url = 'https://api.brevo.com/v3/smtp/email';
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'api-key'      => $api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            do_action( 'wp_mail_failed', $response );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        if ( $code !== 201 ) {
            $decoded = json_decode( $response_body, true );
            $msg = isset( $decoded['message'] ) ? $decoded['message'] : __( 'Brevo API error', 'gnn-smtpmail' );
            $error = new WP_Error( 'brevo_api_failed', sprintf( __( 'Brevo API Error (HTTP %d): %s', 'gnn-smtpmail' ), $code, $msg ) );
            do_action( 'wp_mail_failed', $error );
            return false;
        }

        // Succeeded
        $mail_data = array(
            'to'          => $to,
            'subject'     => $subject,
            'message'     => $message,
            'headers'     => $headers,
            'attachments' => $attachments,
        );
        do_action( 'wp_mail_succeeded', $mail_data );

        return true;
    }

    public function on_mail_failed( $wp_error ) {
        $s = $this->get_settings();
        $channel = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        $data = $wp_error->get_error_data();
        $to = isset( $data['to'] ) ? $data['to'] : '';
        $subject = isset( $data['subject'] ) ? $data['subject'] : '';
        $msg = $wp_error->get_error_message();
        GNN_SMTPMail_Logger::insert( $channel, is_array($to) ? implode(',', $to) : $to, $subject, 'error', $msg );
    }

    public function on_mail_succeeded( $mail_data ) {
        $s = $this->get_settings();
        $channel = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        $to = isset( $mail_data['to'] ) ? $mail_data['to'] : '';
        $subject = isset( $mail_data['subject'] ) ? $mail_data['subject'] : '';
        GNN_SMTPMail_Logger::insert( $channel, is_array($to) ? implode(',', $to) : $to, $subject, 'success', 'OK' );
    }
}