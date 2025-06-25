<?php
/**
 * Handles database operations for the plugin.
 *
 * This class manages custom database tables, including creation,
 * updates, and cleanup of plugin-specific database tables.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/db
 */

class Straico_DB_Manager {

    /**
     * The database version number.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $db_version    The current database version.
     */
    private $db_version = '1.0.0';

    /**
     * The prefix for plugin tables.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_prefix    The prefix for plugin tables.
     */
    private $table_prefix;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'straico_';
    }

    /**
     * Create or update the plugin's database tables.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function install() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Shortcodes table
        $table_name_shortcodes = $this->table_prefix . 'shortcodes';
        $sql_shortcodes = "CREATE TABLE IF NOT EXISTS $table_name_shortcodes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            agent_id varchar(100) NOT NULL,
            settings longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";
        dbDelta($sql_shortcodes);

        // Model changes tracking table
        $table_name_model_changes = $this->table_prefix . 'model_changes';
        $sql_model_changes = "CREATE TABLE IF NOT EXISTS $table_name_model_changes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            model_id varchar(100) NOT NULL,
            model_name varchar(255) NOT NULL,
            model_type varchar(50) NOT NULL,
            change_type enum('added', 'removed') NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY model_id (model_id),
            KEY change_type (change_type)
        ) $charset_collate;";
        dbDelta($sql_model_changes);

        // Coin balance history table
        $table_name_coin_history = $this->table_prefix . 'coin_history';
        $sql_coin_history = "CREATE TABLE IF NOT EXISTS $table_name_coin_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            balance decimal(10,2) NOT NULL,
            threshold decimal(10,2) NOT NULL,
            notification_sent tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql_coin_history);

        // Store the database version
        update_option('straico_db_version', $this->db_version);

        return true;
    }

    /**
     * Remove the plugin's database tables.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function uninstall() {
        global $wpdb;

        $tables = array(
            'shortcodes',
            'model_changes',
            'coin_history'
        );

        foreach ($tables as $table) {
            $table_name = $this->table_prefix . $table;
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
        }

        delete_option('straico_db_version');

        return true;
    }

    /**
     * Save a shortcode configuration.
     *
     * @since    1.0.0
     * @param    string    $name       The shortcode name.
     * @param    string    $agent_id   The agent ID.
     * @param    array     $settings   The shortcode settings.
     * @return   int|false             The inserted ID or false on failure.
     */
    public function save_shortcode($name, $agent_id, $settings) {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'agent_id' => $agent_id,
                'settings' => wp_json_encode($settings)
            ),
            array('%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update a shortcode configuration.
     *
     * @since    1.0.0
     * @param    int       $id         The shortcode ID.
     * @param    string    $name       The shortcode name.
     * @param    string    $agent_id   The agent ID.
     * @param    array     $settings   The shortcode settings.
     * @return   bool                  True on success, false on failure.
     */
    public function update_shortcode($id, $name, $agent_id, $settings) {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        return $wpdb->update(
            $table_name,
            array(
                'name' => $name,
                'agent_id' => $agent_id,
                'settings' => wp_json_encode($settings)
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Delete a shortcode configuration.
     *
     * @since    1.0.0
     * @param    int       $id    The shortcode ID.
     * @return   bool            True on success, false on failure.
     */
    public function delete_shortcode($id) {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Get a shortcode configuration by ID.
     *
     * @since    1.0.0
     * @param    int       $id    The shortcode ID.
     * @return   object|null     The shortcode data or null if not found.
     */
    public function get_shortcode($id) {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            )
        );

        if ($result) {
            $result->settings = json_decode($result->settings, true);
        }

        return $result;
    }

    /**
     * Get a shortcode configuration by name.
     *
     * @since    1.0.0
     * @param    string    $name    The shortcode name.
     * @return   object|null       The shortcode data or null if not found.
     */
    public function get_shortcode_by_name($name) {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE name = %s",
                $name
            )
        );

        if ($result) {
            $result->settings = json_decode($result->settings, true);
        }

        return $result;
    }

    /**
     * Get all shortcode configurations.
     *
     * @since    1.0.0
     * @return   array    Array of shortcode configurations.
     */
    public function get_all_shortcodes() {
        global $wpdb;

        $table_name = $this->table_prefix . 'shortcodes';
        
        // Check if table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )
        );

        if (!$table_exists) {
            // Install tables if they don't exist
            $this->install();
            return array(); // Return empty array since table was just created
        }

        $results = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY name ASC"
        );

        foreach ($results as $result) {
            $result->settings = json_decode($result->settings, true);
        }

        return $results;
    }

    /**
     * Log a model change.
     *
     * @since    1.0.0
     * @param    string    $model_id      The model ID.
     * @param    string    $model_name    The model name.
     * @param    string    $model_type    The model type (chat or image).
     * @param    string    $change_type   The type of change (added or removed).
     * @return   int|false                The inserted ID or false on failure.
     */
    public function log_model_change($model_id, $model_name, $model_type, $change_type) {
        global $wpdb;

        $table_name = $this->table_prefix . 'model_changes';
        $result = $wpdb->insert(
            $table_name,
            array(
                'model_id' => $model_id,
                'model_name' => $model_name,
                'model_type' => $model_type,
                'change_type' => $change_type
            ),
            array('%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get recent model changes.
     *
     * @since    1.0.0
     * @param    int       $limit    Optional. Number of changes to retrieve.
     * @return   array              Array of model changes.
     */
    public function get_recent_model_changes($limit = 10) {
        global $wpdb;

        $table_name = $this->table_prefix . 'model_changes';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Log a coin balance check.
     *
     * @since    1.0.0
     * @param    float    $balance            The current balance.
     * @param    float    $threshold          The threshold value.
     * @param    bool     $notification_sent  Whether a notification was sent.
     * @return   int|false                    The inserted ID or false on failure.
     */
    public function log_coin_balance($balance, $threshold, $notification_sent = false) {
        global $wpdb;

        $table_name = $this->table_prefix . 'coin_history';
        $result = $wpdb->insert(
            $table_name,
            array(
                'balance' => $balance,
                'threshold' => $threshold,
                'notification_sent' => $notification_sent ? 1 : 0
            ),
            array('%f', '%f', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get recent coin balance history.
     *
     * @since    1.0.0
     * @param    int       $limit    Optional. Number of records to retrieve.
     * @return   array              Array of balance history records.
     */
    public function get_recent_coin_history($limit = 10) {
        global $wpdb;

        $table_name = $this->table_prefix . 'coin_history';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }

    /**
     * Clean up old records.
     *
     * @since    1.0.0
     * @param    int    $days    Optional. Number of days of records to keep.
     * @return   bool           True on success, false on failure.
     */
    public function cleanup_old_records($days = 30) {
        global $wpdb;

        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

        // Clean up model changes
        $table_name = $this->table_prefix . 'model_changes';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            )
        );

        // Clean up coin history
        $table_name = $this->table_prefix . 'coin_history';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            )
        );

        return true;
    }
}
