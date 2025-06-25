<?php
/**
 * Handles model-related API interactions with Straico.
 *
 * This class provides methods for retrieving information about available
 * LLM models and their capabilities.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_Models_API extends Straico_API_Base {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get the list of available models (v1).
     *
     * @since    1.0.0
     * @return   array|WP_Error    List of models or error on failure.
     */
    public function get_models() {
        error_log('Straico Models API - Fetching models list');
        
        // Test API key validity first
        if (!$this->has_api_key()) {
            error_log('Straico Models API - No API key set');
            return new WP_Error(
                'missing_api_key',
                __('API key is not set. Please configure it in the settings.', 'straico-integration')
            );
        }

        $response = $this->get('v1/models');
        
        if (is_wp_error($response)) {
            error_log('Straico Models API - Error fetching models: ' . $response->get_error_message());
            return $response;
        }

        // Validate response structure
        if (!isset($response['data'])) {
            error_log('Straico Models API - Invalid response structure (missing data)');
            return new WP_Error(
                'invalid_response',
                __('Invalid response from Straico API. Please try again.', 'straico-integration')
            );
        }

        // Validate chat models exist
        if (!isset($response['data']['chat']) || !is_array($response['data']['chat'])) {
            error_log('Straico Models API - No chat models in response');
            return new WP_Error(
                'no_chat_models',
                __('No chat models available. Please try again later.', 'straico-integration')
            );
        }

        // Validate image models structure if present
        if (isset($response['data']['image']) &&
            (!is_array($response['data']['image']) || empty($response['data']['image']) || !is_array($response['data']['image'][0]))
        ) {
            error_log('Straico Models API - Invalid image models structure in response');
            return new WP_Error(
                'invalid_image_models_structure',
                __('Invalid image models structure received from API.', 'straico-integration')
            );
        }

        error_log('Straico Models API - Successfully fetched models');
        if (isset($response['data']['chat'])) {
            error_log('Straico Models API - Found ' . count($response['data']['chat']) . ' chat models');
        }
        if (isset($response['data']['image'][0])) {
            error_log('Straico Models API - Found ' . count($response['data']['image'][0]) . ' image models');
        }
        
        return $response;
    }

    /**
     * Get chat models only.
     *
     * @since    1.0.0
     * @return   array|WP_Error    List of chat models or error on failure.
     */
    public function get_chat_models() {
        error_log('Straico Models API - Fetching chat models');
        
        $response = $this->get_models();

        if (is_wp_error($response)) {
            error_log('Straico Models API - Error fetching chat models: ' . $response->get_error_message());
            return $response;
        }

        if (!isset($response['data']['chat'])) {
            error_log('Straico Models API - Chat models not found in response');
            return new WP_Error(
                'invalid_response',
                __('Chat models not found in response', 'straico-integration')
            );
        }

        error_log('Straico Models API - Successfully fetched chat models');
        return $response['data']['chat'];
    }

    /**
     * Get image generation models only.
     *
     * @since    1.0.0
     * @return   array|WP_Error    List of image models or error on failure.
     */
    public function get_image_models() {
        error_log('Straico Models API - Fetching image models');
        
        $response = $this->get_models();

        if (is_wp_error($response)) {
            error_log('Straico Models API - Error fetching image models: ' . $response->get_error_message());
            return $response;
        }

        if (!isset($response['data']['image']) || !is_array($response['data']['image']) || empty($response['data']['image']) || !is_array($response['data']['image'][0])) {
            error_log('Straico Models API - Image models not found or invalid structure in response');
            return new WP_Error(
                'invalid_response',
                __('Image models not found or invalid structure in response', 'straico-integration')
            );
        }

        error_log('Straico Models API - Successfully fetched image models');
        return $response['data']['image'][0]; // Access the first element which is the array of image models
    }

    /**
     * Compare two lists of models to detect changes.
     *
     * @since    1.0.0
     * @param    array    $old_models    Previous list of models.
     * @param    array    $new_models    Current list of models.
     * @return   array                   Array containing added and removed models.
     */
    public function detect_model_changes($old_models, $new_models) {
        error_log('Straico Models API - Detecting model changes');
        error_log('Old models: ' . print_r($old_models, true));
        error_log('New models: ' . print_r($new_models, true));

        if (!isset($old_models['data']) || !isset($new_models['data'])) {
            error_log('Straico Models API - Invalid model data structure');
            return array('added' => array(), 'removed' => array());
        }

        $changes = array(
            'added' => array(),
            'removed' => array()
        );

        // Check chat models
        $old_chat = isset($old_models['data']['chat']) ? $old_models['data']['chat'] : array();
        $new_chat = isset($new_models['data']['chat']) ? $new_models['data']['chat'] : array();

        $old_chat_models = array_column($old_chat, 'model');
        $new_chat_models = array_column($new_chat, 'model');

        $added_chat = array_diff($new_chat_models, $old_chat_models);
        $removed_chat = array_diff($old_chat_models, $new_chat_models);

        foreach ($added_chat as $model) {
            $model_data = $this->find_model_by_id($new_chat, $model);
            if ($model_data) {
                $changes['added'][] = array(
                    'type' => 'chat',
                    'name' => $model_data['name'],
                    'model' => $model_data['model']
                );
            }
        }

        foreach ($removed_chat as $model) {
            $model_data = $this->find_model_by_id($old_chat, $model);
            if ($model_data) {
                $changes['removed'][] = array(
                    'type' => 'chat',
                    'name' => $model_data['name'],
                    'model' => $model_data['model']
                );
            }
        }

        // Check image models
        $old_image_data = isset($old_models['data']['image']) && is_array($old_models['data']['image']) && !empty($old_models['data']['image']) && is_array($old_models['data']['image'][0]) ? $old_models['data']['image'][0] : array();
        $new_image_data = isset($new_models['data']['image']) && is_array($new_models['data']['image']) && !empty($new_models['data']['image']) && is_array($new_models['data']['image'][0]) ? $new_models['data']['image'][0] : array();

        $old_image_models = array_column($old_image_data, 'model');
        $new_image_models = array_column($new_image_data, 'model');

        $added_image = array_diff($new_image_models, $old_image_models);
        $removed_image = array_diff($old_image_models, $new_image_models);

        foreach ($added_image as $model) {
            $model_data = $this->find_model_by_id($new_image_data, $model);
            if ($model_data) {
                $changes['added'][] = array(
                    'type' => 'image',
                    'name' => $model_data['name'],
                    'model' => $model_data['model']
                );
            }
        }

        foreach ($removed_image as $model) {
            $model_data = $this->find_model_by_id($old_image_data, $model);
            if ($model_data) {
                $changes['removed'][] = array(
                    'type' => 'image',
                    'name' => $model_data['name'],
                    'model' => $model_data['model']
                );
            }
        }

        error_log('Straico Models API - Changes detected: ' . print_r($changes, true));
        return $changes;
    }

    /**
     * Find a model in a list by its ID.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $models    List of models to search.
     * @param    string    $model_id  The model ID to find.
     * @return   array|null          The model data or null if not found.
     */
    private function find_model_by_id($models, $model_id) {
        foreach ($models as $model) {
            if ($model['model'] === $model_id) {
                return $model;
            }
        }
        error_log('Straico Models API - Model not found: ' . $model_id);
        return null;
    }

    /**
     * Send a model changes notification email.
     *
     * @since    1.0.0
     * @param    array    $changes      Array of added and removed models.
     * @param    array    $recipients   Email addresses to notify.
     * @return   bool                   Whether the email was sent successfully.
     */
    public function send_model_changes_notification($changes, $recipients) {
        error_log('Straico Models API - Sending model changes notification');
        error_log('Changes: ' . print_r($changes, true));
        error_log('Recipients: ' . print_r($recipients, true));

        if (empty($recipients) || (empty($changes['added']) && empty($changes['removed']))) {
            error_log('Straico Models API - No recipients or changes to notify');
            return false;
        }

        $subject = __('[Straico] Model Changes Detected', 'straico-integration');

        $message = __('The following changes have been detected in available Straico models:', 'straico-integration') . "\n\n";

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

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $result = wp_mail($recipients, $subject, $message, $headers);
        error_log('Straico Models API - Notification email sent: ' . ($result ? 'success' : 'failed'));
        
        return $result;
    }

    /**
     * Format model information for display.
     *
     * @since    1.0.0
     * @param    array    $model    Raw model information from API.
     * @return   array             Formatted model information.
     */
    public function format_model_info($model) {
        error_log('Straico Models API - Formatting model info: ' . print_r($model, true));

        $formatted = array(
            'name' => $model['name'],
            'model_id' => $model['model'],
            'type' => isset($model['pricing']['coins']) ? 'chat' : 'image'
        );

        if ($formatted['type'] === 'chat') {
            $formatted['word_limit'] = number_format($model['word_limit']);
            $formatted['max_output'] = number_format($model['max_output']);
            $formatted['price'] = sprintf(
                /* translators: %s: number of coins */
                __('%s coins per 100 words', 'straico-integration'),
                number_format($model['pricing']['coins'], 2)
            );
        } else {
            $sizes = array();
            foreach (['square', 'landscape', 'portrait'] as $size) {
                if (isset($model['pricing'][$size])) {
                    $sizes[] = sprintf(
                        '%s (%s): %s coins',
                        ucfirst($size),
                        $model['pricing'][$size]['size'],
                        number_format($model['pricing'][$size]['coins'])
                    );
                }
            }
            $formatted['sizes'] = $sizes;
        }

        error_log('Straico Models API - Formatted model info: ' . print_r($formatted, true));
        return $formatted;
    }

    /**
     * Validate selected models against available models.
     *
     * @since    1.0.0
     * @param    array    $selected_models    Array of selected model IDs.
     * @return   bool|WP_Error               True if valid, WP_Error if invalid.
     */
    public function validate_selected_models($selected_models) {
        error_log('Straico Models API - Validating selected models: ' . print_r($selected_models, true));

        if (empty($selected_models)) {
            error_log('Straico Models API - No models selected');
            return new WP_Error(
                'no_models_selected',
                __('Please select at least one model.', 'straico-integration')
            );
        }

        if (count($selected_models) > 4) {
            error_log('Straico Models API - Too many models selected');
            return new WP_Error(
                'too_many_models',
                __('Maximum of 4 models allowed.', 'straico-integration')
            );
        }

        // Get available models
        $response = $this->get_models();
        if (is_wp_error($response)) {
            error_log('Straico Models API - Error fetching models for validation: ' . $response->get_error_message());
            return $response;
        }

        if (!isset($response['data']['chat']) || !is_array($response['data']['chat'])) {
            error_log('Straico Models API - No chat models available for validation');
            return new WP_Error(
                'no_chat_models',
                __('No chat models available. Please try again later.', 'straico-integration')
            );
        }

        // Get list of available model IDs
        $available_models = array_column($response['data']['chat'], 'model');

        // Check each selected model
        foreach ($selected_models as $model) {
            if (!in_array($model, $available_models)) {
                error_log('Straico Models API - Invalid model selected: ' . $model);
                return new WP_Error(
                    'invalid_model',
                    sprintf(
                        /* translators: %s: model ID */
                        __('Invalid model selected: %s', 'straico-integration'),
                        $model
                    )
                );
            }
        }

        error_log('Straico Models API - Selected models validated successfully');
        return true;
    }
}
