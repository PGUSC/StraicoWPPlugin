<?php
/**
 * Handles shortcode management for the plugin.
 *
 * This class manages the registration, storage, and retrieval of
 * agent shortcodes that can be embedded in posts and pages.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/shortcodes
 */

class Straico_Shortcode_Manager {

    /**
     * The database manager class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_DB_Manager    $db_manager    Handles database operations.
     */
    private $db_manager;

    /**
     * The agent API class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Agent_API    $agent_api    Handles agent API interactions.
     */
    private $agent_api;

    /**
     * The shortcode renderer class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Shortcode_Renderer    $renderer    Handles shortcode rendering.
     */
    private $renderer;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->db_manager = new Straico_DB_Manager();
        $this->agent_api = new Straico_Agent_API();
        $this->renderer = new Straico_Shortcode_Renderer();
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        // Get all shortcodes from the database
        $shortcodes = $this->db_manager->get_all_shortcodes();

        // Register each shortcode
        foreach ($shortcodes as $shortcode) {
            add_shortcode(
                'straico_' . $shortcode->name,
                array($this->renderer, 'render_shortcode')
            );
        }
    }

    /**
     * Create a new shortcode.
     *
     * @since    1.0.0
     * @param    string    $name       The shortcode name.
     * @param    string    $agent_id   The agent ID.
     * @param    array     $settings   The shortcode settings.
     * @return   int|WP_Error         The shortcode ID or error on failure.
     */
    public function create_shortcode($name, $agent_id, $settings) {
        // Validate shortcode name
        $name = sanitize_title($name);
        if (empty($name)) {
            return new WP_Error(
                'invalid_shortcode_name',
                __('Invalid shortcode name', 'straico-integration')
            );
        }

        // Check if shortcode name is already in use
        if ($this->db_manager->get_shortcode_by_name($name)) {
            return new WP_Error(
                'shortcode_exists',
                __('A shortcode with this name already exists', 'straico-integration')
            );
        }

        // Validate agent ID
        $agent = $this->agent_api->get_agent_details($agent_id);
        if (is_wp_error($agent)) {
            return new WP_Error(
                'invalid_agent',
                __('Invalid agent ID', 'straico-integration')
            );
        }

        // Validate settings
        $validation = $this->validate_shortcode_settings($settings);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Create the shortcode
        $shortcode_id = $this->db_manager->save_shortcode($name, $agent_id, $settings);
        if (!$shortcode_id) {
            return new WP_Error(
                'shortcode_creation_failed',
                __('Failed to create shortcode', 'straico-integration')
            );
        }

        // Register the shortcode
        add_shortcode(
            'straico_' . $name,
            array($this->renderer, 'render_shortcode')
        );

        return $shortcode_id;
    }

    /**
     * Update an existing shortcode.
     *
     * @since    1.0.0
     * @param    int       $id         The shortcode ID.
     * @param    string    $name       The shortcode name.
     * @param    string    $agent_id   The agent ID.
     * @param    array     $settings   The shortcode settings.
     * @return   bool|WP_Error        True on success, error on failure.
     */
    public function update_shortcode($id, $name, $agent_id, $settings) {
        // Get existing shortcode
        $existing = $this->db_manager->get_shortcode($id);
        if (!$existing) {
            return new WP_Error(
                'shortcode_not_found',
                __('Shortcode not found', 'straico-integration')
            );
        }

        // Validate shortcode name
        $name = sanitize_title($name);
        if (empty($name)) {
            return new WP_Error(
                'invalid_shortcode_name',
                __('Invalid shortcode name', 'straico-integration')
            );
        }

        // Check if new name is already in use by another shortcode
        $existing_by_name = $this->db_manager->get_shortcode_by_name($name);
        if ($existing_by_name && $existing_by_name->id != $id) {
            return new WP_Error(
                'shortcode_exists',
                __('A shortcode with this name already exists', 'straico-integration')
            );
        }

        // Validate agent ID
        $agent = $this->agent_api->get_agent_details($agent_id);
        if (is_wp_error($agent)) {
            return new WP_Error(
                'invalid_agent',
                __('Invalid agent ID', 'straico-integration')
            );
        }

        // Validate settings
        $validation = $this->validate_shortcode_settings($settings);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Update the shortcode
        $result = $this->db_manager->update_shortcode($id, $name, $agent_id, $settings);
        if (!$result) {
            return new WP_Error(
                'shortcode_update_failed',
                __('Failed to update shortcode', 'straico-integration')
            );
        }

        // Remove old shortcode and register new one if name changed
        if ($existing->name !== $name) {
            remove_shortcode('straico_' . $existing->name);
            add_shortcode(
                'straico_' . $name,
                array($this->renderer, 'render_shortcode')
            );
        }

        return true;
    }

    /**
     * Delete a shortcode.
     *
     * @since    1.0.0
     * @param    int       $id    The shortcode ID.
     * @return   bool|WP_Error   True on success, error on failure.
     */
    public function delete_shortcode($id) {
        // Get existing shortcode
        $shortcode = $this->db_manager->get_shortcode($id);
        if (!$shortcode) {
            return new WP_Error(
                'shortcode_not_found',
                __('Shortcode not found', 'straico-integration')
            );
        }

        // Delete the shortcode
        $result = $this->db_manager->delete_shortcode($id);
        if (!$result) {
            return new WP_Error(
                'shortcode_deletion_failed',
                __('Failed to delete shortcode', 'straico-integration')
            );
        }

        // Remove the shortcode registration
        remove_shortcode('straico_' . $shortcode->name);

        return true;
    }

    /**
     * Get all registered shortcodes.
     *
     * @since    1.0.0
     * @return   array    Array of shortcode configurations.
     */
    public function get_shortcodes() {
        return $this->db_manager->get_all_shortcodes();
    }

    /**
     * Get a specific shortcode configuration.
     *
     * @since    1.0.0
     * @param    int          $id    The shortcode ID.
     * @return   object|null        The shortcode configuration or null if not found.
     */
    public function get_shortcode($id) {
        return $this->db_manager->get_shortcode($id);
    }

    /**
     * Get a shortcode configuration by name.
     *
     * @since    1.0.0
     * @param    string       $name    The shortcode name.
     * @return   object|null          The shortcode configuration or null if not found.
     */
    public function get_shortcode_by_name($name) {
        return $this->db_manager->get_shortcode_by_name($name);
    }

    /**
     * Validate shortcode settings.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $settings    The settings to validate.
     * @return   bool|WP_Error         True if valid, error if invalid.
     */
    private function validate_shortcode_settings($settings) {
        $required_fields = array(
            'temperature' => array(
                'type' => 'float',
                'min' => 0,
                'max' => 2,
                'message' => __('Temperature must be between 0 and 2', 'straico-integration')
            ),
            'max_tokens' => array(
                'type' => 'int',
                'min' => 1,
                'message' => __('Max tokens must be greater than 0', 'straico-integration')
            )
        );

        foreach ($required_fields as $field => $rules) {
            if (!isset($settings[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(
                        /* translators: %s: field name */
                        __('Missing required field: %s', 'straico-integration'),
                        $field
                    )
                );
            }

            $value = $settings[$field];
            if ($rules['type'] === 'float') {
                $value = floatval($value);
                if ($value < $rules['min'] || $value > $rules['max']) {
                    return new WP_Error(
                        'invalid_field_value',
                        $rules['message']
                    );
                }
            } elseif ($rules['type'] === 'int') {
                $value = intval($value);
                if ($value < $rules['min']) {
                    return new WP_Error(
                        'invalid_field_value',
                        $rules['message']
                    );
                }
            }
        }

        return true;
    }

    /**
     * Get default shortcode settings.
     *
     * @since    1.0.0
     * @return   array    Default settings for shortcodes.
     */
    public function get_default_settings() {
        return array(
            'temperature' => 0.7,
            'max_tokens' => 2048,
            'display_reset' => true,
            'prompt_placeholder' => __('Enter your question...', 'straico-integration'),
            'submit_button_text' => __('Submit', 'straico-integration'),
            'reset_button_text' => __('Reset', 'straico-integration'),
            'loading_text' => __('Processing...', 'straico-integration'),
            'error_text' => __('An error occurred. Please try again.', 'straico-integration')
        );
    }

    /**
     * Format shortcode information for display.
     *
     * @since    1.0.0
     * @param    object    $shortcode    The shortcode object.
     * @return   array                   Formatted shortcode information.
     */
    public function format_shortcode_info($shortcode) {
        return array(
            'id' => $shortcode->id,
            'name' => $shortcode->name,
            'full_name' => 'straico_' . $shortcode->name,
            'agent_id' => $shortcode->agent_id,
            'settings' => $shortcode->settings,
            'created_at' => get_date_from_gmt($shortcode->created_at),
            'updated_at' => get_date_from_gmt($shortcode->updated_at)
        );
    }
}
