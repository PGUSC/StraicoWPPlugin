<?php
/**
 * Handles model update cron jobs.
 *
 * This class manages the periodic checking of available models
 * and detects changes in the model list.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/cron
 */

class Straico_Model_Updates {

    /**
     * The options class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Options    $options    Handles plugin options.
     */
    private $options;

    /**
     * The models API class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Models_API    $models_api    Handles model API interactions.
     */
    private $models_api;

    /**
     * The database manager class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_DB_Manager    $db_manager    Handles database operations.
     */
    private $db_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->options = new Straico_Options();
        $this->models_api = new Straico_Models_API();
        $this->db_manager = new Straico_DB_Manager();

        // Register the cron action
        add_action('straico_model_update_event', array($this, 'check_model_updates'));
    }

    /**
     * Check for model updates.
     *
     * @since    1.0.0
     */
    public function check_model_updates() {
        // Get the current list of models
        $current_models = $this->models_api->get_models();
        if (is_wp_error($current_models)) {
            error_log(sprintf(
                '[Straico] Failed to fetch current models: %s',
                $current_models->get_error_message()
            ));
            return;
        }

        // Get the previous list of models from transient
        $previous_models = get_transient('straico_previous_models');
        if ($previous_models === false) {
            // If no previous models exist, store current models and exit
            set_transient('straico_previous_models', $current_models);
            return;
        }

        // Compare models and detect changes
        $changes = $this->models_api->detect_model_changes($previous_models, $current_models);

        // If there are changes and notifications are enabled
        if (($changes['added'] || $changes['removed']) && $this->options->is_model_change_notification_enabled()) {
            // Log changes to database
            foreach ($changes['added'] as $model) {
                $this->db_manager->log_model_change(
                    $model['model'],
                    $model['name'],
                    $model['type'],
                    'added'
                );
            }

            foreach ($changes['removed'] as $model) {
                $this->db_manager->log_model_change(
                    $model['model'],
                    $model['name'],
                    $model['type'],
                    'removed'
                );
            }

            // Send notification email
            $recipients = $this->options->get_model_notification_emails();
            if (!empty($recipients)) {
                $this->models_api->send_model_changes_notification($changes, $recipients);
            }
        }

        // Update stored models
        set_transient('straico_previous_models', $current_models);
    }

    /**
     * Force an immediate model update check.
     *
     * @since    1.0.0
     * @return   array|WP_Error    The changes detected or error on failure.
     */
    public function force_check() {
        // Get the current list of models
        $current_models = $this->models_api->get_models();
        if (is_wp_error($current_models)) {
            return $current_models;
        }

        // Get the previous list of models from transient
        $previous_models = get_transient('straico_previous_models');
        if ($previous_models === false) {
            set_transient('straico_previous_models', $current_models);
            return array('added' => array(), 'removed' => array());
        }

        // Compare models and detect changes
        $changes = $this->models_api->detect_model_changes($previous_models, $current_models);

        // Update stored models
        set_transient('straico_previous_models', $current_models);

        return $changes;
    }

    /**
     * Get the list of available models.
     *
     * @since    1.0.0
     * @param    bool      $force_update    Whether to force a fresh update.
     * @return   array|WP_Error            The list of models or error on failure.
     */
    public function get_models($force_update = false) {
        if (!$force_update) {
            $models = get_transient('straico_previous_models');
            if ($models !== false) {
                return $models;
            }
        }

        $models = $this->models_api->get_models();
        if (!is_wp_error($models)) {
            set_transient('straico_previous_models', $models);
        }

        return $models;
    }

    /**
     * Get recent model changes.
     *
     * @since    1.0.0
     * @param    int       $limit    Optional. Number of changes to retrieve.
     * @return   array              Array of model changes.
     */
    public function get_recent_changes($limit = 10) {
        return $this->db_manager->get_recent_model_changes($limit);
    }

    /**
     * Format model changes for display.
     *
     * @since    1.0.0
     * @param    array    $changes    The changes to format.
     * @return   string              Formatted changes message.
     */
    public function format_changes_message($changes) {
        $message = '';

        if (!empty($changes['added'])) {
            $message .= __('New Models Added:', 'straico-integration') . "\n";
            foreach ($changes['added'] as $model) {
                $message .= sprintf(
                    "- %s (%s model)\n",
                    $model['name'],
                    ucfirst($model['type'])
                );
            }
            $message .= "\n";
        }

        if (!empty($changes['removed'])) {
            $message .= __('Models Removed:', 'straico-integration') . "\n";
            foreach ($changes['removed'] as $model) {
                $message .= sprintf(
                    "- %s (%s model)\n",
                    $model['name'],
                    ucfirst($model['type'])
                );
            }
        }

        return $message;
    }
}
