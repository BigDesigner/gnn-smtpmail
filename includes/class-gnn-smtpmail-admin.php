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
        register_setting('gnn_smtp_settings_group', self::OPTION_NAME);
    }

    /**
     * Add menu pages.
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'GNN SMTPMail',
            'GNN SMTPMail',
            'manage_options',
            'gnn-smtpmail',
            array($this, 'render_settings_page'),
            'dashicons-email-alt'
        );

        add_submenu_page(
            'gnn-smtpmail',
            'Settings',
            'Settings',
            'manage_options',
            'gnn-smtpmail',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'gnn-smtpmail',
            'Test Mail',
            'Test Mail',
            'manage_options',
            'gnn-smtpmail-test',
            array($this, 'render_test_page')
        );

        add_submenu_page(
            'gnn-smtpmail',
            'Logs',
            'Logs',
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

        // Handle Save
        if (isset($_POST['gnn_smtp_save_settings'])) {
            check_admin_referer(self::NONCE_ACTION);
            $this->save_settings();
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
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
            <h1>GNN SMTPMail Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Mailer Type</th>
                        <td>
                            <select name="gnn_smtp[mailer]">
                                <option value="smtp" <?php selected($mailer, 'smtp'); ?>>SMTP</option>
                                <option value="mail" <?php selected($mailer, 'mail'); ?>>Default (PHP mail)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">From Email</th>
                        <td><input type="email" name="gnn_smtp[from_email]" value="<?php echo esc_attr($from_email); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">From Name</th>
                        <td><input type="text" name="gnn_smtp[from_name]" value="<?php echo esc_attr($from_name); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Host</th>
                        <td><input type="text" name="gnn_smtp[smtp_host]" value="<?php echo esc_attr($smtp_host); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Port</th>
                        <td><input type="number" name="gnn_smtp[smtp_port]" value="<?php echo esc_attr($smtp_port); ?>"
                                class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Encryption</th>
                        <td>
                            <select name="gnn_smtp[smtp_secure]">
                                <option value="" <?php selected($smtp_secure, ''); ?>>None</option>
                                <option value="ssl" <?php selected($smtp_secure, 'ssl'); ?>>SSL</option>
                                <option value="tls" <?php selected($smtp_secure, 'tls'); ?>>TLS</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Authentication</th>
                        <td>
                            <label><input type="checkbox" name="gnn_smtp[smtp_auth]" value="1" <?php checked($smtp_auth); ?>>
                                Enable SMTP Authentication</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Username</th>
                        <td><input type="text" name="gnn_smtp[smtp_user]" value="<?php echo esc_attr($smtp_user); ?>"
                                class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Password</th>
                        <td>
                            <input type="password" name="gnn_smtp[smtp_pass]" value="" class="regular-text"
                                placeholder="Leave empty to keep existing password">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Timeout (s)</th>
                        <td><input type="number" name="gnn_smtp[timeout]" value="<?php echo esc_attr($timeout); ?>"
                                class="small-text"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="gnn_smtp_save_settings" id="submit" class="button button-primary"
                        value="Save Changes">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Save settings helper.
     */
    private function save_settings()
    {
        $input = isset($_POST['gnn_smtp']) ? $_POST['gnn_smtp'] : array();
        $existing = get_option(self::OPTION_NAME, array());

        $clean = array();
        $clean['mailer'] = sanitize_text_field($input['mailer']);
        $clean['from_email'] = sanitize_email($input['from_email']);
        $clean['from_name'] = sanitize_text_field($input['from_name']);
        $clean['smtp_host'] = sanitize_text_field($input['smtp_host']);
        $clean['smtp_port'] = (int) $input['smtp_port'];
        $clean['smtp_secure'] = sanitize_text_field($input['smtp_secure']);
        $clean['smtp_auth'] = isset($input['smtp_auth']) ? true : false;
        $clean['smtp_user'] = sanitize_text_field($input['smtp_user']);
        $clean['timeout'] = (int) $input['timeout'];

        // Password handling
        if (!empty($input['smtp_pass'])) {
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

            $to = sanitize_email($_POST['test_email']);

            if (is_email($to)) {
                $subject = 'GNN SMTPMail Test';
                $message = '<h1>It Works!</h1><p>This is a test email sent from GNN SMTPMail plugin.</p><p>Time: ' . current_time('mysql') . '</p>';
                $headers = array('Content-Type: text/html; charset=UTF-8');

                // Result handled by logging hooks in GNN_SMTPMail, but we capture result for UI
                $sent = wp_mail($to, $subject, $message, $headers);

                if ($sent) {
                    $result_msg = '<div class="notice notice-success is-dismissible"><p>Test email sent successfully to ' . esc_html($to) . '.</p></div>';
                } else {
                    $result_msg = '<div class="notice notice-error is-dismissible"><p>Failed to send test email. Check the logs for details.</p></div>';
                }
            } else {
                $result_msg = '<div class="notice notice-warning is-dismissible"><p>Invalid email address.</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>Send Test Email</h1>
            <?php echo $result_msg; ?>
            <form method="post" action="">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Recipient Email</th>
                        <td>
                            <input type="email" name="test_email"
                                value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" class="regular-text"
                                required>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="gnn_smtp_send_test" class="button button-primary" value="Send Test Email">
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
                echo '<div class="notice notice-success is-dismissible"><p>Logs cleared.</p></div>';
            }
        }

        // Pagination & Filter
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $per_page = 20;

        $logs = array('rows' => array(), 'total' => 0, 'pages' => 0);
        if (class_exists('GNN_SMTP_Logger')) {
            $logs = GNN_SMTP_Logger::get_logs($paged, $per_page, $status);
        }

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Email Logs</h1>
            <form method="post" action="" style="display:inline-block; float:right;">
                <?php wp_nonce_field(self::NONCE_ACTION); ?>
                <input type="submit" name="gnn_smtp_clear_logs" class="button button-secondary" value="Clear All Logs"
                    onclick="return confirm('Are you sure?');">
            </form>
            <hr class="wp-header-end">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="gnn-smtpmail-logs">
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="sent" <?php selected($status, 'sent'); ?>>Sent</option>
                            <option value="failed" <?php selected($status, 'failed'); ?>>Failed</option>
                        </select>
                        <input type="submit" class="button" value="Filter">
                    </form>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo esc_html($logs['total']); ?> items
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
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Subject</th>
                        <th>To</th>
                        <th>Message</th>
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
                                        <span style="color:green; font-weight:bold;">Sent</span>
                                    <?php else: ?>
                                        <span style="color:red; font-weight:bold;">Failed</span>
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
                            <td colspan="5">No logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
