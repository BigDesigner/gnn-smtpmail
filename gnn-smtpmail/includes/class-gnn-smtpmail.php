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
        update_option( 'gnn_smtpmail_db_version', GNN_SMTPMAIL_VERSION );

        if ( ! wp_next_scheduled( 'gnn_smtpmail_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'gnn_smtpmail_daily_cleanup' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'gnn_smtpmail_daily_cleanup' );
    }

    public function __construct() {
        // Lazy DB table creation/migration only when plugin version changes
        $db_version = get_option( 'gnn_smtpmail_db_version', '' );
        if ( $db_version !== GNN_SMTPMAIL_VERSION ) {
            GNN_SMTPMail_Logger::create_table();
            update_option( 'gnn_smtpmail_db_version', GNN_SMTPMAIL_VERSION );
        }

        // Scheduled log rotation
        add_action( 'gnn_smtpmail_daily_cleanup', array( 'GNN_SMTPMail_Logger', 'prune_old_logs' ) );

        // Admin UI
        if ( is_admin() ) {
            add_action( 'init', array( $this, 'ensure_cron_scheduled' ) );
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

    /**
     * Ensure daily log pruning cron is scheduled.
     */
    public function ensure_cron_scheduled() {
        if ( ! wp_next_scheduled( 'gnn_smtpmail_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'gnn_smtpmail_daily_cleanup' );
        }
    }

    public function admin_hardening_headers() {
        if ( ! headers_sent() ) {
            header( 'X-Content-Type-Options: nosniff' );
            header( 'X-Frame-Options: sameorigin' );
            header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        }
    }

    private function get_settings() {
        $s = get_option( GNN_SMTPMAIL_OPTION, array() );
        return $s;
    }

    /**
     * Resolve SMTP password, preferring wp-config.php constant override.
     */
    private function get_smtp_password( $custom_settings ) {
        if ( defined( 'GNN_SMTPMAIL_SMTP_PASS' ) && is_string( GNN_SMTPMAIL_SMTP_PASS ) ) {
            return GNN_SMTPMAIL_SMTP_PASS;
        }
        return (string) ( $custom_settings['password'] ?? '' );
    }

    /**
     * Resolve Brevo API key, preferring wp-config.php constant override.
     */
    private function get_brevo_api_key( $brevo_settings ) {
        if ( defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY ) ) {
            return GNN_SMTPMAIL_BREVO_KEY;
        }
        return (string) ( $brevo_settings['api_key'] ?? '' );
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
        $port                  = intval( $c['port'] );
        $phpmailer->Port       = ( $port >= 1 && $port <= 65535 ) ? $port : 587;
        $secure = isset( $c['smtp_secure'] ) ? $c['smtp_secure'] : 'tls';
        if ( $secure === 'ssl' || $secure === 'tls' ) {
            $phpmailer->SMTPSecure = $secure;
        } else {
            $phpmailer->SMTPSecure = '';
        }
        if ( $phpmailer->SMTPAuth ) {
            $phpmailer->Username = (string) ( $c['username'] ?? '' );
            $phpmailer->Password = $this->get_smtp_password( $c );
        }
        $phpmailer->SMTPAutoTLS = ( $secure !== 'none' );
    }

    /**
     * Parse comma-separated email addresses (optionally with display names) into Brevo recipient objects.
     *
     * @param string|array $raw_addresses Raw email address list.
     * @return array
     */
    private function parse_email_address_list( $raw_addresses ) {
        $result = array();
        $items  = is_array( $raw_addresses ) ? $raw_addresses : explode( ',', (string) $raw_addresses );
        foreach ( $items as $item ) {
            $item = trim( $item );
            if ( empty( $item ) ) {
                continue;
            }
            if ( preg_match( '/^(.*)<([^>]+)>$/', $item, $matches ) ) {
                $name  = trim( trim( $matches[1] ), " \t\n\r\0\x0B\"'" );
                $email = trim( $matches[2] );
                if ( is_email( $email ) ) {
                    $entry = array( 'email' => $email );
                    if ( ! empty( $name ) ) {
                        $entry['name'] = $name;
                    }
                    $result[] = $entry;
                }
            } elseif ( is_email( $item ) ) {
                $result[] = array( 'email' => $item );
            }
        }
        return $result;
    }

    public function pre_wp_mail_handler( $null, $atts ) {
        if ( null !== $null ) {
            return $null;
        }

        $s = $this->get_settings();
        $mailer_type = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GNN SMTPMail - pre_wp_mail_handler triggered. Mailer Type: ' . $mailer_type );
        }
        if ( $mailer_type !== 'brevo' ) {
            return $null; // Use default / PHPMailer SMTP
        }

        $to          = isset( $atts['to'] ) ? $atts['to'] : '';
        $subject     = isset( $atts['subject'] ) ? $atts['subject'] : '';
        $message     = isset( $atts['message'] ) ? $atts['message'] : '';
        $headers     = isset( $atts['headers'] ) ? $atts['headers'] : array();
        $attachments = isset( $atts['attachments'] ) ? $atts['attachments'] : array();

        $brevo = isset( $s['brevo'] ) ? $s['brevo'] : array();
        $api_key = $this->get_brevo_api_key( $brevo );

        if ( empty( $api_key ) ) {
            $error = new WP_Error( 'brevo_missing_api_key', __( 'Brevo API Key is missing.', 'gnn-smtpmail' ) );
            GNN_SMTPMail_Logger::insert( 'brevo', $to, $subject, 'error', $error->get_error_message() );
            do_action( 'wp_mail_failed', $error );
            return false;
        }

        // Build To array
        $to_emails = $this->parse_email_address_list( $to );

        if ( empty( $to_emails ) ) {
            $error = new WP_Error( 'brevo_invalid_recipient', __( 'Invalid or empty recipient email address.', 'gnn-smtpmail' ) );
            GNN_SMTPMail_Logger::insert( 'brevo', $to, $subject, 'error', $error->get_error_message() );
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

        // Parse headers (aligned with official Brevo plugin behavior)
        $content_type   = apply_filters( 'wp_mail_content_type', 'text/plain' );
        $reply_to       = '';
        $cc_emails      = array();
        $bcc_emails     = array();
        $tags           = array();
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
                if ( strcasecmp( $name, 'From' ) === 0 ) {
                    if ( strpos( $value, '<' ) !== false ) {
                        $parsed_name  = trim( trim( substr( $value, 0, strpos( $value, '<' ) ) ), " \t\n\r\0\x0B\"'" );
                        $parsed_email = trim( str_replace( '>', '', substr( $value, strpos( $value, '<' ) + 1 ) ) );
                        if ( is_email( $parsed_email ) ) {
                            $from_email = $parsed_email;
                            $from_name  = $parsed_name;
                        }
                    } elseif ( is_email( $value ) ) {
                        $from_email = $value;
                        $from_name  = '';
                    }
                } elseif ( strcasecmp( $name, 'Reply-To' ) === 0 ) {
                    if ( preg_match( '/<([^>]+)>/', $value, $matches ) ) {
                        $reply_to = trim( $matches[1] );
                    } else {
                        $reply_to = $value;
                    }
                } elseif ( strcasecmp( $name, 'Cc' ) === 0 ) {
                    $cc_emails = array_merge( $cc_emails, $this->parse_email_address_list( $value ) );
                } elseif ( strcasecmp( $name, 'Bcc' ) === 0 ) {
                    $bcc_emails = array_merge( $bcc_emails, $this->parse_email_address_list( $value ) );
                } elseif ( strcasecmp( $name, 'Content-Type' ) === 0 ) {
                    if ( strpos( strtolower( $value ), 'text/html' ) !== false ) {
                        $content_type = 'text/html';
                    } elseif ( strpos( strtolower( $value ), 'text/plain' ) !== false ) {
                        $content_type = 'text/plain';
                    }
                } elseif ( strcasecmp( $name, 'X-Mailin-Tag' ) === 0 ) {
                    if ( '' !== $value ) {
                        $tags[] = $value;
                    }
                    $custom_headers[ $name ] = $value;
                } else {
                    $custom_headers[ $name ] = $value;
                }
            }
        }

        // Prepare JSON body
        $sender = array( 'email' => $from_email );
        if ( '' !== trim( (string) $from_name ) ) {
            $sender['name'] = $from_name;
        }
        $body = array(
            'sender'  => $sender,
            'to'      => $to_emails,
            'subject' => $subject,
        );

        if ( ! empty( $cc_emails ) ) {
            $body['cc'] = $cc_emails;
        }
        if ( ! empty( $bcc_emails ) ) {
            $body['bcc'] = $bcc_emails;
        }

        if ( $content_type === 'text/html' ) {
            $html_body = (string) $message;
            if ( strpos( $html_body, '</table>' ) === false && strpos( $html_body, '</div>' ) === false && strpos( $html_body, "\n" ) !== false ) {
                $html_body = preg_replace( '#<(https?://[^*]+)>#', '$1', $html_body );
                $html_body = nl2br( $html_body );
            }
            $body['htmlContent'] = $html_body;
            $body['textContent'] = wp_strip_all_tags( (string) $message );
        } else {
            $plain_body = (string) $message;
            $body['textContent'] = $plain_body;
            $clean_for_html      = preg_replace( '#<(https?://[^*]+)>#', '$1', $plain_body );
            $body['htmlContent'] = nl2br( esc_html( $clean_for_html ) );
        }

        if ( ! empty( $reply_to ) && is_email( $reply_to ) ) {
            $body['replyTo'] = array( 'email' => $reply_to );
        }

        if ( ! empty( $tags ) ) {
            $body['tags'] = array_values( array_unique( $tags ) );
        }

        if ( ! empty( $custom_headers ) ) {
            $body['headers'] = $custom_headers;
        }

        // Attachments
        $skipped_attachments = array();
        if ( ! empty( $attachments ) ) {
            $brevo_attachments = array();
            $upload_dir = wp_upload_dir();
            $allowed_roots = apply_filters(
                'gnn_smtpmail_allowed_attachment_roots',
                array_filter( array(
                    defined( 'WP_CONTENT_DIR' ) ? realpath( WP_CONTENT_DIR ) : false,
                    ! empty( $upload_dir['basedir'] ) ? realpath( $upload_dir['basedir'] ) : false,
                ) )
            );

            foreach ( $attachments as $attachment ) {
                $real_path = realpath( $attachment );
                $attached  = false;
                if ( false !== $real_path && file_exists( $real_path ) && is_file( $real_path ) ) {
                    $base_name = strtolower( basename( $real_path ) );
                    $ext       = strtolower( pathinfo( $real_path, PATHINFO_EXTENSION ) );
                    if ( 'php' !== $ext && 'phtml' !== $ext && 'wp-config.php' !== $base_name ) {
                        $norm_path = wp_normalize_path( $real_path );
                        $is_allowed = false;
                        foreach ( $allowed_roots as $root ) {
                            $norm_root = trailingslashit( wp_normalize_path( $root ) );
                            if ( 0 === stripos( $norm_path, $norm_root ) ) {
                                $is_allowed = true;
                                break;
                            }
                        }
                        if ( $is_allowed ) {
                            $file_content = file_get_contents( $real_path );
                            if ( false !== $file_content ) {
                                $brevo_attachments[] = array(
                                    'content' => base64_encode( $file_content ),
                                    'name'    => basename( $real_path ),
                                );
                                $attached = true;
                            }
                        }
                    }
                }
                if ( ! $attached ) {
                    $skipped_attachments[] = basename( (string) $attachment );
                }
            }
            if ( ! empty( $brevo_attachments ) ) {
                $body['attachment'] = $brevo_attachments;
            }
        }

        // Strip empty top-level fields (matching official Brevo API client)
        foreach ( $body as $key => $val ) {
            if ( empty( $val ) && false !== $val && 0 !== $val ) {
                unset( $body[ $key ] );
            }
        }

        // Send API Request
        $api_url = 'https://api.brevo.com/v3/smtp/email';
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'api-key'      => $api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'gnn-smtpmail/wordpress',
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'GNN SMTPMail - wp_remote_post returned WP_Error: ' . $response->get_error_message() );
            }
            GNN_SMTPMail_Logger::insert( 'brevo', $to, $subject, 'error', $response->get_error_message() );
            do_action( 'wp_mail_failed', $response );
            return false;
        }

        $code          = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $decoded       = json_decode( $response_body, true );
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GNN SMTPMail - Brevo response code: ' . $code );
        }

        $has_message_id = is_array( $decoded ) && ( ! empty( $decoded['messageId'] ) || ! empty( $decoded['messageIds'] ) );
        if ( ! in_array( $code, array( 201, 202 ), true ) || ! $has_message_id ) {
            $msg = ( is_array( $decoded ) && isset( $decoded['message'] ) )
                ? $decoded['message']
                : __( 'Brevo API error', 'gnn-smtpmail' );
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'GNN SMTPMail - Brevo non-success response body: ' . $response_body );
            }
            $error = new WP_Error( 'brevo_api_failed', sprintf( __( 'Brevo API Error (HTTP %d): %s', 'gnn-smtpmail' ), $code, $msg ) );
            GNN_SMTPMail_Logger::insert( 'brevo', $to, $subject, 'error', $error->get_error_message() );
            do_action( 'wp_mail_failed', $error );
            return false;
        }

        // Succeeded
        $message_id = ! empty( $decoded['messageId'] )
            ? (string) $decoded['messageId']
            : implode( ',', array_map( 'strval', (array) $decoded['messageIds'] ) );
        // Strip angle brackets so sanitize_textarea_field() in the logger does not remove the ID as an HTML tag.
        $message_id  = str_replace( array( '<', '>' ), '', $message_id );
        $log_message = 'Message ID: ' . $message_id;
        if ( ! empty( $skipped_attachments ) ) {
            $log_message .= ' | ' . sprintf( __( 'Atlanan ekler: %s', 'gnn-smtpmail' ), implode( ', ', $skipped_attachments ) );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GNN SMTPMail - Brevo API successful send. Inserting success log.' );
        }
        GNN_SMTPMail_Logger::insert( 'brevo', $to, $subject, 'success', $log_message );

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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GNN SMTPMail - on_mail_failed hook triggered: ' . $wp_error->get_error_message() );
        }
        $s = $this->get_settings();
        $channel = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        if ( $channel === 'brevo' ) {
            return; // Already logged in pre_wp_mail_handler
        }
        $data = $wp_error->get_error_data();
        $to = isset( $data['to'] ) ? $data['to'] : '';
        $subject = isset( $data['subject'] ) ? $data['subject'] : '';
        $msg = $wp_error->get_error_message();
        GNN_SMTPMail_Logger::insert( $channel, is_array($to) ? implode(',', $to) : $to, $subject, 'error', $msg );
    }

    public function on_mail_succeeded( $mail_data ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GNN SMTPMail - on_mail_succeeded hook triggered.' );
        }
        $s = $this->get_settings();
        $channel = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        if ( $channel === 'brevo' ) {
            return; // Already logged in pre_wp_mail_handler
        }
        $to = isset( $mail_data['to'] ) ? $mail_data['to'] : '';
        $subject = isset( $mail_data['subject'] ) ? $mail_data['subject'] : '';
        GNN_SMTPMail_Logger::insert( $channel, is_array($to) ? implode(',', $to) : $to, $subject, 'success', 'OK' );
    }
}