<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logger class using WordPress Custom Post Type (no direct DB queries).
 */
class GNN_SMTP_Logger
{
    /**
     * Custom post type name for logs.
     */
    const POST_TYPE = 'gnn_smtpmail_log';

    /**
     * Register the custom post type for logs.
     * Called on 'init' hook.
     */
    public static function register_post_type()
    {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name' => __('Email Logs', 'gnn-smtpmail'),
                'singular_name' => __('Email Log', 'gnn-smtpmail'),
            ),
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ));
    }

    /**
     * Add a new log entry using WordPress post and meta APIs.
     *
     * @param string       $channel   The sending channel (e.g., 'smtp', 'mail').
     * @param string|array $recipient Recipient email(s).
     * @param string       $subject   Email subject.
     * @param string       $status    Status string (e.g., 'sent', 'failed').
     * @param string       $message   Error message or debug info.
     * @param array        $context   Additional context data.
     * @return int|false Post ID on success, false on failure.
     */
    public static function add($channel, $recipient, $subject, $status, $message, $context = array())
    {
        $recipient_str = is_array($recipient) ? implode(', ', $recipient) : (string) $recipient;

        $post_id = wp_insert_post(array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'post_title' => sanitize_text_field($subject ? $subject : __('No Subject', 'gnn-smtpmail')),
            'post_content' => sanitize_textarea_field($message),
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        ), true);

        if (is_wp_error($post_id) || !$post_id) {
            return false;
        }

        // Store structured data as post meta
        update_post_meta($post_id, 'gnn_channel', sanitize_text_field($channel));
        update_post_meta($post_id, 'gnn_recipient', sanitize_text_field($recipient_str));
        update_post_meta($post_id, 'gnn_subject', sanitize_text_field($subject));
        update_post_meta($post_id, 'gnn_status', sanitize_text_field($status));
        update_post_meta($post_id, 'gnn_message', sanitize_textarea_field($message));
        update_post_meta($post_id, 'gnn_context', wp_json_encode($context));
        update_post_meta($post_id, 'gnn_created_at', current_time('mysql'));

        return $post_id;
    }

    /**
     * Retrieve logs with pagination and filtering using WP_Query.
     *
     * @param int    $paged    Current page number.
     * @param int    $per_page Number of items per page.
     * @param string $status   Optional status to filter by.
     * @return array Array containing 'rows', 'total', and 'pages'.
     */
    public static function get_logs($paged = 1, $per_page = 20, $status = '')
    {
        $args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'private',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        );

        if (!empty($status)) {
            $args['meta_key'] = 'gnn_status';
            $args['meta_value'] = sanitize_text_field($status);
        }

        $query = new WP_Query($args);
        $rows = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $rows[] = (object) array(
                    'id' => $post_id,
                    'created_at' => get_post_meta($post_id, 'gnn_created_at', true),
                    'status' => get_post_meta($post_id, 'gnn_status', true),
                    'subject' => get_post_meta($post_id, 'gnn_subject', true),
                    'recipient' => get_post_meta($post_id, 'gnn_recipient', true),
                    'message' => get_post_meta($post_id, 'gnn_message', true),
                    'channel' => get_post_meta($post_id, 'gnn_channel', true),
                    'context' => get_post_meta($post_id, 'gnn_context', true),
                );
            }
            wp_reset_postdata();
        }

        $total = $query->found_posts;
        $pages = $query->max_num_pages;

        return array(
            'rows' => $rows,
            'total' => $total,
            'pages' => $pages,
        );
    }

    /**
     * Clear all log entries using WordPress post deletion.
     */
    public static function clear_all()
    {
        $logs = get_posts(array(
            'post_type' => self::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'private',
            'fields' => 'ids',
        ));

        foreach ($logs as $log_id) {
            wp_delete_post($log_id, true);
        }
    }
}
