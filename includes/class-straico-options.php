<?php
/**
 * Handles all options and settings management for the plugin.
 *
 * This class provides methods for storing and retrieving plugin options,
 * including API keys, update frequencies, and notification settings.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes
 */

class Straico_Options {

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    private $plugin_name;

    /**
     * The security class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Security    $security    Handles security operations.
     */
    private $security;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'straico-integration';
        $this->security = new Straico_Security();
    }

    /**
     * Register hooks for updating cron schedules when settings change.
     *
     * @since    1.0.0
     */
    public function register_cron_update_hooks() {
        // Add hooks to update cron schedules when settings are saved
        add_action('update_option_straico_model_update_frequency', array($this, 'update_cron_schedules'));
        add_action('update_option_straico_notify_model_changes', array($this, 'update_cron_schedules'));
        // Use a dedicated handler for straico_notify_low_coins to pass the new value
        add_action('update_option_straico_notify_low_coins', array($this, 'handle_notify_low_coins_option_change'), 10, 2);
        add_action('update_option_straico_coin_check_frequency', array($this, 'update_cron_schedules'));
        add_action('update_option_straico_low_coin_threshold', array($this, 'update_cron_schedules'));
        add_action('update_option_straico_low_coin_notification_emails', array($this, 'update_cron_schedules'));
    }

    /**
     * Register all plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings sections
        add_settings_section(
            'straico_api_settings',
            __('API Settings', 'straico-integration'),
            array($this, 'render_api_settings_section'),
            $this->plugin_name
        );

        add_settings_section(
            'straico_model_settings',
            __('Model Update Settings', 'straico-integration'),
            array($this, 'render_model_settings_section'),
            $this->plugin_name
        );

        add_settings_section(
            'straico_notification_settings',
            __('Notification Settings', 'straico-integration'),
            array($this, 'render_notification_settings_section'),
            $this->plugin_name
        );

        // Register API settings
        register_setting(
            $this->plugin_name,
            'straico_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this->security, 'validate_api_key'),
                'default' => ''
            )
        );

        // Register model update settings
        register_setting(
            $this->plugin_name,
            'straico_model_update_frequency',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 12
            )
        );

        register_setting(
            $this->plugin_name,
            'straico_notify_model_changes',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            )
        );

        register_setting(
            $this->plugin_name,
            'straico_model_notification_emails',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_email_list'),
                'default' => array()
            )
        );

        // Register coin balance notification settings
        register_setting(
            $this->plugin_name,
            'straico_notify_low_coins',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => false
            )
        );

        register_setting(
            $this->plugin_name,
            'straico_low_coin_threshold',
            array(
                'type' => 'number',
                'sanitize_callback' => 'floatval',
                'default' => 100
            )
        );

        register_setting(
            $this->plugin_name,
            'straico_coin_check_frequency',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 360
            )
        );

        register_setting(
            $this->plugin_name,
            'straico_low_coin_notification_emails',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_email_list'),
                'default' => array()
            )
        );
    }

    /**
     * Render the API settings section description.
     *
     * @since    1.0.0
     */
    public function render_api_settings_section() {
        echo '<p>' . esc_html__('Configure your Straico API settings below.', 'straico-integration') . '</p>';
    }

    /**
     * Render the model settings section description.
     *
     * @since    1.0.0
     */
    public function render_model_settings_section() {
        echo '<p>' . esc_html__('Configure how often to update the list of available LLM models.', 'straico-integration') . '</p>';
    }

    /**
     * Render the notification settings section description.
     *
     * @since    1.0.0
     */
    public function render_notification_settings_section() {
        echo '<p>' . esc_html__('Configure email notifications for model changes and low coin balance.', 'straico-integration') . '</p>';
    }

    /**
     * Get the API key.
     *
     * @since    1.0.0
     * @return   string    The API key.
     */
    public function get_api_key() {
        return get_option('straico_api_key', '');
    }

    /**
     * Get the model update frequency in hours.
     *
     * @since    1.0.0
     * @return   int       The update frequency in hours.
     */
    public function get_model_update_frequency() {
        return get_option('straico_model_update_frequency', 12);
    }

    /**
     * Check if model change notifications are enabled.
     *
     * @since    1.0.0
     * @return   bool      Whether notifications are enabled.
     */
    public function is_model_change_notification_enabled() {
        return get_option('straico_notify_model_changes', false);
    }

    /**
     * Get the email addresses for model change notifications.
     *
     * @since    1.0.0
     * @return   array     Array of email addresses.
     */
    public function get_model_notification_emails() {
        return get_option('straico_model_notification_emails', array());
    }

    /**
     * Check if low coin notifications are enabled.
     *
     * @since    1.0.0
     * @return   bool      Whether notifications are enabled.
     */
    public function is_low_coin_notification_enabled() {
        return get_option('straico_notify_low_coins', false);
    }

    /**
     * Get the low coin threshold.
     *
     * @since    1.0.0
     * @return   float     The threshold value.
     */
    public function get_low_coin_threshold() {
        return get_option('straico_low_coin_threshold', 100);
    }

    /**
     * Get the coin balance check frequency in minutes.
     *
     * @since    1.0.0
     * @return   int       The check frequency in minutes.
     */
    public function get_coin_check_frequency() {
        return get_option('straico_coin_check_frequency', 360);
    }

    /**
     * Get the email addresses for low coin notifications.
     *
     * @since    1.0.0
     * @return   array     Array of email addresses.
     */
    public function get_low_coin_notification_emails() {
        return get_option('straico_low_coin_notification_emails', array());
    }

    /**
     * Sanitize a checkbox value.
     *
     * @since    1.0.0
     * @param    mixed     $value    The value to sanitize.
     * @return   bool                The sanitized value.
     */
    public function sanitize_checkbox($value) {
        return (bool) $value;
    }

    /**
     * Sanitize a list of email addresses.
     *
     * @since    1.0.0
     * @param    mixed     $emails    The emails to sanitize.
     * @return   array                Array of sanitized email addresses.
     */
    public function sanitize_email_list($emails) {
        if (!is_array($emails)) {
            $emails = explode(',', $emails);
        }

        $sanitized = array();
        foreach ($emails as $email) {
            $email = trim($email);
            if ($this->security->sanitize_email($email)) {
                $sanitized[] = $email;
            }
        }

        return array_unique($sanitized);
    }

    /**
     * Update cron schedules when settings are changed.
     *
     * @since    1.0.0
     */
    public function update_cron_schedules() {
        $cron_manager = new Straico_Cron_Manager();
        // Call update_cron_schedules without specific override, relying on get_option
        $cron_manager->update_cron_schedules();
    }

    /**
     * Handle the change of the 'straico_notify_low_coins' option specifically.
     * This method receives the old and new values of the option.
     *
     * @since    1.0.1
     * @param    mixed $old_value The old value of the option.
     * @param    mixed $new_value The new value of the option.
     */
    public function handle_notify_low_coins_option_change($old_value, $new_value) {
        $cron_manager = new Straico_Cron_Manager();
        // Pass the explicit new state of 'straico_notify_low_coins'
        // The $new_value from the hook for a checkbox is '1' for checked, or it might not be present for unchecked.
        // sanitize_checkbox converts it to boolean.
        $is_enabled = $this->sanitize_checkbox($new_value);
        $cron_manager->update_cron_schedules($is_enabled);
    }
}
