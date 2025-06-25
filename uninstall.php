<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * This script is responsible for cleaning up any data created by the plugin,
 * including database tables, options, and transients.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load the database manager class
require_once plugin_dir_path(__FILE__) . 'includes/db/class-straico-db-manager.php';

// Create an instance of the database manager
$db_manager = new Straico_DB_Manager();

// Remove database tables
$db_manager->uninstall();

// Remove plugin options
$options = array(
    'straico_api_key',
    'straico_model_update_frequency',
    'straico_notify_model_changes',
    'straico_model_notification_emails',
    'straico_notify_low_coins',
    'straico_low_coin_threshold',
    'straico_coin_check_frequency',
    'straico_low_coin_notification_emails',
    'straico_db_version'
);

foreach ($options as $option) {
    delete_option($option);
}

// Remove transients
$transients = array(
    'straico_previous_models',
    'straico_last_balance_notification'
);

foreach ($transients as $transient) {
    delete_transient($transient);
}

// Clear any scheduled cron events
wp_clear_scheduled_hook('straico_model_update_event');
wp_clear_scheduled_hook('straico_coin_check_event');

// Remove any uploaded files in the WordPress uploads directory
$upload_dir = wp_upload_dir();
$straico_dir = trailingslashit($upload_dir['basedir']) . 'straico';
if (is_dir($straico_dir)) {
    $files = glob($straico_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($straico_dir);
}
