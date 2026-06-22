<?php
/**
 * Plugin Name: GNN SMTPMail
 * Description: Custom SMTP ile WordPress e-posta gönderimini güvenli şekilde yapılandırın. Test e-postası gönderin, hata/success loglarını görüntüleyin ve kaldırırken tüm verileri temizleyin.
 * Version: 1.3.2
 * Author: GNN
 * Text Domain: gnn-smtpmail
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'GNN_SMTPMAIL_VERSION', '1.3.2' );
define( 'GNN_SMTPMAIL_FILE', __FILE__ );
define( 'GNN_SMTPMAIL_DIR', plugin_dir_path( __FILE__ ) );
define( 'GNN_SMTPMAIL_URL', plugin_dir_url( __FILE__ ) );
define( 'GNN_SMTPMAIL_OPTION', 'gnn_smtpmail_settings' );
define( 'GNN_SMTPMAIL_TABLE', 'gnn_smtpmail_logs' );

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once GNN_SMTPMAIL_DIR . 'includes/class-gnn-smtpmail-logger.php';
require_once GNN_SMTPMAIL_DIR . 'includes/class-gnn-smtpmail.php';
require_once GNN_SMTPMAIL_DIR . 'inc/updater.php';

register_activation_hook( __FILE__, array( 'GNN_SMTPMail', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'GNN_SMTPMail', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'GNN_SMTPMail', 'instance' ) );

/**
 * Add plugin action links
 */
function gnn_smtpmail_plugin_links( $links ) {
    $donate_link = '<a href="https://buymeacoffee.com/bigdesigner" target="_blank" style="font-weight:bold; color:#d63638;">' . esc_html__( 'Donate', 'gnn-smtpmail' ) . '</a>';
    $settings_link = '<a href="' . admin_url( 'admin.php?page=gnn-smtpmail-custom' ) . '">' . esc_html__( 'Settings', 'gnn-smtpmail' ) . '</a>';
    $update_url = wp_nonce_url( admin_url( 'plugins.php?gnn_smtpmail_check_update=1' ), 'gnn_smtpmail_manual_update' );
    $update_link = '<a href="' . esc_url( $update_url ) . '">' . esc_html__( 'Check Updates', 'gnn-smtpmail' ) . '</a>';

    array_unshift( $links, $donate_link, $settings_link, $update_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gnn_smtpmail_plugin_links' );