<?php
/**
 * Fired when the plugin is uninstalled.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

global $wpdb;
$option = 'gnn_smtpmail_settings';
delete_option( $option );
delete_site_option( $option );

$table = $wpdb->prefix . 'gnn_smtpmail_logs';
$wpdb->query( "DROP TABLE IF EXISTS $table" );