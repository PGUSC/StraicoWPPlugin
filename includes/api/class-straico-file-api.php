<?php
/**
 * Handles file upload API interactions with Straico.
 *
 * This class provides methods for uploading files to be used with
 * various Straico API features like RAG creation and prompt completion.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_File_API extends Straico_API_Base {

    /**
     * Maximum file size in bytes (25MB).
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_file_size    Maximum allowed file size.
     */
    private $max_file_size = 26214400; // 25 * 1024 * 1024

    /**
     * Allowed file types and their MIME types.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $allowed_types    Array of allowed file extensions and MIME types.
     */
    private $allowed_types = array(
        'pdf'  => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'mp3'  => 'audio/mpeg',
        'mp4'  => 'video/mp4',
        'html' => 'text/html',
        'csv'  => 'text/csv',
        'json' => 'application/json',
        'py'   => 'text/x-python',
        'php'  => 'text/x-php',
        'js'   => 'application/javascript',
        'css'  => 'text/css',
        'cs'   => 'text/x-csharp',
        'swift'=> 'text/x-swift',
        'kt'   => 'text/x-kotlin',
        'xml'  => 'application/xml',
        'ts'   => 'application/typescript'
    );

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Upload a file to the Straico API.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the file to upload.
     * @return   array|WP_Error          The API response or error on failure.
     */
    public function upload_file($file_path) {
        // Validate file existence
        if (!file_exists($file_path)) {
            return new WP_Error(
                'file_not_found',
                sprintf(
                    /* translators: %s: file path */
                    __('File not found: %s', 'straico-integration'),
                    $file_path
                )
            );
        }

        // Validate file size
        $file_size = filesize($file_path);
        if ($file_size > $this->max_file_size) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    /* translators: %s: file name */
                    __('File too large: %s (maximum size is 25MB)', 'straico-integration'),
                    basename($file_path)
                )
            );
        }

        // Get original filename and extension
        $original_name = basename($file_path);
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        // Validate file type
        if (!array_key_exists($extension, $this->allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                sprintf(
                    /* translators: 1: file name 2: allowed file types */
                    __('Invalid file type for %1$s. Allowed types: %2$s', 'straico-integration'),
                    $original_name,
                    implode(', ', array_keys($this->allowed_types))
                )
            );
        }

        // Prepare multipart form data
        $boundary = wp_generate_password(24);
        $payload = '';

        // Add the file content with original filename
        $payload .= '--' . $boundary . "\r\n";
        $payload .= 'Content-Disposition: form-data; name="file"; filename="' . $original_name . '"' . "\r\n";
        $payload .= 'Content-Type: ' . $this->allowed_types[$extension] . "\r\n\r\n";
        $payload .= file_get_contents($file_path) . "\r\n";

        // Add closing boundary
        $payload .= '--' . $boundary . '--';

        // Set headers
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
            'Content-Length' => strlen($payload)
        );

        // Make the request
        $response = wp_remote_post($this->build_url('v0/file/upload'), array(
            'headers' => $headers,
            'body' => $payload,
            'timeout' => 60
        ));

        return $this->handle_response($response);
    }

    /**
     * Upload multiple files to the Straico API.
     *
     * @since    1.0.0
     * @param    array     $files    Array of file paths to upload.
     * @return   array               Array of upload results.
     */
    public function upload_multiple_files($files) {
        if (!is_array($files)) {
            return array(
                'success' => false,
                'error' => __('Files parameter must be an array', 'straico-integration')
            );
        }

        $results = array(
            'success' => true,
            'files' => array(),
            'errors' => array()
        );

        foreach ($files as $file_path) {
            $response = $this->upload_file($file_path);

            if (is_wp_error($response)) {
                $results['success'] = false;
                $results['errors'][] = array(
                    'file' => basename($file_path),
                    'error' => $response->get_error_message()
                );
            } else {
                $results['files'][] = array(
                    'file' => basename($file_path),
                    'url' => $response['data']['url']
                );
            }
        }

        return $results;
    }

    /**
     * Check if a file type is allowed.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the file to check.
     * @return   bool                    Whether the file type is allowed.
     */
    public function is_file_type_allowed($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        return array_key_exists($extension, $this->allowed_types);
    }

    /**
     * Get the list of allowed file types.
     *
     * @since    1.0.0
     * @return   array    Array of allowed file extensions.
     */
    public function get_allowed_file_types() {
        return array_keys($this->allowed_types);
    }

    /**
     * Get the maximum allowed file size.
     *
     * @since    1.0.0
     * @return   int    Maximum file size in bytes.
     */
    public function get_max_file_size() {
        return $this->max_file_size;
    }

    /**
     * Format file size for display.
     *
     * @since    1.0.0
     * @param    int       $size    Size in bytes.
     * @return   string             Formatted size string.
     */
    public function format_file_size($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }

    /**
     * Validate a file before upload.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the file to validate.
     * @return   bool|WP_Error           True if valid, WP_Error if invalid.
     */
    public function validate_file($file_path, $original_name = null) {
        // Check file existence
        if (!file_exists($file_path)) {
            return new WP_Error(
                'file_not_found',
                sprintf(
                    /* translators: %s: file path */
                    __('File not found: %s', 'straico-integration'),
                    $file_path
                )
            );
        }

        // Check file size
        $file_size = filesize($file_path);
        if ($file_size > $this->max_file_size) {
            return new WP_Error(
                'file_too_large',
                sprintf(
                    /* translators: 1: file name 2: maximum size */
                    __('File %1$s is too large. Maximum size allowed is %2$s', 'straico-integration'),
                    basename($file_path),
                    $this->format_file_size($this->max_file_size)
                )
            );
        }

        // Check file type using original name if provided
        $extension = strtolower(pathinfo($original_name ?? $file_path, PATHINFO_EXTENSION));
        if (!array_key_exists($extension, $this->allowed_types)) {
            return new WP_Error(
                'invalid_file_type',
                sprintf(
                    /* translators: 1: file name 2: allowed file types */
                    __('Invalid file type for %1$s. Allowed types: %2$s', 'straico-integration'),
                    basename($file_path),
                    implode(', ', array_keys($this->allowed_types))
                )
            );
        }

        // Check if file is readable
        if (!is_readable($file_path)) {
            return new WP_Error(
                'file_not_readable',
                sprintf(
                    /* translators: %s: file name */
                    __('File is not readable: %s', 'straico-integration'),
                    basename($file_path)
                )
            );
        }

        return true;
    }
}
