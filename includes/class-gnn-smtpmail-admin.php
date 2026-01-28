<?php
if (!defined('ABSPATH')) {
    exit;
}

class GNN_SMTPMail_Admin
{

    /**
     * Option name.
     */
    const OPTION_NAME = 'gnn_smtp_options';

    /**
     * Nonce action.
     */
    const NONCE_ACTION = 'gnn_smtp_admin_action';

    /**
     * Initialize admin hooks.
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings (not using Settings API for full custom control in form handling if preferred, 
     * but user asked for "Settings page: save enabled..."). 
     * We will handle saving manually in the render method or admin_post to satisfy specific logic like password preservation.
     */
    public function register_settings()
    {
        register_setting('gnn_smtp_settings_group', self::OPTION_NAME, array('sanitize_callback' => 'sanitize_text_field'));
    }

    /**
     * Add menu pages.
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('GNN SMTPMail', 'gnn-smtpmail'),
            __('GNN SMTPMail', 'gnn-smtpmail'),
            'manage_options',
            'gnn-smtpmail',
            array($this, 'render_settings_page'),
            'dashicons-email-alt'
        );

        add_submenu_page(
            'gnn-smtpmail',
            __('Settings', 'gnn-smtpmail'),
            __('Settings', 'gnn-smtpmail'),
            'manage_options',
            'gnn-smtpmail',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'gnn-smtpmail',
            __('Test Mail', 'gnn-smtpmail'),
            __('Test Mail', 'gnn-smtpmail'),
            'manage_options',
            'gnn-smtpmail-test',
            array($this, 'render_test_page')
        );

        add_submenu_page(
            'gnn-smtpmail',
            __('Logs', 'gnn-smtpmail'),
            __('Logs', 'gnn-smtpmail'),
            'manage_options',
            'gnn-smtpmail-logs',
            array($this, 'render_logs_page')
        );
    }

    /**
     * Render Settings Page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle Save (security checks are in save_settings())
        if (isset($_POST['gnn_smtp_save_settings'])) {
            $this->save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'gnn-smtpmail') . '</p></div>';
        }

        $options = get_option(self::OPTION_NAME, array());

        // Defaults
        $mailer = isset($options['mailer']) ? $options['mailer'] : 'smtp';
        $smtp_host = isset($options['smtp_host']) ? $options['smtp_host'] : '';
        $smtp_port = isset($options['smtp_port']) ? $options['smtp_port'] : '587';
        $smtp_secure = isset($options['smtp_secure']) ? $options['smtp_secure'] : 'tls';
        $smtp_auth = isset($options['smtp_auth']) && $options['smtp_auth'];
        $smtp_user = isset($options['smtp_user']) ? $options['smtp_user'] : '';
        $from_email = isset($options['from_email']) ? $options['from_email'] : '';
        $from_name = isset($options['from_name']) ? $options['from_name'] : '';
        $timeout = isset($options['timeout']) ? $options['timeout'] : 10;

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('GNN SMTPMail Settings', 'gnn-smtpmail'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Mailer Type', 'gnn-smtpmail'); ?></th>
                        <td>
                            <select name="gnn_smtp[mailer]">
                                <option value="smtp" <?php selected($mailer, 'smtp'); ?>>
                                    <?php esc_html_e('SMTP', 'gnn-smtpmail'); ?>
                                </option>
                                <option value="mail" <?php selected($mailer, 'mail'); ?>>
                                    <?php esc_html_e('Default (PHP mail)', 'gnn-smtpmail'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('From Email', 'gnn-smtpmail'); ?></th>
                        <td><input type="email" name="gnn_smtp[from_email]" value="<?php echo esc_attr($from_email); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('From Name', 'gnn-smtpmail'); ?></th>
                        <td><input type="text" name="gnn_smtp[from_name]" value="<?php echo esc_attr($from_name); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('SMTP Host', 'gnn-smtpmail'); ?></th>
                        <td><input type="text" name="gnn_smtp[smtp_host]" value="<?php echo esc_attr($smtp_host); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('SMTP Port', 'gnn-smtpmail'); ?></th>
                        <td><input type="number" name="gnn_smtp[smtp_port]" value="<?php echo esc_attr($smtp_port); ?>"
                                class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Encryption', 'gnn-smtpmail'); ?></th>
                        <td>
                            <select name="gnn_smtp[smtp_secure]">
                                <option value="" <?php selected($smtp_secure, ''); ?>>
                                    <?php esc_html_e('None', 'gnn-smtpmail'); ?>
                                </option>
                                <option value="ssl" <?php selected($smtp_secure, 'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected($smtp_secure, 'tls'); ?>>TLS</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Authentication', 'gnn-smtpmail'); ?></th>
                        <td>
                            <label><input type="checkbox" name="gnn_smtp[smtp_auth]" value="1" <?php checked($smtp_auth); ?>>
                                <?php esc_html_e('Enable SMTP Authentication', 'gnn-smtpmail'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('SMTP Username', 'gnn-smtpmail'); ?></th>
                        <td><input type="text" name="gnn_smtp[smtp_user]" value="<?php echo esc_attr($smtp_user); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('SMTP Password', 'gnn-smtpmail'); ?></th>
                        <td>
                            <input type="password" name="gnn_smtp[smtp_pass]" value="" class="regular-text"
                                placeholder="<?php esc_attr_e('Leave empty to keep existing password', 'gnn-smtpmail'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Timeout (s)', 'gnn-smtpmail'); ?></th>
                        <td><input type="number" name="gnn_smtp[timeout]" value="<?php echo esc_attr($timeout); ?>"
                                class="small-text"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="gnn_smtp_save_settings" id="submit" class="button button-primary"
                        value="<?php esc_attr_e('Save Changes', 'gnn-smtpmail'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings helper with security checks.
     */
    private function save_settings()
    {
        // Security checks MUST be in the same function where $_POST is read
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gnn-smtpmail'));
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::NONCE_ACTION)) {
            wp_die(esc_html__('Security check failed.', 'gnn-smtpmail'));
        }

        // Now safe to read $_POST
        $input = isset($_POST['gnn_smtp']) && is_array($_POST['gnn_smtp']) ? wp_unslash($_POST['gnn_smtp']) : array();
        $existing = get_option(self::OPTION_NAME, array());

        $clean = array();

        // Mailer type
        $clean['mailer'] = isset($input['mailer']) ? sanitize_text_field($input['mailer']) : 'smtp';

        // Email settings
        $clean['from_email'] = isset($input['from_email']) ? sanitize_email($input['from_email']) : '';
        $clean['from_name'] = isset($input['from_name']) ? sanitize_text_field($input['from_name']) : '';

        // SMTP settings
        $clean['smtp_host'] = isset($input['smtp_host']) ? sanitize_text_field($input['smtp_host']) : '';
        $clean['smtp_port'] = isset($input['smtp_port']) ? absint($input['smtp_port']) : 587;

        // Validate smtp_secure with allowlist
        $allowed_secure = array('', 'ssl', 'tls');
        $smtp_secure = isset($input['smtp_secure']) ? sanitize_text_field($input['smtp_secure']) : 'tls';
        $clean['smtp_secure'] = in_array($smtp_secure, $allowed_secure, true) ? $smtp_secure : 'tls';

        // Boolean for auth
        $clean['smtp_auth'] = !empty($input['smtp_auth']);

        $clean['smtp_user'] = isset($input['smtp_user']) ? sanitize_text_field($input['smtp_user']) : '';
        $clean['timeout'] = isset($input['timeout']) ? absint($input['timeout']) : 10;

        // Password handling - only update if new password provided
        if (isset($input['smtp_pass']) && !empty($input['smtp_pass'])) {
            $clean['smtp_pass'] = sanitize_text_field($input['smtp_pass']);
        } else {
            $clean['smtp_pass'] = isset($existing['smtp_pass']) ? $existing['smtp_pass'] : '';
        }

        update_option(self::OPTION_NAME, $clean);
    }

    /**
     * Render Test Mail Page.
     */
    public function render_test_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $result_msg = '';

        if (isset($_POST['gnn_smtp_send_test'])) {
            check_admin_referer(self::NONCE_ACTION);

            $to = isset($_POST['test_email']) ? sanitize_email(wp_unslash($_POST['test_email'])) : '';

            if (is_email($to)) {
                $subject = __('GNN SMTPMail Test', 'gnn-smtpmail');
                // translators: %s: Current time.
                $message = '<h1>' . esc_html__('It Works!', 'gnn-smtpmail') . '</h1><p>' . esc_html__('This is a test email sent from GNN SMTPMail plugin.', 'gnn-smtpmail') . '</p><p>' . sprintf(esc_html__('Time: %s', 'gnn-smtpmail'), current_time('mysql')) . '</p>';
                $headers = array('Content-Type: text/html; charset=UTF-8');

                // Result handled by logging hooks in GNN_SMTPMail, but we capture result for UI
                $sent = wp_mail($to, $subject, $message, $headers);

                if ($sent) {
                    // translators: %s: Recipient email address.
                    $result_msg = '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('Test email sent successfully to %s.', 'gnn-smtpmail'), esc_html($to)) . '</p></div>';
                } else {
                    $result_msg = '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to send test email. Check the logs for details.', 'gnn-smtpmail') . '</p></div>';
                }
            } else {
                $result_msg = '<div class="notice notice-warning is-dismissible"><p>' . esc_html__('Invalid email address.', 'gnn-smtpmail') . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Send Test Email', 'gnn-smtpmail'); ?></h1>
            <?php echo wp_kses_post($result_msg); ?>
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Recipient Email', 'gnn-smtpmail'); ?></th>
                        <td>
                            <input type="email" name="test_email"
                                value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text"
                                required>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="gnn_smtp_send_test" class="button button-primary"
                        value="<?php esc_attr_e('Send Test Email', 'gnn-smtpmail'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render Logs Page.
     */
    public function render_logs_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle Clear
        if (isset($_POST['gnn_smtp_clear_logs'])) {
            check_admin_referer(self::NONCE_ACTION);
            if (class_exists('GNN_SMTP_Logger')) {
                GNN_SMTP_Logger::clear_all();
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Logs cleared.', 'gnn-smtpmail') . '</p></div>';
            }
        }

        // Pagination & Filter
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $per_page = 20;

        $logs = array('rows' => array(), 'total' => 0, 'pages' => 0);
        if (class_exists('GNN_SMTP_Logger')) {
            $logs = GNN_SMTP_Logger::get_logs($paged, $per_page, $status);
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Email Logs', 'gnn-smtpmail'); ?></h1>
            <form method="post" action="" style="display:inline-block; float:right;">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="submit" name="gnn_smtp_clear_logs" class="button button-secondary"
                    value="<?php esc_attr_e('Clear All Logs', 'gnn-smtpmail'); ?>"
                    onclick="return confirm('<?php esc_attr_e('Are you sure?', 'gnn-smtpmail'); ?>');">
            </form>
            <hr class="wp-header-end">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="gnn-smtpmail-logs">
                        <select name="status">
                            <option value=""><?php esc_html_e('All Statuses', 'gnn-smtpmail'); ?></option>
                            <option value="sent" <?php selected($status, 'sent'); ?>>
                                <?php esc_html_e('Sent', 'gnn-smtpmail'); ?>
                            </option>
                            <option value="failed" <?php selected($status, 'failed'); ?>>
                                <?php esc_html_e('Failed', 'gnn-smtpmail'); ?>
                            </option>
                        </select>
                        <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'gnn-smtpmail'); ?>">
                    </form>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php
                        // translators: %s: Total number of items.
                        echo sprintf(esc_html__('%s items', 'gnn-smtpmail'), esc_html($logs['total']));
                        ?>
                    </span>
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $logs['pages'],
                        'current' => $paged
                    ));
                    if ($page_links) {
                        echo '<span class="pagination-links">' . wp_kses_post($page_links) . '</span>';
                    }
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'gnn-smtpmail'); ?></th>
                        <th><?php esc_html_e('Status', 'gnn-smtpmail'); ?></th>
                        <th><?php esc_html_e('Subject', 'gnn-smtpmail'); ?></th>
                        <th><?php esc_html_e('To', 'gnn-smtpmail'); ?></th>
                        <th><?php esc_html_e('Message', 'gnn-smtpmail'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs['rows'])): ?>
                        <?php foreach ($logs['rows'] as $row): ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($row->created_at); ?>
                                </td>
                                <td>
                                    <?php if ('sent' === $row->status): ?>
                                        <span style="color:green; font-weight:bold;"><?php esc_html_e('Sent', 'gnn-smtpmail'); ?></span>
                                    <?php else: ?>
                                        <span style="color:red; font-weight:bold;"><?php esc_html_e('Failed', 'gnn-smtpmail'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo esc_html($row->subject); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($row->recipient); ?>
                                </td>
                                <td>
                                    <?php echo esc_html(mb_strimwidth($row->message, 0, 100, '...')); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No logs found.', 'gnn-smtpmail'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
