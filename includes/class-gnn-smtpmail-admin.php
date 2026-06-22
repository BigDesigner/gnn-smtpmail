<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GNN_SMTPMail_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_save_forms' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
    }

    public function assets( $hook ) {
        if ( strpos( $hook, 'gnn-smtpmail' ) === false ) return;
        wp_enqueue_style( 'gnn-smtpmail-admin', GNN_SMTPMAIL_URL . 'assets/admin.css', array(), GNN_SMTPMAIL_VERSION );
        wp_enqueue_script( 'gnn-smtpmail-admin', GNN_SMTPMAIL_URL . 'assets/admin.js', array('jquery'), GNN_SMTPMAIL_VERSION, true );
    }

    public function admin_menu() {
        add_menu_page(
            __( 'GNN SMTPMail', 'gnn-smtpmail' ),
            __( 'GNN SMTPMail', 'gnn-smtpmail' ),
            'manage_options',
            'gnn-smtpmail',
            array( $this, 'page_welcome' ),
            'dashicons-email-alt2',
            81
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

            $custom = array(
                'enabled'     => isset($_POST['enabled']) ? 1 : 0,
                'host'        => isset($_POST['host']) ? sanitize_text_field( wp_unslash($_POST['host']) ) : '',
                'port'        => isset($_POST['port']) ? intval($_POST['port']) : 587,
                'smtp_secure' => isset($_POST['smtp_secure']) && in_array($_POST['smtp_secure'], array('ssl','tls','none'), true) ? sanitize_text_field($_POST['smtp_secure']) : 'tls',
                'auth'        => isset($_POST['auth']) ? 1 : 0,
                'username'    => isset($_POST['username']) ? sanitize_text_field( wp_unslash($_POST['username']) ) : '',
                'password'    => isset($_POST['password']) ? sanitize_text_field( wp_unslash($_POST['password']) ) : '',
                'from_email'  => isset($_POST['from_email']) ? sanitize_email( wp_unslash($_POST['from_email']) ) : '',
                'from_name'   => isset($_POST['from_name']) ? sanitize_text_field( wp_unslash($_POST['from_name']) ) : '',
            );

            $brevo = array(
                'api_key'    => isset($_POST['brevo_api_key']) ? sanitize_text_field( wp_unslash($_POST['brevo_api_key']) ) : '',
                'from_email' => isset($_POST['brevo_from_email']) ? sanitize_email( wp_unslash($_POST['brevo_from_email']) ) : '',
                'from_name'  => isset($_POST['brevo_from_name']) ? sanitize_text_field( wp_unslash($_POST['brevo_from_name']) ) : '',
            );

            $settings = array(
                'mailer_type' => $mailer_type,
                'custom'      => $custom,
                'brevo'       => $brevo,
            );
            update_option( GNN_SMTPMAIL_OPTION, $settings );
            add_settings_error( 'gnn-smtpmail', 'saved', __( 'Ayarlar kaydedildi.', 'gnn-smtpmail' ), 'updated' );
        }

        // Send test mail
        if ( isset( $_POST['gnn_test_send'] ) ) {
            check_admin_referer( 'gnn_test_send_action', 'gnn_test_nonce' );

            $to      = isset($_POST['to'])      ? sanitize_email( wp_unslash($_POST['to']) ) : '';
            $subject = isset($_POST['subject']) ? sanitize_text_field( wp_unslash($_POST['subject']) ) : 'GNN SMTPMail Test';
            $message = isset($_POST['message']) ? wp_kses_post( wp_unslash($_POST['message']) ) : __( 'Bu bir test e-postasıdır.', 'gnn-smtpmail' );

            if ( ! is_email( $to ) ) {
                add_settings_error( 'gnn-smtpmail', 'invalid_email', __( 'Geçerli bir e-posta adresi girin.', 'gnn-smtpmail' ), 'error' );
                return;
            }

            add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );
            $sent = wp_mail( $to, $subject, wpautop( $message ) );
            remove_filter( 'wp_mail_content_type', '__return_false' );

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
    }

    public function page_welcome() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('GNN SMTPMail (Custom SMTP & Brevo API)', 'gnn-smtpmail'); ?></h1>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <p><?php esc_html_e('Custom SMTP veya Brevo API ile tüm WordPress e-postalarınızı güvenli şekilde gönderin. Soldaki menüden ayarlarınızı yapın, ardından Test Mail sayfasından doğrulayın ve Loglar sayfasından durumu takip edin.', 'gnn-smtpmail'); ?></p>
            <div class="gnn-grid">
                <a class="button button-primary" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-custom') ); ?>"><?php esc_html_e('Ayarlar', 'gnn-smtpmail'); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-test') ); ?>"><?php esc_html_e('Test Mail', 'gnn-smtpmail'); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url('admin.php?page=gnn-smtpmail-logs') ); ?>"><?php esc_html_e('Loglar', 'gnn-smtpmail'); ?></a>
            </div>
        </div>
        <?php
    }

    public function page_custom() {
        $s = $this->settings();
        $mailer_type = isset($s['mailer_type']) ? $s['mailer_type'] : 'custom';
        $c = isset($s['custom']) ? $s['custom'] : array();
        $b = isset($s['brevo']) ? $s['brevo'] : array();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('E-posta Gönderim Ayarları', 'gnn-smtpmail'); ?></h1>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <form method="post">
                <?php wp_nonce_field( 'gnn_custom_save_action', 'gnn_custom_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Gönderim Yöntemi', 'gnn-smtpmail'); ?></th>
                        <td>
                            <select name="mailer_type" id="gnn_mailer_type">
                                <option value="custom" <?php selected($mailer_type, 'custom'); ?>><?php esc_html_e('Custom SMTP', 'gnn-smtpmail'); ?></option>
                                <option value="brevo" <?php selected($mailer_type, 'brevo'); ?>><?php esc_html_e('Brevo API', 'gnn-smtpmail'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div id="gnn_section_custom" class="gnn-settings-section" style="<?php echo $mailer_type === 'custom' ? '' : 'display:none;'; ?>">
                    <h3><?php esc_html_e('Custom SMTP Ayarları', 'gnn-smtpmail'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><?php esc_html_e('Custom SMTP\'yi Etkinleştir', 'gnn-smtpmail'); ?></th>
                            <td><label><input type="checkbox" name="enabled" value="1" <?php checked( !empty($c['enabled']) ); ?> /> <?php esc_html_e('Etkin', 'gnn-smtpmail'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Sunucu', 'gnn-smtpmail'); ?></th>
                            <td><input type="text" name="host" value="<?php echo esc_attr( $c['host'] ?? '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Port', 'gnn-smtpmail'); ?></th>
                            <td><input type="number" name="port" value="<?php echo esc_attr( $c['port'] ?? 587 ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Şifreleme', 'gnn-smtpmail'); ?></th>
                            <td>
                                <select name="smtp_secure">
                                    <?php $sel = $c['smtp_secure'] ?? 'tls'; ?>
                                    <option value="none" <?php selected($sel,'none'); ?>><?php esc_html_e('Yok', 'gnn-smtpmail'); ?></option>
                                    <option value="tls" <?php selected($sel,'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected($sel,'ssl'); ?>>SSL</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('SMTP Kimlik Doğrulama', 'gnn-smtpmail'); ?></th>
                            <td><label><input type="checkbox" name="auth" value="1" <?php checked( !empty($c['auth']) ); ?> /> <?php esc_html_e('Açık', 'gnn-smtpmail'); ?></label></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Kullanıcı Adı', 'gnn-smtpmail'); ?></th>
                            <td><input type="text" name="username" value="<?php echo esc_attr( $c['username'] ?? '' ); ?>" class="regular-text" autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Şifre', 'gnn-smtpmail'); ?></th>
                            <td><input type="password" name="password" value="<?php echo esc_attr( $c['password'] ?? '' ); ?>" class="regular-text" autocomplete="new-password" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Gönderen E-posta', 'gnn-smtpmail'); ?></th>
                            <td><input type="email" name="from_email" value="<?php echo esc_attr( $c['from_email'] ?? '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Gönderen Adı', 'gnn-smtpmail'); ?></th>
                            <td><input type="text" name="from_name" value="<?php echo esc_attr( $c['from_name'] ?? '' ); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <div id="gnn_section_brevo" class="gnn-settings-section" style="<?php echo $mailer_type === 'brevo' ? '' : 'display:none;'; ?>">
                    <h3><?php esc_html_e('Brevo API Ayarları', 'gnn-smtpmail'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th><?php esc_html_e('Brevo API Key', 'gnn-smtpmail'); ?></th>
                            <td><input type="password" name="brevo_api_key" value="<?php echo esc_attr( $b['api_key'] ?? '' ); ?>" class="regular-text" autocomplete="new-password" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Gönderen E-posta', 'gnn-smtpmail'); ?></th>
                            <td><input type="email" name="brevo_from_email" value="<?php echo esc_attr( $b['from_email'] ?? '' ); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Gönderen Adı', 'gnn-smtpmail'); ?></th>
                            <td><input type="text" name="brevo_from_name" value="<?php echo esc_attr( $b['from_name'] ?? '' ); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                </div>

                <p><button type="submit" name="gnn_custom_save" class="button button-primary"><?php esc_html_e('Kaydet', 'gnn-smtpmail'); ?></button></p>
            </form>
        </div>
        <?php
    }

    public function page_test() {
        $s = $this->settings();
        $mailer_type = isset($s['mailer_type']) ? $s['mailer_type'] : 'custom';
        $active = false;
        
        if ( $mailer_type === 'brevo' ) {
            $active = ! empty( $s['brevo']['api_key'] );
            $channel_name = 'BREVO API';
        } else {
            $active = ! empty( $s['custom']['enabled'] );
            $channel_name = 'CUSTOM SMTP';
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Test Mail Gönder', 'gnn-smtpmail'); ?></h1>
            <?php settings_errors( 'gnn-smtpmail' ); ?>
            <p>
            <?php if ( ! $active ) : ?>
                <span class="notice notice-warning inline"><p><?php echo sprintf( esc_html__('%s şu an devre dışı veya yapılandırılmamış.', 'gnn-smtpmail'), $channel_name ); ?></p></span>
            <?php else : ?>
                <strong><?php echo sprintf( esc_html__('Aktif kanal: %s', 'gnn-smtpmail'), $channel_name ); ?></strong>
            <?php endif; ?>
            </p>
            <form method="post">
                <?php wp_nonce_field( 'gnn_test_send_action', 'gnn_test_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><?php esc_html_e('Alıcı (To)', 'gnn-smtpmail'); ?></th>
                        <td><input type="email" name="to" required class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Konu', 'gnn-smtpmail'); ?></th>
                        <td><input type="text" name="subject" class="regular-text" value="<?php esc_attr_e('GNN SMTPMail Test', 'gnn-smtpmail'); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Mesaj', 'gnn-smtpmail'); ?></th>
                        <td><textarea name="message" rows="6" class="large-text"><?php esc_html_e('Bu bir test e-postasıdır. GNN SMTPMail üzerinden gönderilmiştir.', 'gnn-smtpmail'); ?></textarea></td>
                    </tr>
                </table>
                <p><button type="submit" name="gnn_test_send" class="button button-primary"><?php esc_html_e('Gönder', 'gnn-smtpmail'); ?></button></p>
            </form>
            <hr/>
            <p class="description"><?php esc_html_e('Gönderim sonucu "Loglar" sayfasına kaydedilir. Başarısızsa hata mesajı loglarda görünecektir.', 'gnn-smtpmail'); ?></p>
        </div>
        <?php
    }

    public function page_logs() {
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $per_page = 20;
        $data = GNN_SMTPMail_Logger::get_logs( $paged, $per_page, $status );
        $rows = $data['rows'];
        $total = $data['total'];
        $total_pages = max(1, ceil( $total / $per_page ));

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('E-posta Logları', 'gnn-smtpmail'); ?></h1>
            <?php settings_errors( 'gnn-smtpmail' ); ?>

            <form method="get" class="gnn-inline">
                <input type="hidden" name="page" value="gnn-smtpmail-logs"/>
                <label><?php esc_html_e('Durum:', 'gnn-smtpmail'); ?>
                    <select name="status">
                        <option value=""><?php esc_html_e('Hepsi', 'gnn-smtpmail'); ?></option>
                        <option value="success" <?php selected($status,'success'); ?>><?php esc_html_e('Başarılı', 'gnn-smtpmail'); ?></option>
                        <option value="error" <?php selected($status,'error'); ?>><?php esc_html_e('Hata', 'gnn-smtpmail'); ?></option>
                    </select>
                </label>
                <button class="button"><?php esc_html_e('Filtrele', 'gnn-smtpmail'); ?></button>
            </form>

            <form method="post" style="margin-top:10px;">
                <?php wp_nonce_field( 'gnn_clear_logs_action', 'gnn_clear_logs_nonce' ); ?>
                <button type="submit" name="gnn_clear_logs" class="button"><?php esc_html_e('Tüm Logları Temizle', 'gnn-smtpmail'); ?></button>
            </form>

            <table class="widefat striped" style="margin-top:15px;">
                <thead>
                <tr>
                    <th><?php esc_html_e('Tarih', 'gnn-smtpmail'); ?></th>
                    <th><?php esc_html_e('Kanal', 'gnn-smtpmail'); ?></th>
                    <th><?php esc_html_e('Alıcı', 'gnn-smtpmail'); ?></th>
                    <th><?php esc_html_e('Konu', 'gnn-smtpmail'); ?></th>
                    <th><?php esc_html_e('Durum', 'gnn-smtpmail'); ?></th>
                    <th><?php esc_html_e('Mesaj', 'gnn-smtpmail'); ?></th>
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

            <?php if ( $total_pages > 1 ) : ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( array( 'paged' => '%#%', 'status' => $status ) ),
                            'format'    => '',
                            'prev_text' => __('« Önceki'),
                            'next_text' => __('Sonraki »'),
                            'total'     => $total_pages,
                            'current'   => $paged
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}