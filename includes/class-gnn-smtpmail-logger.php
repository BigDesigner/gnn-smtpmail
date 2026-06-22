<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GNN_SMTPMail_Logger {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . GNN_SMTPMAIL_TABLE;
    }

    public static function create_table() {
        global $wpdb;
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            logged_at datetime NOT NULL,
            channel varchar(20) NOT NULL,
            recipient text NOT NULL,
            subject text NULL,
            status varchar(20) NOT NULL,
            message text NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY channel (channel),
            KEY logged_at (logged_at)
        ) $charset_collate;";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta( $sql );
    }

    public static function insert( $channel, $recipient, $subject, $status, $message = '' ) {
        if ( ! in_array( $status, array( 'success', 'error' ), true ) ) {
            $status = 'error';
        }
        global $wpdb;
        $table = self::table_name();
        $wpdb->insert(
            $table,
            array(
                'logged_at' => current_time( 'mysql' ),
                'channel'   => sanitize_text_field( $channel ),
                'recipient' => sanitize_textarea_field( is_array($recipient) ? implode(',', array_map('sanitize_text_field', $recipient)) : $recipient ),
                'subject'   => sanitize_textarea_field( (string) $subject ),
                'status'    => sanitize_text_field( $status ),
                'message'   => sanitize_textarea_field( (string) $message ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public static function clear_all() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query( "TRUNCATE TABLE $table" );
    }

    public static function get_logs( $paged = 1, $per_page = 20, $status = '' ) {
        global $wpdb;
        $table = self::table_name();
        $offset = max(0, ( $paged - 1 ) * $per_page );

        $where = '1=1';
        $params = array();
        if ( in_array( $status, array( 'success', 'error' ), true ) ) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }

        $total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE $where", $params ) );

        $query = "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT %d OFFSET %d";
        $params2 = array_merge( $params, array( $per_page, $offset ) );
        $rows = $wpdb->get_results( $wpdb->prepare( $query, $params2 ) );
        return array( 'rows' => $rows, 'total' => intval($total) );
    }

    public static function drop_table() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query( "DROP TABLE IF EXISTS $table" );
    }
}