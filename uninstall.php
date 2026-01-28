<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('gnn_smtp_options');
delete_site_option('gnn_smtp_options');

// Delete all log posts using WordPress APIs (no direct DB queries)
$logs = get_posts(array(
    'post_type' => 'gnn_smtpmail_log',
    'numberposts' => -1,
    'post_status' => 'private',
    'fields' => 'ids',
));

foreach ($logs as $log_id) {
    wp_delete_post($log_id, true);
}
