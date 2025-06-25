<?php
/**
 * Base class for Straico API interactions.
 *
 * This class provides common functionality for making API requests to the
 * Straico service. All specific API endpoint classes will extend this base class.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_API_Base {

    /**
     * The base URL for the Straico API.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_base_url    The base URL for API requests.
     */
    protected $api_base_url = 'https://api.straico.com';

    /**
     * The API key for authentication.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_key    The API key.
     */
    protected $api_key;

    /**
     * The options class instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Straico_Options    $options    Handles plugin options.
     */
    protected $options;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->options = new Straico_Options();
        $this->api_key = $this->options->get_api_key();
        // error_log('Straico API Base - Initialized with API key: ' . (empty($this->api_key) ? 'Not Set' : 'Set')); // Removed to reduce log noise
    }

    /**
     * Make a GET request to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      Optional query parameters.
     * @return   array|WP_Error         The API response or WP_Error on failure.
     */
    protected function get($endpoint, $params = array()) {
        $url = $this->build_url($endpoint, $params);
        error_log('Straico API Base - GET Request URL: ' . $url);
        
        $args = array(
            'headers' => $this->get_headers(),
            'timeout' => 30
        );
        // error_log('Straico API Base - GET Request Args: ' . print_r($args, true)); // Removed to prevent API key exposure
        
        $response = wp_remote_get($url, $args);
        return $this->handle_response($response);
    }

    /**
     * Make a POST request to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $data        The data to send.
     * @param    array     $params      Optional query parameters.
     * @return   array|WP_Error         The API response or WP_Error on failure.
     */
    protected function post($endpoint, $data = array(), $params = array()) {
        $url = $this->build_url($endpoint, $params);
        error_log('Straico API Base - POST Request URL: ' . $url);
        
        // Only encode if not already a JSON string
        $body = is_string($data) ? $data : wp_json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Straico API Base - JSON Encode Error: ' . json_last_error_msg());
            return new WP_Error('json_encode_error', 'Failed to encode request data');
        }
        
        $args = array(
            'headers' => $this->get_headers(),
            'body' => $body,
            'timeout' => 30
        );
        // error_log('Straico API Base - POST Request Args: ' . print_r($args, true)); // Removed to prevent API key exposure
        
        $response = wp_remote_post($url, $args);
        return $this->handle_response($response);
    }

    /**
     * Make a PUT request to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $data        The data to send.
     * @param    array     $params      Optional query parameters.
     * @return   array|WP_Error         The API response or WP_Error on failure.
     */
    protected function put($endpoint, $data = array(), $params = array()) {
        $url = $this->build_url($endpoint, $params);
        error_log('Straico API Base - PUT Request URL: ' . $url);
        
        // Only encode if not already a JSON string
        $body = is_string($data) ? $data : wp_json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Straico API Base - JSON Encode Error: ' . json_last_error_msg());
            return new WP_Error('json_encode_error', 'Failed to encode request data');
        }
        
        $args = array(
            'method' => 'PUT',
            'headers' => $this->get_headers(),
            'body' => $body,
            'timeout' => 30
        );
        // error_log('Straico API Base - PUT Request Args: ' . print_r($args, true)); // Removed to prevent API key exposure
        
        $response = wp_remote_request($url, $args);
        return $this->handle_response($response);
    }

    /**
     * Make a DELETE request to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      Optional query parameters.
     * @return   array|WP_Error         The API response or WP_Error on failure.
     */
    protected function delete($endpoint, $params = array()) {
        $url = $this->build_url($endpoint, $params);
        error_log('Straico API Base - DELETE Request URL: ' . $url);
        
        $args = array(
            'method' => 'DELETE',
            'headers' => $this->get_headers(),
            'timeout' => 30
        );
        // error_log('Straico API Base - DELETE Request Args: ' . print_r($args, true)); // Removed to prevent API key exposure
        
        $response = wp_remote_request($url, $args);
        return $this->handle_response($response);
    }

    /**
     * Build the full URL for an API request.
     *
     * @since    1.0.0
     * @access   protected
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      Optional query parameters.
     * @return   string                 The complete URL.
     */
    protected function build_url($endpoint, $params = array()) {
        $url = trailingslashit($this->api_base_url) . ltrim($endpoint, '/');
        
        if (!empty($params)) {
            $url = add_query_arg($params, $url);
        }
        
        error_log('Straico API Base - Built URL: ' . $url);
        return $url;
    }

    /**
     * Get the headers for API requests.
     *
     * @since    1.0.0
     * @access   protected
     * @return   array    The request headers.
     */
    protected function get_headers() {
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
        // error_log('Straico API Base - Request Headers: ' . print_r($headers, true)); // Removed to prevent API key exposure
        return $headers;
    }

    /**
     * Handle the API response.
     *
     * @since    1.0.0
     * @access   protected
     * @param    array|WP_Error    $response    The API response.
     * @return   array|WP_Error                 The processed response or error.
     */
    protected function handle_response($response) {
        if (is_wp_error($response)) {
            error_log('Straico API Base - WP Error: ' . $response->get_error_message());
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('Straico API Base - Response Code: ' . $code);
        // error_log('Straico API Base - Response Body: ' . $body); // Removed to reduce log size

        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Straico API Base - JSON Decode Error: ' . json_last_error_msg());
            return new WP_Error('json_decode_error', 'Failed to decode API response');
        }

        if ($code >= 400) {
            $error_message = isset($data['error']) ? $data['error'] : 
                           (isset($data['message']) ? $data['message'] : 
                           __('Unknown error occurred', 'straico-integration'));
            
            error_log('Straico API Base - Error Response: ' . $error_message);
            return new WP_Error('api_error', $error_message, array(
                'status' => $code,
                'response' => $data
            ));
        }

        return $data;
    }

    /**
     * Check if the API key is set.
     *
     * @since    1.0.0
     * @return   bool    Whether the API key is set.
     */
    public function has_api_key() {
        return !empty($this->api_key);
    }

    /**
     * Handle file uploads to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the file to upload.
     * @return   array|WP_Error          The API response or error.
     */
    protected function upload_file($file_path) {
        if (!file_exists($file_path)) {
            error_log('Straico API Base - File Upload Error: File not found at ' . $file_path);
            return new WP_Error('file_not_found', __('File not found', 'straico-integration'));
        }

        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name)['type'];
        error_log('Straico API Base - Uploading file: ' . $file_name . ' (' . $file_type . ')');

        $boundary = wp_generate_password(24);
        $payload = '';

        // Add the file content
        $payload .= '--' . $boundary;
        $payload .= "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . $file_name . '"' . "\r\n";
        $payload .= 'Content-Type: ' . $file_type . "\r\n\r\n";
        $payload .= file_get_contents($file_path);
        $payload .= "\r\n";

        // Add the closing boundary
        $payload .= '--' . $boundary . '--';

        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Content-Length' => strlen($payload)
        );

        $url = $this->build_url('v0/file/upload');
        error_log('Straico API Base - File Upload URL: ' . $url);

        $args = array(
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 60
        );
        
        $response = wp_remote_post($url, $args);
        return $this->handle_response($response);
    }
}
