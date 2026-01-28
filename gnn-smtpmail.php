<?php
/**
 * Plugin Name: GNN SMTPMail
 * Plugin URI:  https://example.com/gnn-smtpmail
 * Description: A lightweight SMTP plugin for WordPress.
 * Version:     1.0.0
 * Author:      GNN Team
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * Text Domain: gnn-smtpmail
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GNN_SMTP_VERSION', '1.0.0');
define('GNN_SMTP_FILE', __FILE__);
define('GNN_SMTP_DIR', plugin_dir_path(__FILE__));
define('GNN_SMTP_URL', plugin_dir_url(__FILE__));

// Load Classes
require_once GNN_SMTP_DIR . 'includes/class-gnn-smtpmail-logger.php';
require_once GNN_SMTP_DIR . 'includes/class-gnn-smtpmail.php';
require_once GNN_SMTP_DIR . 'includes/class-gnn-smtpmail-admin.php';

/**
 * Activation Hook
 */
function gnn_smtp_activate()
{
    // 1. Trigger static activation methods
    if (class_exists('GNN_SMTPMail')) {
        GNN_SMTPMail::activate();
    }
}
register_activation_hook(__FILE__, 'gnn_smtp_activate');

/**
 * Initialization
 */
function gnn_smtp_init()
{
    // 1. Init Main Mailer Logic
    if (class_exists('GNN_SMTPMail')) {
        $mailer = new GNN_SMTPMail();
        $mailer->run();
    }

    // 2. Init Admin Interface (only in admin)
    if (is_admin() && class_exists('GNN_SMTPMail_Admin')) {
        $admin = new GNN_SMTPMail_Admin();
        $admin->init();
    }
}
add_action('plugins_loaded', 'gnn_smtp_init');

/**
 * Load Text Domain
 */
function gnn_smtp_load_textdomain()
{
    load_plugin_textdomain('gnn-smtpmail', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'gnn_smtp_load_textdomain');
