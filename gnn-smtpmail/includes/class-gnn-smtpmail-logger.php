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

        $sql = "CREATE TABLE `" . esc_sql( $table ) . "` (
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
        
        $rec_str = is_array($recipient) ? implode(',', $recipient) : $recipient;
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 'GNN Logger - Inserting: Table=%s, Channel=%s, Recipient=%s, Subject=%s, Status=%s', $table, $channel, $rec_str, $subject, $status ) );
        }

        $inserted = $wpdb->insert(
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
        if ( false === $inserted ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'GNN Logger - Insert Failed: ' . $wpdb->last_error );
            }
            update_option( 'gnn_smtpmail_last_insert_error', $wpdb->last_error );
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'GNN Logger - Insert Succeeded. Row ID: ' . $wpdb->insert_id );
            }
            delete_option( 'gnn_smtpmail_last_insert_error' );
        }
    }

    public static function clear_all() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query( "TRUNCATE TABLE `" . esc_sql( $table ) . "`" );
    }

    public static function get_logs( $paged = 1, $per_page = 20, $status = '' ) {
        global $wpdb;
        $table = esc_sql( self::table_name() );
        $offset = max(0, ( $paged - 1 ) * $per_page );

        $where = '1=1';
        $params = array();
        if ( in_array( $status, array( 'success', 'error' ), true ) ) {
            $where .= ' AND status = %s';
            $params[] = $status;
        }

        if ( empty( $params ) ) {
            $total = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` WHERE {$where}" );
        } else {
            $total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE {$where}", $params ) );
        }

        $params2 = array_merge( $params, array( (int) $per_page, (int) $offset ) );
        $query = "SELECT * FROM `{$table}` WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $query, $params2 ) );
        return array( 'rows' => $rows, 'total' => intval($total) );
    }

    /**
     * Get aggregate counts and latest timestamp from the log table.
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        $raw_table  = self::table_name();
        $show_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $raw_table ) );
        if ( empty( $show_table ) || strcasecmp( $show_table, $raw_table ) !== 0 ) {
            return array(
                'total'          => 0,
                'success'        => 0,
                'error'          => 0,
                'last_logged_at' => '',
            );
        }

        $table = esc_sql( $raw_table );
        $row   = $wpdb->get_row(
            "SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) AS error_count,
                MAX(logged_at) AS last_logged_at
             FROM `{$table}`"
        );

        if ( ! $row ) {
            return array(
                'total'          => 0,
                'success'        => 0,
                'error'          => 0,
                'last_logged_at' => '',
            );
        }

        return array(
            'total'          => intval( $row->total ),
            'success'        => intval( $row->success_count ),
            'error'          => intval( $row->error_count ),
            'last_logged_at' => (string) $row->last_logged_at,
        );
    }

    /**
     * Fetch logs for CSV/JSON export.
     *
     * @param string $status Optional status filter ('success'|'error'|'').
     * @param int    $limit  Maximum number of rows to export.
     * @return array
     */
    public static function get_logs_for_export( $status = '', $limit = 5000 ) {
        global $wpdb;
        $table = esc_sql( self::table_name() );
        $limit = max( 1, min( 10000, intval( $limit ) ) );

        if ( in_array( $status, array( 'success', 'error' ), true ) ) {
            $query = $wpdb->prepare(
                "SELECT id, logged_at, channel, recipient, subject, status, message FROM `{$table}` WHERE status = %s ORDER BY id DESC LIMIT %d",
                $status,
                $limit
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT id, logged_at, channel, recipient, subject, status, message FROM `{$table}` ORDER BY id DESC LIMIT %d",
                $limit
            );
        }

        $rows = $wpdb->get_results( $query, ARRAY_A );
        return is_array( $rows ) ? $rows : array();
    }

    /**
     * Prune log entries older than the configured retention period.
     */
    public static function prune_old_logs() {
        global $wpdb;
        $table = self::table_name();
        $days = absint( apply_filters( 'gnn_smtpmail_log_retention_days', 30 ) );
        if ( $days < 1 ) {
            return;
        }
        $cutoff = wp_date( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `" . esc_sql( $table ) . "` WHERE logged_at < %s",
                $cutoff
            )
        );
    }

    public static function drop_table() {
        global $wpdb;
        $table = self::table_name();
        $wpdb->query( "DROP TABLE IF EXISTS `" . esc_sql( $table ) . "`" );
    }
}