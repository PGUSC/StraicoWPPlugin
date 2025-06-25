<?php
/**
 * Handles all security-related functionality for the plugin.
 *
 * This class provides methods for nonce verification, input sanitization,
 * capability checks, and other security measures.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes
 */

class Straico_Security {

    /**
     * The nonce action prefix for the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $nonce_action    The nonce action prefix.
     */
    private $nonce_action = 'straico_integration_';

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Add any initialization code here
    }

    /**
     * Verify a nonce.
     *
     * @since    1.0.0
     * @param    string    $nonce       The nonce to verify.
     * @param    string    $action      The action to verify the nonce against.
     * @return   bool                   Whether the nonce is valid.
     */
    public function verify_nonce($nonce, $action) {
        return wp_verify_nonce($nonce, $this->nonce_action . $action);
    }

    /**
     * Create a nonce field.
     *
     * @since    1.0.0
     * @param    string    $action      The action to create the nonce for.
     * @param    bool      $referer     Whether to include the referer field.
     * @return   string                 The nonce field HTML.
     */
    public function nonce_field($action, $referer = true) {
        return wp_nonce_field($this->nonce_action . $action, '_wpnonce', $referer, false);
    }

    /**
     * Check if the current user has the required capability.
     *
     * @since    1.0.0
     * @param    string    $capability    The capability to check for.
     * @return   bool                     Whether the user has the capability.
     */
    public function current_user_can($capability) {
        return current_user_can($capability);
    }

    /**
     * Sanitize text input.
     *
     * @since    1.0.0
     * @param    string    $input    The input to sanitize.
     * @return   string              The sanitized input.
     */
    public function sanitize_text_field($input) {
        return sanitize_text_field($input);
    }

    /**
     * Sanitize email input.
     *
     * @since    1.0.0
     * @param    string    $email    The email to sanitize.
     * @return   string              The sanitized email.
     */
    public function sanitize_email($email) {
        return sanitize_email($email);
    }

    /**
     * Sanitize URL input.
     *
     * @since    1.0.0
     * @param    string    $url      The URL to sanitize.
     * @return   string              The sanitized URL.
     */
    public function sanitize_url($url) {
        return esc_url_raw($url);
    }

    /**
     * Sanitize textarea input.
     *
     * @since    1.0.0
     * @param    string    $input    The input to sanitize.
     * @return   string              The sanitized input.
     */
    public function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }

    /**
     * Escape HTML output.
     *
     * @since    1.0.0
     * @param    string    $input    The input to escape.
     * @return   string              The escaped input.
     */
    public function escape_html($input) {
        return esc_html($input);
    }

    /**
     * Escape HTML attributes.
     *
     * @since    1.0.0
     * @param    string    $input    The input to escape.
     * @return   string              The escaped input.
     */
    public function escape_attr($input) {
        return esc_attr($input);
    }

    /**
     * Encrypt sensitive data.
     *
     * @since    1.0.0
     * @param    string    $data       The data to encrypt.
     * @param    string    $key        The encryption key.
     * @return   string                The encrypted data.
     */
    public function encrypt_data($data, $key) {
        if (empty($data)) {
            return '';
        }

        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data.
     *
     * @since    1.0.0
     * @param    string    $data       The data to decrypt.
     * @param    string    $key        The encryption key.
     * @return   string                The decrypted data.
     */
    public function decrypt_data($data, $key) {
        if (empty($data)) {
            return '';
        }

        $data = base64_decode($data);
        $method = 'aes-256-cbc';
        $ivlen = openssl_cipher_iv_length($method);
        
        $iv = substr($data, 0, $ivlen);
        $encrypted = substr($data, $ivlen);
        
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    /**
     * Generate a secure random string.
     *
     * @since    1.0.0
     * @param    int       $length    The length of the string to generate.
     * @return   string               The generated string.
     */
    public function generate_random_string($length = 32) {
        return wp_generate_password($length, true, true);
    }

    /**
     * Validate and sanitize API key.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key to validate.
     * @return   string                The sanitized API key or empty string if invalid.
     */
    public function validate_api_key($api_key) {
        // Remove any whitespace
        $api_key = trim($api_key);
        
        // Log validation attempt (without showing the full key)
        $key_preview = substr($api_key, 0, 4) . '...' . substr($api_key, -4);
        error_log('Straico Security - Validating API key: ' . $key_preview);
        
        // Basic validation - ensure it's not empty
        if (empty($api_key)) {
            error_log('Straico Security - API key validation failed: Empty key');
            return '';
        }

        // Validate key format - allow alphanumeric, dots, underscores, and hyphens
        // This matches the standard format of Straico API keys
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $api_key)) {
            error_log('Straico Security - API key validation failed: Invalid format');
            return '';
        }

        // Validate key length (Straico API keys are typically 40+ characters)
        if (strlen($api_key) < 40) {
            error_log('Straico Security - API key validation failed: Key too short');
            return '';
        }

        error_log('Straico Security - API key validation passed');
        return $api_key;
    }

    /**
     * Test API key validity by making a test request.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key to test.
     * @return   bool|WP_Error         True if valid, WP_Error if invalid.
     */
    public function test_api_key($api_key) {
        error_log('Straico Security - Testing API key validity');

        // First validate the format
        $validated_key = $this->validate_api_key($api_key);
        if (empty($validated_key)) {
            error_log('Straico Security - API key format validation failed');
            return new WP_Error(
                'invalid_api_key_format',
                __('Invalid API key format. Please check your API key and try again.', 'straico-integration')
            );
        }

        // Make a test request to the API
        $response = wp_remote_get('https://api.straico.com/v0/user', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $validated_key,
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('Straico Security - API key test failed: ' . $response->get_error_message());
            return new WP_Error(
                'api_connection_error',
                __('Could not connect to Straico API. Please try again later.', 'straico-integration')
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Straico Security - API key test failed: HTTP ' . $response_code);
            return new WP_Error(
                'invalid_api_key',
                __('Invalid API key. Please check your API key and try again.', 'straico-integration')
            );
        }

        error_log('Straico Security - API key test passed');
        return true;
    }
}
