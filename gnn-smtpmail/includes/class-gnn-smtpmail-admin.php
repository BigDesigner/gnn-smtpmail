<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GNN_SMTPMail_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_save_forms' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_notices', array( $this, 'check_mail_conflict_notice' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'gnn-smtpmail' ) === false ) return;
        wp_enqueue_style( 'gnn-smtpmail-admin', GNN_SMTPMAIL_URL . 'assets/admin.css', array(), GNN_SMTPMAIL_VERSION );
        wp_enqueue_script( 'gnn-smtpmail-admin', GNN_SMTPMAIL_URL . 'assets/admin.js', array(), GNN_SMTPMAIL_VERSION, true );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'GNN SMTPMail', 'gnn-smtpmail' ),
            __( 'GNN SMTPMail', 'gnn-smtpmail' ),
            'manage_options',
            'gnn-smtpmail',
            array( $this, 'page_welcome' ),
            'dashicons-email-alt2',
            '79.101'
        );

        add_submenu_page( 'gnn-smtpmail', __( 'Ayarlar', 'gnn-smtpmail' ), __( 'Ayarlar', 'gnn-smtpmail' ), 'manage_options', 'gnn-smtpmail-custom', array( $this, 'page_custom' ) );
        add_submenu_page( 'gnn-smtpmail', __( 'Test Mail', 'gnn-smtpmail' ), __( 'Test Mail', 'gnn-smtpmail' ), 'manage_options', 'gnn-smtpmail-test', array( $this, 'page_test' ) );
        add_submenu_page( 'gnn-smtpmail', __( 'Loglar', 'gnn-smtpmail' ), __( 'Loglar', 'gnn-smtpmail' ), 'manage_options', 'gnn-smtpmail-logs', array( $this, 'page_logs' ) );
    }

    private function settings() {
        $s = get_option( GNN_SMTPMAIL_OPTION, array() );
        if ( ! isset( $s['mailer_type'] ) ) { $s['mailer_type'] = 'custom'; }
        if ( ! isset( $s['custom'] ) ) { $s['custom'] = array(); }
        if ( ! isset( $s['brevo'] ) ) { $s['brevo'] = array(); }
        return $s;
    }

    public function maybe_save_forms() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Save settings
        if ( isset( $_POST['gnn_custom_save'] ) ) {
            check_admin_referer( 'gnn_custom_save_action', 'gnn_custom_nonce' );

            $mailer_type = isset($_POST['mailer_type']) && in_array($_POST['mailer_type'], array('custom', 'brevo'), true) ? sanitize_text_field($_POST['mailer_type']) : 'custom';

            $existing_settings = $this->settings();
            $smtp_pass_const   = defined( 'GNN_SMTPMAIL_SMTP_PASS' ) && is_string( GNN_SMTPMAIL_SMTP_PASS );
            $brevo_key_const   = defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY );

            $port = isset( $_POST['port'] ) ? intval( $_POST['port'] ) : 587;
            if ( $port < 1 || $port > 65535 ) {
                $port = 587;
            }

            if ( $smtp_pass_const ) {
                $smtp_password = (string) ( $existing_settings['custom']['password'] ?? '' );
            } elseif ( ! empty( $_POST['clear_smtp_password'] ) ) {
                $smtp_password = '';
            } elseif ( isset( $_POST['password'] ) && '' !== $_POST['password'] ) {
                $smtp_password = (string) wp_unslash( $_POST['password'] );
            } else {
                $smtp_password = (string) ( $existing_settings['custom']['password'] ?? '' );
            }

            $custom = array(
                'enabled'     => isset($_POST['enabled']) ? 1 : 0,
                'host'        => isset($_POST['host']) ? sanitize_text_field( wp_unslash($_POST['host']) ) : '',
                'port'        => $port,
                'smtp_secure' => isset($_POST['smtp_secure']) && in_array($_POST['smtp_secure'], array('ssl','tls','none'), true) ? sanitize_text_field( wp_unslash($_POST['smtp_secure']) ) : 'tls',
                'auth'        => isset($_POST['auth']) ? 1 : 0,
                'username'    => isset($_POST['username']) ? sanitize_text_field( wp_unslash($_POST['username']) ) : '',
                'password'    => $smtp_password,
                'from_email'  => isset($_POST['from_email']) ? sanitize_email( wp_unslash($_POST['from_email']) ) : '',
                'from_name'   => isset($_POST['from_name']) ? sanitize_text_field( wp_unslash($_POST['from_name']) ) : '',
            );

            $existing_brevo_key = (string) ( $existing_settings['brevo']['api_key'] ?? '' );
            if ( $brevo_key_const ) {
                $brevo_api_key = $existing_brevo_key;
            } elseif ( ! empty( $_POST['clear_brevo_api_key'] ) ) {
                $brevo_api_key = '';
            } elseif ( isset( $_POST['brevo_api_key'] ) && '' !== trim( (string) wp_unslash( $_POST['brevo_api_key'] ) ) ) {
                $brevo_api_key = sanitize_text_field( wp_unslash( $_POST['brevo_api_key'] ) );
            } else {
                $brevo_api_key = $existing_brevo_key;
            }

            if ( $brevo_api_key !== $existing_brevo_key && '' !== $existing_brevo_key ) {
                delete_transient( $this->get_brevo_cache_key( $existing_brevo_key ) );
            }

            if ( ! empty( $_POST['clear_brevo_api_key'] ) ) {
                $brevo_from_name  = '';
                $brevo_from_email = '';
            } elseif ( isset( $_POST['brevo_sender_select'] ) ) {
                $brevo_sender_select = sanitize_text_field( wp_unslash( $_POST['brevo_sender_select'] ) );
                $brevo_from_name     = '';
                $brevo_from_email    = '';
                if ( ! empty( $brevo_sender_select ) && strpos( $brevo_sender_select, '|' ) !== false ) {
                    list( $raw_brevo_name, $raw_brevo_email ) = explode( '|', $brevo_sender_select, 2 );
                    $brevo_from_name  = sanitize_text_field( trim( $raw_brevo_name ) );
                    $brevo_from_email = sanitize_email( trim( $raw_brevo_email ) );
                }
            } else {
                $brevo_from_name  = (string) ( $existing_settings['brevo']['from_name'] ?? '' );
                $brevo_from_email = (string) ( $existing_settings['brevo']['from_email'] ?? '' );
            }

            $brevo = array(
                'api_key'    => $brevo_api_key,
                'from_email' => $brevo_from_email,
                'from_name'  => $brevo_from_name,
            );

            $settings = array(
                'mailer_type' => $mailer_type,
                'custom'      => $custom,
                'brevo'       => $brevo,
            );
            update_option( GNN_SMTPMAIL_OPTION, $settings );
            add_settings_error( 'gnn-smtpmail', 'saved', __( 'Ayarlar kaydedildi.', 'gnn-smtpmail' ), 'updated' );
        }

        // Refresh Brevo senders cache
        if ( isset( $_POST['gnn_refresh_brevo_senders'] ) ) {
            check_admin_referer( 'gnn_refresh_brevo_action', 'gnn_refresh_brevo_nonce' );
            $s                = $this->settings();
            $brevo_key_const  = defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY );
            $active_brevo_key = $brevo_key_const ? GNN_SMTPMAIL_BREVO_KEY : ( $s['brevo']['api_key'] ?? '' );
            if ( ! empty( $active_brevo_key ) ) {
                delete_transient( $this->get_brevo_cache_key( $active_brevo_key ) );
            }
            add_settings_error( 'gnn-smtpmail', 'brevo_refreshed', __( 'Brevo gönderici listesi yenilendi.', 'gnn-smtpmail' ), 'updated' );
        }

        // Send test mail
        if ( isset( $_POST['gnn_test_send'] ) ) {
            check_admin_referer( 'gnn_test_send_action', 'gnn_test_nonce' );

            $to          = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
            $is_html     = ! empty( $_POST['test_html'] );
            $is_custom   = ! empty( $_POST['test_custom'] );
            $default_sub = __( 'GNN SMTPMail Test', 'gnn-smtpmail' );
            $default_msg = __( 'Bu bir test e-postasıdır. GNN SMTPMail üzerinden gönderilmiştir.', 'gnn-smtpmail' );

            $subject = ( $is_custom && isset( $_POST['subject'] ) && '' !== trim( (string) wp_unslash( $_POST['subject'] ) ) )
                ? sanitize_text_field( wp_unslash( $_POST['subject'] ) )
                : $default_sub;
            $message = ( $is_custom && isset( $_POST['message'] ) && '' !== trim( (string) wp_unslash( $_POST['message'] ) ) )
                ? wp_kses_post( wp_unslash( $_POST['message'] ) )
                : $default_msg;

            if ( ! is_email( $to ) ) {
                add_settings_error( 'gnn-smtpmail', 'invalid_email', __( 'Geçerli bir e-posta adresi girin.', 'gnn-smtpmail' ), 'error' );
                return;
            }

            if ( $is_html ) {
                $html_filter = function() { return 'text/html'; };
                add_filter( 'wp_mail_content_type', $html_filter );
                $sent = wp_mail( $to, $subject, wpautop( $message ) );
                remove_filter( 'wp_mail_content_type', $html_filter );
            } else {
                $sent = wp_mail( $to, $subject, wp_strip_all_tags( $message ) );
            }

            if ( $sent ) {
                add_settings_error( 'gnn-smtpmail', 'test_ok', __( 'Test e-postası başarıyla gönderildi. Logları kontrol edebilirsiniz.', 'gnn-smtpmail' ), 'updated' );
            } else {
                add_settings_error( 'gnn-smtpmail', 'test_fail', __( 'Test e-postası gönderilemedi. Hata detayları loglarda listelenecektir.', 'gnn-smtpmail' ), 'error' );
            }
        }

        // Clear logs
        if ( isset( $_POST['gnn_clear_logs'] ) ) {
            check_admin_referer( 'gnn_clear_logs_action', 'gnn_clear_logs_nonce' );
            GNN_SMTPMail_Logger::clear_all();
            add_settings_error( 'gnn-smtpmail', 'logs_cleared', __( 'Loglar temizlendi.', 'gnn-smtpmail' ), 'updated' );
        }

        // Export logs (CSV / JSON)
        if ( isset( $_POST['gnn_export_logs'] ) ) {
            check_admin_referer( 'gnn_export_logs_action', 'gnn_export_logs_nonce' );

            $raw_format    = sanitize_key( wp_unslash( $_POST['gnn_export_logs'] ) );
            $format        = in_array( $raw_format, array( 'csv', 'json' ), true ) ? $raw_format : 'csv';
            $raw_status    = isset( $_POST['export_status'] ) ? sanitize_text_field( wp_unslash( $_POST['export_status'] ) ) : '';
            $export_status = in_array( $raw_status, array( 'success', 'error' ), true ) ? $raw_status : '';
            $rows          = GNN_SMTPMail_Logger::get_logs_for_export( $export_status );
            $filename      = 'gnn-smtpmail-logs-' . wp_date( 'Y-m-d' ) . '.' . $format;

            nocache_headers();
            if ( 'json' === $format ) {
                header( 'Content-Type: application/json; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
                echo wp_json_encode( $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
                exit;
            }

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
            $out = fopen( 'php://output', 'w' );
            if ( $out ) {
                fwrite( $out, "\xEF\xBB\xBF" );
                fputcsv( $out, array( 'ID', 'Logged At', 'Channel', 'Recipient', 'Subject', 'Status', 'Message' ) );
                foreach ( $rows as $row ) {
                    fputcsv(
                        $out,
                        array(
                            intval( $row['id'] ?? 0 ),
                            $this->escape_csv_cell( $row['logged_at'] ?? '' ),
                            $this->escape_csv_cell( $row['channel'] ?? '' ),
                            $this->escape_csv_cell( $row['recipient'] ?? '' ),
                            $this->escape_csv_cell( $row['subject'] ?? '' ),
                            $this->escape_csv_cell( $row['status'] ?? '' ),
                            $this->escape_csv_cell( $row['message'] ?? '' ),
                        )
                    );
                }
                fclose( $out );
            }
            exit;
        }
    }

    /**
     * Build transient cache key for Brevo verified senders.
     *
     * @param string $api_key Brevo API key.
     * @return string
     */
    private function get_brevo_cache_key( $api_key ) {
        return 'gnn_smtpmail_brevo_senders_' . substr( wp_hash( $api_key ), 0, 16 );
    }

    /**
     * Prevent CSV formula injection by prefixing cells starting with formula triggers.
     *
     * @param mixed $value Cell value.
     * @return string
     */
    private function escape_csv_cell( $value ) {
        $str = (string) $value;
        if ( '' !== $str && preg_match( '/^\s*[=+\-@\t\r]/', $str ) ) {
            $str = "'" . $str;
        }
        return $str;
    }

    /**
     * Render top tab navigation across plugin admin pages.
     *
     * @param string $active_slug Active page slug.
     */
    private function render_tabs( $active_slug ) {
        $tabs = array(
            'gnn-smtpmail'        => __( 'Genel Bakış', 'gnn-smtpmail' ),
            'gnn-smtpmail-custom' => __( 'Ayarlar', 'gnn-smtpmail' ),
            'gnn-smtpmail-test'   => __( 'E-posta Testi', 'gnn-smtpmail' ),
            'gnn-smtpmail-logs'   => __( 'Loglar', 'gnn-smtpmail' ),
        );
        echo '<nav class="gnn-tabs" aria-label="' . esc_attr__( 'GNN SMTPMail Menüsü', 'gnn-smtpmail' ) . '">';
        foreach ( $tabs as $slug => $label ) {
            $url = admin_url( 'admin.php?page=' . $slug );
            $cls = ( $slug === $active_slug ) ? 'gnn-tab is-active' : 'gnn-tab';
            echo '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
    }

    /**
     * Render an accessible pill toggle switch.
     *
     * @param string $id      Input ID attribute.
     * @param string $name    Input name attribute.
     * @param bool   $checked Current checked state.
     */
    private function render_switch( $id, $name, $checked ) {
        $on_text  = __( 'AÇIK', 'gnn-smtpmail' );
        $off_text = __( 'KAPALI', 'gnn-smtpmail' );
        ?>
        <label class="gnn-switch" for="<?php echo esc_attr( $id ); ?>">
            <input
                type="checkbox"
                class="gnn-switch-input"
                id="<?php echo esc_attr( $id ); ?>"
                name="<?php echo esc_attr( $name ); ?>"
                value="1"
                role="switch"
                aria-checked="<?php echo $checked ? 'true' : 'false'; ?>"
                <?php checked( $checked ); ?>
            />
            <span class="gnn-switch-track" aria-hidden="true"></span>
            <span class="gnn-switch-state" data-on="<?php echo esc_attr( $on_text ); ?>" data-off="<?php echo esc_attr( $off_text ); ?>">
                <?php echo esc_html( $checked ? $on_text : $off_text ); ?>
            </span>
        </label>
        <?php
    }

    public function page_welcome() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'gnn-smtpmail' ) );
        }
        $s            = $this->settings();
        $mailer_type  = isset( $s['mailer_type'] ) ? $s['mailer_type'] : 'custom';
        $stats        = GNN_SMTPMail_Logger::get_stats();
        $wp_mail_src  = $this->get_wp_mail_source();
        $has_conflict = ! empty( $wp_mail_src ) && strpos( $wp_mail_src, 'wp-includes/pluggable.php' ) === false;
        $update_url   = wp_nonce_url( admin_url( 'plugins.php?gnn_smtpmail_check_update=1' ), 'gnn_smtpmail_manual_update' );

        if ( 'brevo' === $mailer_type ) {
            $brevo_key      = ( defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY ) ) ? GNN_SMTPMAIL_BREVO_KEY : ( $s['brevo']['api_key'] ?? '' );
            $channel_label  = __( 'Brevo API', 'gnn-smtpmail' );
            $channel_active = ! empty( $brevo_key );
        } else {
            $channel_label  = __( 'Custom SMTP', 'gnn-smtpmail' );
            $channel_active = ! empty( $s['custom']['enabled'] );
        }
        ?>
        <div class="wrap gnn-wrap">
            <h1><?php esc_html_e('GNN SMTPMail (Custom SMTP & Brevo API)', 'gnn-smtpmail'); ?></h1>
            <?php $this->render_tabs( 'gnn-smtpmail' ); ?>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <div class="gnn-card">
                <div class="gnn-card-header">
                    <h2><?php esc_html_e( 'Sistem Durumu Özeti', 'gnn-smtpmail' ); ?></h2>
                    <span class="gnn-badge <?php echo $channel_active ? 'success' : 'error'; ?>">
                        <?php echo esc_html( $channel_active ? __( 'Aktif', 'gnn-smtpmail' ) : __( 'Devre Dışı', 'gnn-smtpmail' ) ); ?>
                    </span>
                </div>
                <div class="gnn-card-body-padded">
                    <div class="gnn-stats-grid">
                        <div class="gnn-stat-card">
                            <span class="gnn-stat-label"><?php esc_html_e( 'Aktif Kanal', 'gnn-smtpmail' ); ?></span>
                            <strong class="gnn-stat-value"><?php echo esc_html( $channel_label ); ?></strong>
                        </div>
                        <div class="gnn-stat-card">
                            <span class="gnn-stat-label"><?php esc_html_e( 'Toplam Log', 'gnn-smtpmail' ); ?></span>
                            <strong class="gnn-stat-value"><?php echo esc_html( (string) $stats['total'] ); ?></strong>
                        </div>
                        <div class="gnn-stat-card">
                            <span class="gnn-stat-label"><?php esc_html_e( 'Başarılı', 'gnn-smtpmail' ); ?></span>
                            <strong class="gnn-stat-value gnn-text-success"><?php echo esc_html( (string) $stats['success'] ); ?></strong>
                        </div>
                        <div class="gnn-stat-card">
                            <span class="gnn-stat-label"><?php esc_html_e( 'Hatalı', 'gnn-smtpmail' ); ?></span>
                            <strong class="gnn-stat-value gnn-text-error"><?php echo esc_html( (string) $stats['error'] ); ?></strong>
                        </div>
                    </div>
                    <p class="gnn-overview-meta">
                        <strong><?php esc_html_e( 'wp_mail() Kaynağı:', 'gnn-smtpmail' ); ?></strong>
                        <?php if ( $has_conflict ) : ?>
                            <span class="gnn-text-error gnn-text-bold"><?php echo esc_html( $wp_mail_src ); ?></span>
                        <?php else : ?>
                            <span class="gnn-text-success"><?php esc_html_e( 'Çekirdek (Uyumlu)', 'gnn-smtpmail' ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $stats['last_logged_at'] ) ) : ?>
                            &middot; <strong><?php esc_html_e( 'Son Kayıt:', 'gnn-smtpmail' ); ?></strong> <?php echo esc_html( $stats['last_logged_at'] ); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="gnn-card">
                <div class="gnn-card-header">
                    <h2><?php esc_html_e( 'Hızlı Başlangıç', 'gnn-smtpmail' ); ?></h2>
                </div>
                <div class="gnn-card-body-padded">
                    <p><?php esc_html_e('Custom SMTP veya Brevo API ile tüm WordPress e-postalarınızı güvenli şekilde gönderin. Üstteki sekmelerden ayarlarınızı yapın, ardından E-posta Testi sayfasından doğrulayın ve Loglar sayfasından durumu takip edin.', 'gnn-smtpmail'); ?></p>
                    <div class="gnn-grid">
                        <a class="button button-primary" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-custom') ); ?>"><?php esc_html_e('Ayarlar', 'gnn-smtpmail'); ?></a>
                        <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-test') ); ?>"><?php esc_html_e('E-posta Testi', 'gnn-smtpmail'); ?></a>
                        <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-logs') ); ?>"><?php esc_html_e('Loglar', 'gnn-smtpmail'); ?></a>
                        <a class="button" href="<?php echo esc_url( $update_url ); ?>"><?php esc_html_e('Güncellemeleri Denetle', 'gnn-smtpmail'); ?></a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function page_custom() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'gnn-smtpmail' ) );
        }
        $s = $this->settings();
        $mailer_type = isset($s['mailer_type']) ? $s['mailer_type'] : 'custom';
        $c = isset($s['custom']) ? $s['custom'] : array();
        $b = isset($s['brevo']) ? $s['brevo'] : array();
        $smtp_pass_const = defined( 'GNN_SMTPMAIL_SMTP_PASS' ) && is_string( GNN_SMTPMAIL_SMTP_PASS );
        $brevo_key_const = defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY );
        $active_brevo_key = $brevo_key_const ? GNN_SMTPMAIL_BREVO_KEY : ( $b['api_key'] ?? '' );
        ?>
        <div class="wrap gnn-wrap">
            <h1><?php esc_html_e('E-posta Gönderim Ayarları', 'gnn-smtpmail'); ?></h1>
            <?php $this->render_tabs( 'gnn-smtpmail-custom' ); ?>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <form method="post">
                <?php wp_nonce_field( 'gnn_custom_save_action', 'gnn_custom_nonce' ); ?>
                <div class="gnn-card">
                    <div class="gnn-card-header">
                        <h2><?php esc_html_e( 'Gönderim Kanalı', 'gnn-smtpmail' ); ?></h2>
                    </div>
                    <div class="gnn-card-body">
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_mailer_type"><?php esc_html_e('Gönderim Yöntemi', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <select name="mailer_type" id="gnn_mailer_type">
                                    <option value="custom" <?php selected($mailer_type, 'custom'); ?>><?php esc_html_e('Custom SMTP', 'gnn-smtpmail'); ?></option>
                                    <option value="brevo" <?php selected($mailer_type, 'brevo'); ?>><?php esc_html_e('Brevo API', 'gnn-smtpmail'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('WordPress e-postalarının hangi kanal üzerinden iletileceğini seçin.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="gnn_section_custom" class="gnn-settings-section gnn-card"<?php echo $mailer_type === 'custom' ? '' : ' hidden'; ?>>
                    <div class="gnn-card-header">
                        <h2><?php esc_html_e('Custom SMTP Ayarları', 'gnn-smtpmail'); ?></h2>
                    </div>
                    <div class="gnn-card-body">
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_enabled"><?php esc_html_e('Custom SMTP\'yi Etkinleştir', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php $this->render_switch( 'gnn_smtp_enabled', 'enabled', ! empty( $c['enabled'] ) ); ?>
                                <p class="description"><?php esc_html_e('WordPress varsayılan mail gönderimi yerine özel SMTP sunucusunu kullanmak için etkinleştirin.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><?php esc_html_e('Hazır Sağlayıcı Şablonu', 'gnn-smtpmail'); ?></div>
                            <div class="gnn-form-control">
                                <div class="gnn-presets" role="group" aria-label="<?php esc_attr_e( 'SMTP sağlayıcı şablonları', 'gnn-smtpmail' ); ?>">
                                    <button type="button" class="button gnn-preset-btn" data-host="smtp.gmail.com" data-port="587" data-secure="tls" data-auth="1">Gmail</button>
                                    <button type="button" class="button gnn-preset-btn" data-host="smtp.office365.com" data-port="587" data-secure="tls" data-auth="1">Outlook / M365</button>
                                    <button type="button" class="button gnn-preset-btn" data-host="smtp.yandex.com" data-port="465" data-secure="ssl" data-auth="1">Yandex</button>
                                    <button type="button" class="button gnn-preset-btn" data-host="smtp.zoho.com" data-port="587" data-secure="tls" data-auth="1">Zoho</button>
                                    <button type="button" class="button gnn-preset-btn" data-host="email-smtp.eu-west-1.amazonaws.com" data-port="587" data-secure="tls" data-auth="1">Amazon SES</button>
                                </div>
                                <p class="description"><?php esc_html_e('Seçtiğiniz sağlayıcının sunucu, port ve şifreleme ayarlarını otomatik doldurur.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_host"><?php esc_html_e('Sunucu', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <input type="text" id="gnn_smtp_host" name="host" value="<?php echo esc_attr( $c['host'] ?? '' ); ?>" placeholder="smtp.example.com" />
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_port"><?php esc_html_e('Port', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <input type="number" id="gnn_smtp_port" name="port" min="1" max="65535" value="<?php echo esc_attr( $c['port'] ?? 587 ); ?>" />
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_secure"><?php esc_html_e('Şifreleme', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <select id="gnn_smtp_secure" name="smtp_secure">
                                    <?php $sel = $c['smtp_secure'] ?? 'tls'; ?>
                                    <option value="none" <?php selected($sel,'none'); ?>><?php esc_html_e('Yok', 'gnn-smtpmail'); ?></option>
                                    <option value="tls" <?php selected($sel,'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected($sel,'ssl'); ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_auth"><?php esc_html_e('SMTP Kimlik Doğrulama', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php $auth_enabled = ! empty( $c['auth'] ); ?>
                                <?php $this->render_switch( 'gnn_smtp_auth', 'auth', $auth_enabled ); ?>
                            </div>
                        </div>
                        <div id="gnn_smtp_auth_fields"<?php echo $auth_enabled ? '' : ' hidden'; ?>>
                            <div class="gnn-form-row">
                                <div class="gnn-form-label"><label for="gnn_smtp_username"><?php esc_html_e('Kullanıcı Adı', 'gnn-smtpmail'); ?></label></div>
                                <div class="gnn-form-control">
                                    <input type="text" id="gnn_smtp_username" name="username" value="<?php echo esc_attr( $c['username'] ?? '' ); ?>" autocomplete="off" />
                                </div>
                            </div>
                            <div class="gnn-form-row">
                                <div class="gnn-form-label"><label for="gnn_smtp_password"><?php esc_html_e('Şifre', 'gnn-smtpmail'); ?></label></div>
                                <div class="gnn-form-control">
                                    <?php if ( $smtp_pass_const ) : ?>
                                        <input type="password" id="gnn_smtp_password" value="********" disabled />
                                        <p class="description"><?php esc_html_e('wp-config.php içerisinde GNN_SMTPMAIL_SMTP_PASS sabiti ile tanımlandı.', 'gnn-smtpmail'); ?></p>
                                    <?php else : ?>
                                        <?php $has_smtp_pass = ! empty( $c['password'] ); ?>
                                        <input type="password" id="gnn_smtp_password" name="password" value="" placeholder="<?php echo $has_smtp_pass ? '••••••••' : ''; ?>" autocomplete="new-password" />
                                        <?php if ( $has_smtp_pass ) : ?>
                                            <p class="description"><?php esc_html_e('Mevcut şifre kayıtlıdır. Değiştirmek istemiyorsanız boş bırakın.', 'gnn-smtpmail'); ?></p>
                                            <label class="gnn-clear-secret"><input type="checkbox" name="clear_smtp_password" value="1" /> <?php esc_html_e('Kayıtlı şifreyi temizle', 'gnn-smtpmail'); ?></label>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_from_email"><?php esc_html_e('Gönderen E-posta', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <input type="email" id="gnn_smtp_from_email" name="from_email" value="<?php echo esc_attr( $c['from_email'] ?? '' ); ?>" />
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_smtp_from_name"><?php esc_html_e('Gönderen Adı', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <input type="text" id="gnn_smtp_from_name" name="from_name" value="<?php echo esc_attr( $c['from_name'] ?? '' ); ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <div id="gnn_section_brevo" class="gnn-settings-section gnn-card"<?php echo $mailer_type === 'brevo' ? '' : ' hidden'; ?>>
                    <div class="gnn-card-header">
                        <h2><?php esc_html_e('Brevo API Ayarları', 'gnn-smtpmail'); ?></h2>
                    </div>
                    <?php
                    $senders = array();
                    $api_error = '';
                    if ( ! empty( $active_brevo_key ) ) {
                        $cache_key = $this->get_brevo_cache_key( $active_brevo_key );
                        $senders = get_transient( $cache_key );
                        if ( false === $senders ) {
                            $response = wp_remote_get( 'https://api.brevo.com/v3/senders', array(
                                'headers' => array(
                                    'api-key' => $active_brevo_key,
                                    'Accept'  => 'application/json',
                                ),
                                'timeout' => 10,
                            ) );
                            if ( is_wp_error( $response ) ) {
                                $api_error = __( 'Brevo API bağlantı hatası: ', 'gnn-smtpmail' ) . $response->get_error_message();
                                $senders = array();
                            } else {
                                $code = wp_remote_retrieve_response_code( $response );
                                $body = wp_remote_retrieve_body( $response );
                                if ( $code === 200 ) {
                                    $decoded = json_decode( $body, true );
                                    if ( isset( $decoded['senders'] ) && is_array( $decoded['senders'] ) ) {
                                        $senders = $decoded['senders'];
                                        set_transient( $cache_key, $senders, HOUR_IN_SECONDS );
                                    } else {
                                        $senders = array();
                                    }
                                } else {
                                    $decoded = json_decode( $body, true );
                                    $msg = isset( $decoded['message'] ) ? $decoded['message'] : __( 'Bilinmeyen hata', 'gnn-smtpmail' );
                                    $api_error = sprintf( __( 'Brevo API Hatası (HTTP %d): %s', 'gnn-smtpmail' ), $code, $msg );
                                    $senders = array();
                                }
                            }
                        }
                    }
                    ?>
                    <div class="gnn-card-body">
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_brevo_api_key"><?php esc_html_e('Brevo API Key', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php if ( $brevo_key_const ) : ?>
                                    <input type="password" id="gnn_brevo_api_key" value="********" disabled />
                                    <p class="description"><?php esc_html_e('wp-config.php içerisinde GNN_SMTPMAIL_BREVO_KEY sabiti ile tanımlandı.', 'gnn-smtpmail'); ?></p>
                                <?php else : ?>
                                    <?php $has_brevo_key = ! empty( $b['api_key'] ); ?>
                                    <input type="password" id="gnn_brevo_api_key" name="brevo_api_key" value="" placeholder="<?php echo $has_brevo_key ? '••••••••' : ''; ?>" autocomplete="new-password" />
                                    <?php if ( $has_brevo_key ) : ?>
                                        <p class="description"><?php esc_html_e('Mevcut API anahtarı kayıtlıdır. Değiştirmek istemiyorsanız boş bırakın.', 'gnn-smtpmail'); ?></p>
                                        <label class="gnn-clear-secret"><input type="checkbox" name="clear_brevo_api_key" value="1" /> <?php esc_html_e('Kayıtlı API anahtarını temizle', 'gnn-smtpmail'); ?></label>
                                    <?php else : ?>
                                        <p class="description"><?php esc_html_e('Brevo hesabınızdaki API Key değerini buraya girin ve kaydedin.', 'gnn-smtpmail'); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_brevo_sender_select"><?php esc_html_e('Gönderici Seçin', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php if ( empty( $active_brevo_key ) ) : ?>
                                    <p class="description gnn-text-error"><?php esc_html_e('Gönderici seçmek için önce API anahtarınızı girip kaydedin.', 'gnn-smtpmail'); ?></p>
                                <?php elseif ( ! empty( $api_error ) ) : ?>
                                    <p class="description gnn-text-error"><?php echo esc_html( $api_error ); ?></p>
                                    <?php wp_nonce_field( 'gnn_refresh_brevo_action', 'gnn_refresh_brevo_nonce' ); ?>
                                    <p class="gnn-form-spaced"><button type="submit" name="gnn_refresh_brevo_senders" value="1" class="button"><?php esc_html_e( 'Göndericileri Yenile', 'gnn-smtpmail' ); ?></button></p>
                                <?php else : ?>
                                    <div class="gnn-inline">
                                        <select id="gnn_brevo_sender_select" name="brevo_sender_select">
                                            <option value=""><?php esc_html_e('-- Seçin --', 'gnn-smtpmail'); ?></option>
                                            <?php
                                            $current_sender = ($b['from_name'] ?? '') . '|' . ($b['from_email'] ?? '');
                                            foreach ( $senders as $sender ) {
                                                if ( empty($sender['active']) ) {
                                                    continue;
                                                }
                                                $val = $sender['name'] . '|' . $sender['email'];
                                                $label = $sender['name'] . ' (' . $sender['email'] . ')';
                                                ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected( $current_sender, $val ); ?>>
                                                    <?php echo esc_html($label); ?>
                                                </option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                        <?php wp_nonce_field( 'gnn_refresh_brevo_action', 'gnn_refresh_brevo_nonce' ); ?>
                                        <button type="submit" name="gnn_refresh_brevo_senders" value="1" class="button"><?php esc_html_e( 'Göndericileri Yenile', 'gnn-smtpmail' ); ?></button>
                                    </div>
                                    <p class="description"><?php esc_html_e('Gönderimlerin spam klasörüne düşmemesi için Brevo hesabınızda doğrulanmış bir alan adı/gönderici seçin.', 'gnn-smtpmail'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="gnn-actions">
                    <button type="submit" name="gnn_custom_save" class="button button-primary"><?php esc_html_e('Ayarları Kaydet', 'gnn-smtpmail'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    public function page_test() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'gnn-smtpmail' ) );
        }
        $s = $this->settings();
        $mailer_type = isset($s['mailer_type']) ? $s['mailer_type'] : 'custom';
        $active = false;
        
        if ( $mailer_type === 'brevo' ) {
            $brevo_key = ( defined( 'GNN_SMTPMAIL_BREVO_KEY' ) && is_string( GNN_SMTPMAIL_BREVO_KEY ) ) ? GNN_SMTPMAIL_BREVO_KEY : ( $s['brevo']['api_key'] ?? '' );
            $active = ! empty( $brevo_key );
            $channel_name = 'BREVO API';
        } else {
            $active = ! empty( $s['custom']['enabled'] );
            $channel_name = 'CUSTOM SMTP';
        }
        ?>
        <div class="wrap gnn-wrap">
            <h1><?php esc_html_e('E-posta Testi', 'gnn-smtpmail'); ?></h1>
            <?php $this->render_tabs( 'gnn-smtpmail-test' ); ?>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <?php if ( ! $active ) : ?>
                <div class="notice notice-warning inline"><p><?php echo sprintf( esc_html__('%s şu an devre dışı veya yapılandırılmamış.', 'gnn-smtpmail'), esc_html( $channel_name ) ); ?></p></div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field( 'gnn_test_send_action', 'gnn_test_nonce' ); ?>
                <div class="gnn-card">
                    <div class="gnn-card-header">
                        <h2><?php esc_html_e('Bir Test Gönderin', 'gnn-smtpmail'); ?></h2>
                    </div>
                    <div class="gnn-card-body">
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_test_to"><?php esc_html_e('Şuna Gönder', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <input type="email" id="gnn_test_to" name="to" required value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                                <p class="description"><?php esc_html_e('Test e-postasını göndermek istediğiniz e-posta adresini girin.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_test_html"><?php esc_html_e('HTML', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php $this->render_switch( 'gnn_test_html', 'test_html', true ); ?>
                                <p class="description"><?php esc_html_e('Bu e-postayı HTML biçiminde göndermek için etkinleştirin. Düz metin biçiminde göndermek için devre dışı bırakın.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                        <div class="gnn-form-row">
                            <div class="gnn-form-label"><label for="gnn_test_custom"><?php esc_html_e('Özel E-posta', 'gnn-smtpmail'); ?></label></div>
                            <div class="gnn-form-control">
                                <?php $this->render_switch( 'gnn_test_custom', 'test_custom', false ); ?>
                                <p class="description"><?php esc_html_e('Önceden tanımlanmış e-posta şablonunu kendi içeriğinizle değiştirin.', 'gnn-smtpmail'); ?></p>
                            </div>
                        </div>
                        <div id="gnn_test_custom_fields" hidden>
                            <div class="gnn-form-row">
                                <div class="gnn-form-label"><label for="gnn_test_subject"><?php esc_html_e('Konu', 'gnn-smtpmail'); ?></label></div>
                                <div class="gnn-form-control">
                                    <input type="text" id="gnn_test_subject" name="subject" value="<?php esc_attr_e('GNN SMTPMail Test', 'gnn-smtpmail'); ?>" />
                                </div>
                            </div>
                            <div class="gnn-form-row">
                                <div class="gnn-form-label"><label for="gnn_test_message"><?php esc_html_e('Mesaj', 'gnn-smtpmail'); ?></label></div>
                                <div class="gnn-form-control">
                                    <textarea id="gnn_test_message" name="message" rows="5"><?php esc_html_e('Bu bir test e-postasıdır. GNN SMTPMail üzerinden gönderilmiştir.', 'gnn-smtpmail'); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="gnn-actions">
                    <button type="submit" name="gnn_test_send" class="button button-primary"><?php esc_html_e('Test e-postası gönder', 'gnn-smtpmail'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    public function page_logs() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'gnn-smtpmail' ) );
        }
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $raw_status = isset($_GET['status']) ? sanitize_text_field( wp_unslash($_GET['status']) ) : '';
        $status = in_array( $raw_status, array( 'success', 'error' ), true ) ? $raw_status : '';
        $per_page = 20;
        $data = GNN_SMTPMail_Logger::get_logs( $paged, $per_page, $status );
        $rows = $data['rows'];
        $total = $data['total'];
        $total_pages = max(1, ceil( $total / $per_page ));

        ?>
        <div class="wrap gnn-wrap">
            <h1><?php esc_html_e('E-posta Logları', 'gnn-smtpmail'); ?></h1>
            <?php $this->render_tabs( 'gnn-smtpmail-logs' ); ?>
            <?php
            if ( current_user_can('manage_options') ) {
                global $wpdb;
                $table = $wpdb->prefix . GNN_SMTPMAIL_TABLE;
                $show_table = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
                $table_exists = ! empty( $show_table ) && strcasecmp( $show_table, $table ) === 0;

                $wp_mail_defined_in = $this->get_wp_mail_source();

                $has_conflict = strpos( $wp_mail_defined_in, 'wp-includes/pluggable.php' ) === false;
                $last_insert_error = get_option( 'gnn_smtpmail_last_insert_error', '' );
                $last_db_error = $wpdb->last_error;

                $has_issue = ! $table_exists || $has_conflict || ! empty( $last_insert_error ) || ! empty( $last_db_error );

                if ( $has_issue ) {
                    echo '<div class="notice notice-info inline gnn-notice-spaced"><p>';
                    if ( $table_exists ) {
                        $count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `" . esc_sql( $table ) . "`" );
                        echo '<strong>' . esc_html__( 'Sistem Bilgisi:', 'gnn-smtpmail' ) . '</strong> ' . sprintf( esc_html__( 'Log tablosu aktif. Toplam log satırı: %d', 'gnn-smtpmail' ), intval( $count ) ) . '<br>';
                        
                        echo '<strong>' . esc_html__( 'wp_mail() Kaynağı:', 'gnn-smtpmail' ) . '</strong> ';
                        if ( ! $has_conflict ) {
                            echo '<span class="gnn-text-success">' . esc_html__( 'wp-includes/pluggable.php (Çekirdek - Uyumlu)', 'gnn-smtpmail' ) . '</span><br>';
                        } else {
                            echo '<span class="gnn-text-error gnn-text-bold">' . esc_html( $wp_mail_defined_in ) . ' ' . esc_html__( '(Çakışma! Başka bir eklenti wp_mail() fonksiyonunu ele geçirmiş durumda. Bizim eklentimiz pasif kalacaktır.)', 'gnn-smtpmail' ) . '</span><br>';
                        }
                        
                        if ( ! empty( $last_insert_error ) ) {
                            echo '<strong class="gnn-text-error">' . esc_html__( 'Son Log Kayıt Hatası:', 'gnn-smtpmail' ) . '</strong> ' . esc_html( $last_insert_error ) . '<br>';
                        }
                        if ( ! empty( $last_db_error ) ) {
                            echo esc_html__( 'Son Veri Tabanı Hatası:', 'gnn-smtpmail' ) . ' ' . esc_html( $last_db_error ) . '<br>';
                        }
                    } else {
                        echo '<strong>' . esc_html__( 'Sistem Hatası:', 'gnn-smtpmail' ) . '</strong> ' . sprintf( esc_html__( '`%s` tablosu veri tabanında bulunamadı!', 'gnn-smtpmail' ), esc_html( $table ) );
                    }
                    echo '</p></div>';
                }
            }
            ?>
            <?php settings_errors( 'gnn-smtpmail' ); ?>

            <div class="gnn-card">
                <div class="gnn-card-header">
                    <h2><?php esc_html_e( 'Log Filtreleme ve İşlemler', 'gnn-smtpmail' ); ?></h2>
                </div>
                <div class="gnn-card-body-padded">
                    <div class="gnn-logs-toolbar">
                        <form method="get" class="gnn-inline">
                            <input type="hidden" name="page" value="gnn-smtpmail-logs"/>
                            <label for="gnn_logs_status"><?php esc_html_e('Durum:', 'gnn-smtpmail'); ?></label>
                            <select id="gnn_logs_status" name="status">
                                <option value=""><?php esc_html_e('Hepsi', 'gnn-smtpmail'); ?></option>
                                <option value="success" <?php selected($status,'success'); ?>><?php esc_html_e('Başarılı', 'gnn-smtpmail'); ?></option>
                                <option value="error" <?php selected($status,'error'); ?>><?php esc_html_e('Hata', 'gnn-smtpmail'); ?></option>
                            </select>
                            <button type="submit" class="button"><?php esc_html_e('Filtrele', 'gnn-smtpmail'); ?></button>
                        </form>

                        <div class="gnn-inline">
                            <form method="post" class="gnn-inline">
                                <?php wp_nonce_field( 'gnn_export_logs_action', 'gnn_export_logs_nonce' ); ?>
                                <input type="hidden" name="export_status" value="<?php echo esc_attr( $status ); ?>" />
                                <button type="submit" name="gnn_export_logs" value="csv" class="button"><?php esc_html_e( 'CSV İndir', 'gnn-smtpmail' ); ?></button>
                                <button type="submit" name="gnn_export_logs" value="json" class="button"><?php esc_html_e( 'JSON İndir', 'gnn-smtpmail' ); ?></button>
                            </form>

                            <form method="post" class="gnn-confirm-clear" data-confirm="<?php echo esc_attr__( 'Tüm e-posta loglarını kalıcı olarak silmek istediğinize emin misiniz?', 'gnn-smtpmail' ); ?>">
                                <?php wp_nonce_field( 'gnn_clear_logs_action', 'gnn_clear_logs_nonce' ); ?>
                                <button type="submit" name="gnn_clear_logs" class="button"><?php esc_html_e('Tüm Logları Temizle', 'gnn-smtpmail'); ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gnn-card">
                <div class="gnn-card-header">
                    <h2><?php esc_html_e( 'Gönderim Geçmişi', 'gnn-smtpmail' ); ?></h2>
                    <span class="gnn-badge"><?php echo sprintf( esc_html__( 'Toplam: %d', 'gnn-smtpmail' ), intval( $total ) ); ?></span>
                </div>
                <div class="gnn-table-responsive">
                <table class="widefat striped gnn-card-table">
                    <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e('Tarih', 'gnn-smtpmail'); ?></th>
                        <th scope="col"><?php esc_html_e('Kanal', 'gnn-smtpmail'); ?></th>
                        <th scope="col"><?php esc_html_e('Alıcı', 'gnn-smtpmail'); ?></th>
                        <th scope="col"><?php esc_html_e('Konu', 'gnn-smtpmail'); ?></th>
                        <th scope="col"><?php esc_html_e('Durum', 'gnn-smtpmail'); ?></th>
                        <th scope="col"><?php esc_html_e('Mesaj', 'gnn-smtpmail'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ( $rows ) : foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row->logged_at ); ?></td>
                            <td><?php echo esc_html( strtoupper( $row->channel ) ); ?></td>
                            <td><?php echo esc_html( $row->recipient ); ?></td>
                            <td><?php echo esc_html( $row->subject ); ?></td>
                            <td><?php echo $row->status === 'success' ? '<span class="gnn-badge success">'.esc_html__('Başarılı','gnn-smtpmail').'</span>' : '<span class="gnn-badge error">'.esc_html__('Hata','gnn-smtpmail').'</span>'; ?></td>
                            <td><?php echo esc_html( $row->message ); ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="6"><?php esc_html_e('Log bulunamadı.', 'gnn-smtpmail'); ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="gnn-card-footer">
                        <div class="tablenav">
                            <div class="tablenav-pages">
                                <?php
                                $page_args = array( 'paged' => '%#%' );
                                if ( '' !== $status ) {
                                    $page_args['status'] = $status;
                                }
                                echo wp_kses_post(
                                    paginate_links( array(
                                        'base'      => add_query_arg( $page_args ),
                                        'format'    => '',
                                        'prev_text' => esc_html__( '« Önceki', 'gnn-smtpmail' ),
                                        'next_text' => esc_html__( 'Sonraki »', 'gnn-smtpmail' ),
                                        'total'     => $total_pages,
                                        'current'   => $paged,
                                    ) )
                                );
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Display a prominent admin notice if wp_mail is hijacked by another plugin.
     */
    public function check_mail_conflict_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $file = $this->get_wp_mail_source();
        if ( empty( $file ) ) { return; }
            
        if ( strpos( $file, 'wp-includes/pluggable.php' ) === false ) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . esc_html__( 'GNN SMTPMail Çakışma Uyarısı:', 'gnn-smtpmail' ) . '</strong><br>';
            echo sprintf(
                esc_html__( '`wp_mail()` fonksiyonu başka bir eklenti veya dosya tarafından tanımlanmış: %s. Bu durum, e-postalarınızın GNN SMTPMail (Brevo veya Custom SMTP) üzerinden gitmesini ve loglanmasını engellemektedir. Lütfen çakışan e-posta/SMTP eklentilerini devre dışı bırakın.', 'gnn-smtpmail' ),
                '<code>' . esc_html( $file ) . '</code>'
            );
            echo '</p></div>';
        }
    }

    /**
     * Get the file where wp_mail() is defined (relative to ABSPATH).
     *
     * @return string Relative file path or empty string on failure.
     */
    private function get_wp_mail_source() {
        try {
            $reflector = new ReflectionFunction( 'wp_mail' );
            $file = wp_normalize_path( $reflector->getFileName() );
            return str_replace( wp_normalize_path( ABSPATH ), '', $file );
        } catch ( Exception $e ) {
            return '';
        }
    }
}