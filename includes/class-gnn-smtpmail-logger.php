<?php
if (!defined('ABSPATH')) {
    exit;
}

class GNN_SMTP_Logger
{

    /**
     * Get the logger table name with prefix.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'gnn_smtp_logs';
    }

    /**
     * Create the logs table during plugin activation.
     */
    public static function create_table()
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			channel varchar(50) NOT NULL DEFAULT 'smtp',
			recipient text NOT NULL,
			subject text NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'sent',
			message longtext NOT NULL,
			context longtext,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
        dbDelta($sql);
    }

    /**
     * Drop the logs table.
     */
    public static function drop_table()
    {
        global $wpdb;
        $table_name = self::get_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }

    /**
     * Clear all log entries.
     */
    public static function clear_all()
    {
        global $wpdb;
        $table_name = self::get_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query("TRUNCATE TABLE $table_name");
    }

    /**
     * Insert a new log entry.
     *
     * @param string $channel   The sending channel (e.g., 'smtp', 'mail').
     * @param string|array $recipient Recipient email(s).
     * @param string $subject   Email subject.
     * @param string $status    Status string (e.g., 'success', 'failed').
     * @param string $message   Error message or debug info.
     * @param array  $context   Additional context data.
     * @return int|false The number of rows inserted, or false on error.
     */
    public static function insert($channel, $recipient, $subject, $status, $message, $context = array())
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $recipient_str = is_array($recipient) ? implode(', ', $recipient) : (string) $recipient;

        $data = array(
            'channel' => $channel,
            'recipient' => $recipient_str,
            'subject' => $subject,
            'status' => $status,
            'message' => $message,
            'context' => wp_json_encode($context),
            'created_at' => current_time('mysql'),
        );

        $format = array(
            '%s', // channel
            '%s', // recipient
            '%s', // subject
            '%s', // status
            '%s', // message
            '%s', // context
            '%s', // created_at
        );

        return $wpdb->insert($table_name, $data, $format);
    }

    /**
     * Retrieve logs with pagination and filtering.
     *
     * @param int    $paged    Current page number.
     * @param int    $per_page Number of items per page.
     * @param string $status   Optional status to filter by.
     * @return array Array containing 'rows', 'total', and 'pages'.
     */
    public static function get_logs($paged = 1, $per_page = 20, $status = '')
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $offset = ($paged - 1) * $per_page;
        $where_clauses = array('1=1');
        $query_args = array();

        if (!empty($status)) {
            $where_clauses[] = 'status = %s';
            $query_args[] = $status;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Count total
        $count_sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";
        if (!empty($query_args)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $total = $wpdb->get_var($wpdb->prepare($count_sql, $query_args));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $total = $wpdb->get_var($count_sql);
        }

        // Get rows
        $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_args[] = $per_page;
        $query_args[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $rows = $wpdb->get_results($wpdb->prepare($sql, $query_args));

        return array(
            'rows' => $rows,
            'total' => (int) $total,
            'pages' => ceil($total / $per_page),
        );
    }
}
