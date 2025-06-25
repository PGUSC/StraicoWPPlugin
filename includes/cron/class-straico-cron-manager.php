<?php
/**
 * Handles cron job management for the plugin.
 *
 * This class manages the registration and execution of cron jobs
 * for model updates and coin balance checks.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/cron
 */

class Straico_Cron_Manager {

    /**
     * The options class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Options    $options    Handles plugin options.
     */
    private $options;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->options = new Straico_Options();

        // Register custom cron intervals
        add_filter('cron_schedules', array($this, 'add_cron_intervals'));

        // Register activation and deactivation hooks
        register_activation_hook(STRAICO_INTEGRATION_PATH . 'straico-integration.php', array($this, 'activate_cron_jobs'));
        register_deactivation_hook(STRAICO_INTEGRATION_PATH . 'straico-integration.php', array($this, 'deactivate_cron_jobs'));
    }

    /**
     * Add custom cron intervals.
     *
     * @since    1.0.0
     * @param    array    $schedules    The existing cron schedules.
     * @return   array                  Modified cron schedules.
     */
    public function add_cron_intervals($schedules) {
        // Add interval for model updates (default 12 hours)
        $model_update_hours = $this->options->get_model_update_frequency();
        $schedules['straico_model_update'] = array(
            'interval' => $model_update_hours * HOUR_IN_SECONDS,
            'display' => sprintf(
                /* translators: %d: number of hours */
                _n('Every %d hour', 'Every %d hours', $model_update_hours, 'straico-integration'),
                $model_update_hours
            )
        );

        // Add interval for coin balance checks (default 360 minutes = 6 hours)
        $coin_check_minutes = $this->options->get_coin_check_frequency();
        $schedules['straico_coin_check'] = array(
            'interval' => $coin_check_minutes * MINUTE_IN_SECONDS,
            'display' => sprintf(
                /* translators: %d: number of minutes */
                _n('Every %d minute', 'Every %d minutes', $coin_check_minutes, 'straico-integration'),
                $coin_check_minutes
            )
        );

        return $schedules;
    }

    /**
     * Activate cron jobs.
     *
     * @since    1.0.0
     */
    public function activate_cron_jobs() {
        // Schedule model updates if enabled
        if (!wp_next_scheduled('straico_model_update_event')) {
            wp_schedule_event(time(), 'straico_model_update', 'straico_model_update_event');
        }

        // Schedule coin balance checks if enabled
        if ($this->options->is_low_coin_notification_enabled() && !wp_next_scheduled('straico_coin_check_event')) {
            wp_schedule_event(time(), 'straico_coin_check', 'straico_coin_check_event');
        }
    }

    /**
     * Deactivate cron jobs.
     *
     * @since    1.0.0
     */
    public function deactivate_cron_jobs() {
        wp_clear_scheduled_hook('straico_model_update_event');
        wp_clear_scheduled_hook('straico_coin_check_event');
    }

    /**
     * Update cron schedules based on new settings.
     *
     * @since    1.0.0
     * @param    bool|null $coin_check_enabled_override Optional. Explicit state for coin check notifications.
     *                                                  If null, the method will fetch the option itself.
     */
    public function update_cron_schedules($coin_check_enabled_override = null) {
        // Update model update schedule
        wp_clear_scheduled_hook('straico_model_update_event');
        wp_schedule_event(time(), 'straico_model_update', 'straico_model_update_event');

        // Determine if coin check should be enabled
        $schedule_coin_check = false;
        if (is_bool($coin_check_enabled_override)) {
            // Use the override if provided (this comes from the straico_notify_low_coins option update)
            $schedule_coin_check = $coin_check_enabled_override;
        } else {
            // Fallback to fetching the option directly (for other option updates or direct calls)
            $schedule_coin_check = $this->options->is_low_coin_notification_enabled();
        }

        // Update coin check schedule
        wp_clear_scheduled_hook('straico_coin_check_event');
        if ($schedule_coin_check) {
            wp_schedule_event(time(), 'straico_coin_check', 'straico_coin_check_event');
        }
    }

    /**
     * Check if a cron job is scheduled.
     *
     * @since    1.0.0
     * @param    string    $event    The event name to check.
     * @return   bool               Whether the event is scheduled.
     */
    public function is_event_scheduled($event) {
        return (bool) wp_next_scheduled($event);
    }

    /**
     * Get the next scheduled time for an event.
     *
     * @since    1.0.0
     * @param    string    $event       The event name.
     * @param    bool      $formatted   Whether to return a formatted date string.
     * @return   mixed                  Timestamp or formatted date string, false if not scheduled.
     */
    public function get_next_scheduled_time($event, $formatted = false) {
        $timestamp = wp_next_scheduled($event);
        
        if (!$timestamp) {
            return false;
        }

        return $formatted ? get_date_from_gmt(date('Y-m-d H:i:s', $timestamp)) : $timestamp;
    }

    /**
     * Force run a cron event immediately.
     *
     * @since    1.0.0
     * @param    string    $event    The event name to run.
     * @return   bool               Whether the event was successfully triggered.
     */
    public function force_run_event($event) {
        if (!in_array($event, array('straico_model_update_event', 'straico_coin_check_event'))) {
            return false;
        }

        do_action($event);
        return true;
    }

    /**
     * Get the status of all cron jobs.
     *
     * @since    1.0.0
     * @return   array    Array of cron job statuses.
     */
    public function get_cron_status() {
        $status = array();

        // Model update status
        $model_update_time = $this->get_next_scheduled_time('straico_model_update_event', true);
        $status['model_update'] = array(
            'enabled' => (bool) $model_update_time,
            'next_run' => $model_update_time ?: __('Not scheduled', 'straico-integration'),
            'frequency' => sprintf(
                /* translators: %d: number of hours */
                _n('%d hour', '%d hours', $this->options->get_model_update_frequency(), 'straico-integration'),
                $this->options->get_model_update_frequency()
            )
        );

        // Coin check status
        $coin_check_time = $this->get_next_scheduled_time('straico_coin_check_event', true);
        $status['coin_check'] = array(
            'enabled' => $this->options->is_low_coin_notification_enabled(),
            'next_run' => $coin_check_time ?: __('Not scheduled', 'straico-integration'),
            'frequency' => sprintf(
                /* translators: %d: number of minutes */
                _n('%d minute', '%d minutes', $this->options->get_coin_check_frequency(), 'straico-integration'),
                $this->options->get_coin_check_frequency()
            ),
            'threshold' => $this->options->get_low_coin_threshold()
        );

        return $status;
    }

    /**
     * Handle failed cron executions.
     *
     * @since    1.0.0
     * @param    string    $event     The event that failed.
     * @param    string    $error     The error message.
     * @param    mixed     $data      Optional. Additional data about the failure.
     */
    public function log_cron_failure($event, $error, $data = null) {
        $message = sprintf(
            '[%s] Cron job "%s" failed: %s',
            current_time('mysql'),
            $event,
            $error
        );

        if ($data) {
            $message .= "\nAdditional data: " . print_r($data, true);
        }

        error_log($message);

        // Optionally notify admin
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            wp_mail(
                $admin_email,
                sprintf(
                    /* translators: %s: event name */
                    __('[Straico] Cron Job Failed: %s', 'straico-integration'),
                    $event
                ),
                $message
            );
        }
    }

    /**
     * Clean up old cron-related data.
     *
     * @since    1.0.0
     * @param    int    $days    Optional. Number of days of data to keep.
     */
    public function cleanup_old_data($days = 30) {
        global $wpdb;

        // Get database manager instance
        $db_manager = new Straico_DB_Manager();
        $db_manager->cleanup_old_records($days);
    }
}
