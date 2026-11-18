<?php
/**
 * Fired when the plugin is uninstalled.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

/**
 * Clean up plugin options, transients, cron hooks, and log table for a single site.
 */
function gnn_smtpmail_uninstall_site() {
    global $wpdb;

    $options = array(
        'gnn_smtpmail_settings',
        'gnn_smtpmail_db_version',
        'gnn_smtpmail_last_insert_error',
    );
    foreach ( $options as $opt ) {
        delete_option( $opt );
        delete_site_option( $opt );
    }

    delete_transient( 'gnn_smtpmail_github_update_check' );
    wp_clear_scheduled_hook( 'gnn_smtpmail_daily_cleanup' );

    $table = $wpdb->prefix . 'gnn_smtpmail_logs';
    $wpdb->query( "DROP TABLE IF EXISTS `" . esc_sql( $table ) . "`" );
}

if ( is_multisite() ) {
    $site_ids = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $site_ids as $site_id ) {
        switch_to_blog( (int) $site_id );
        gnn_smtpmail_uninstall_site();
        restore_current_blog();
    }
} else {
    gnn_smtpmail_uninstall_site();
}
