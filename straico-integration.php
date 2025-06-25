<?php
/**
 * Plugin Name: Straico Integration
 * Plugin URI: https://straico.com
 * Description: A WordPress plugin for integrating Straico API functionalities including RAG, Agent, and Prompt Completion capabilities.
 * Version: 1.0.0
 * Author: Straico
 * Author URI: https://straico.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: straico-integration
 * Domain Path: /languages
 *
 * @package Straico_Integration
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('STRAICO_INTEGRATION_VERSION', '1.0.0');

/**
 * Plugin base directory path.
 */
define('STRAICO_INTEGRATION_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin base URL.
 */
define('STRAICO_INTEGRATION_URL', plugin_dir_url(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once STRAICO_INTEGRATION_PATH . 'includes/class-straico-loader.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_straico_integration() {
    $plugin = new Straico_Loader();
    $plugin->run();
}

// Register activation hook
register_activation_hook(__FILE__, function() {
    // Create database tables
    require_once STRAICO_INTEGRATION_PATH . 'includes/db/class-straico-db-manager.php';
    $db_manager = new Straico_DB_Manager();
    $db_manager->install();
});

// Initialize the plugin
run_straico_integration();
