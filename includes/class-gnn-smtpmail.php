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
        // Default settings (Custom SMTP only). Also purge any legacy 'brevo' keys if present.
        $defaults = array(
            'custom' => array(
                'enabled'    => 0,
                'host'       => '',
                'port'       => 587,
                'smtp_secure'=> 'tls', // tls|ssl|none
                'auth'       => 1,
                'username'   => '',
                'password'   => '',
                'from_email' => '',
                'from_name'  => '',
            ),
        );
        $current = get_option( GNN_SMTPMAIL_OPTION );
        if ( ! is_array( $current ) ) {
            add_option( GNN_SMTPMAIL_OPTION, $defaults, '', 'no' );
        } else {
            // Migrate: drop any old keys (mode/brevo) and keep custom
            $migrated = array(
                'custom' => isset($current['custom']) && is_array($current['custom']) ? $current['custom'] : $defaults['custom'],
            );
            update_option( GNN_SMTPMAIL_OPTION, $migrated );
        }
        GNN_SMTPMail_Logger::create_table();
    }

    public static function deactivate() {
        // keep data until uninstall
    }

    public function __construct() {

        // Admin UI
        if ( is_admin() ) {
            require_once GNN_SMTPMAIL_DIR . 'includes/class-gnn-smtpmail-admin.php';
            new GNN_SMTPMail_Admin();
        }

        // Hook PHPMailer for sending
        add_action( 'phpmailer_init', array( $this, 'configure_phpmailer' ) );

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
        if ( ! isset( $s['custom'] ) ) { $s['custom'] = array(); }
        return $s['custom'];
    }

    private function get_from_email_name() {
        $c = $this->get_settings();
        $from_email = isset( $c['from_email'] ) ? $c['from_email'] : '';
        $from_name  = isset( $c['from_name'] ) ? $c['from_name'] : '';
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
        $c = $this->get_settings();
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

    public function on_mail_failed( $wp_error ) {
        $data = $wp_error->get_error_data();
        $to = isset( $data['to'] ) ? $data['to'] : '';
        $subject = isset( $data['subject'] ) ? $data['subject'] : '';
        $msg = $wp_error->get_error_message();
        GNN_SMTPMail_Logger::insert( 'custom', is_array($to) ? implode(',', $to) : $to, $subject, 'error', $msg );
    }

    public function on_mail_succeeded( $mail_data ) {
        $to = isset( $mail_data['to'] ) ? $mail_data['to'] : '';
        $subject = isset( $mail_data['subject'] ) ? $mail_data['subject'] : '';
        GNN_SMTPMail_Logger::insert( 'custom', is_array($to) ? implode(',', $to) : $to, $subject, 'success', 'OK' );
    }
}