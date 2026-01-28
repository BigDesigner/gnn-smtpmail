<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('gnn_smtp_options');
delete_site_option('gnn_smtp_options');

// Drop logs table
$table_name = $wpdb->prefix . 'gnn_smtp_logs';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
