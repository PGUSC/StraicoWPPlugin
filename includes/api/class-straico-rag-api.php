<?php
/**
 * Handles RAG-related API interactions with Straico.
 *
 * This class provides methods for managing RAGs (Retrieval-Augmented Generation),
 * including creation, listing, retrieval, and deletion.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_RAG_API extends Straico_API_Base {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new RAG.
     *
     * @since    1.0.0
     * @param    string    $name              The name of the RAG.
     * @param    string    $description       The description of the RAG.
     * @param    array     $files             Array of file paths to upload.
     * @param    string    $chunking_method   Optional. The chunking method to use.
     * @param    array     $chunking_options  Optional. Additional chunking options.
     * @return   array|WP_Error              The API response or error on failure.
     */
    public function create_rag($name, $description, $files, $chunking_method = 'fixed_size', $chunking_options = array()) {
        // Check if API key is set
        if (!$this->has_api_key()) {
            return new WP_Error(
                'missing_api_key',
                __('Straico API key is not configured. Please set it in the plugin settings.', 'straico-integration')
            );
        }

        // Verify files exist and are readable
        foreach ($files as $file_path) {
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
            if (!is_readable($file_path)) {
                return new WP_Error(
                    'file_not_readable',
                    sprintf(
                        /* translators: %s: file path */
                        __('File is not readable: %s', 'straico-integration'),
                        $file_path
                    )
                );
            }
        }

        // Function to clean up temporary files
        $cleanup_temp_files = function() use (&$files) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }
        };

        // Register shutdown function to ensure cleanup
        register_shutdown_function($cleanup_temp_files);

        try {
            // Prepare the request body
            $body = array(
                'name' => $name,
                'description' => $description,
                'chunking_method' => $chunking_method
            );

            // Set common chunking options at the top level
            $body['chunk_size'] = isset($chunking_options['chunk_size']) ? intval($chunking_options['chunk_size']) : 1000;
            $body['chunk_overlap'] = isset($chunking_options['chunk_overlap']) ? intval($chunking_options['chunk_overlap']) : 50;

            // Handle method-specific options
            if ($chunking_method === 'fixed_size') {
                $body['separator'] = isset($chunking_options['separator']) ? str_replace('\\n', "\n", $chunking_options['separator']) : "\n";
            } else if ($chunking_method === 'recursive') {
                // Handle separators for recursive chunking
                if (isset($chunking_options['separators'])) {
                    $separators = is_array($chunking_options['separators']) 
                        ? $chunking_options['separators'] 
                        : explode("\n", $chunking_options['separators']);
                    
                    // Clean up separators
                    $separators = array_values(array_filter(array_map(function($separator) {
                        return str_replace('\\n', "\n", trim($separator));
                    }, $separators)));
                } else {
                    // Use defaults if not set
                    $separators = ["\n\n", "\n", ". "];
                }
                
                // Always set separators in body
                $body['separators'] = $separators;
                
                // Debug logs
                error_log('Recursive chunking method detected');
                error_log('Chunk size: ' . $body['chunk_size']);
                error_log('Chunk overlap: ' . $body['chunk_overlap']);
                error_log('Separators: ' . print_r($body['separators'], true));
            } else {
                // For other methods, add remaining options as is
                foreach ($chunking_options as $key => $value) {
                    if (!in_array($key, ['chunk_size', 'chunk_overlap'])) {
                        $body[$key] = $value;
                    }
                }
            }

            // Generate boundary
            $boundary = wp_generate_password(24);

            // Build multipart body
            $payload = '';
            
            // Debug log the request body and files
            error_log('Straico RAG API Request Body: ' . print_r($body, true));
            error_log('Straico RAG API Files: ' . print_r($files, true));
            error_log('Straico RAG API Files Exist Check:');
            foreach ($files as $file_path) {
                error_log("File {$file_path}: exists=" . (file_exists($file_path) ? 'yes' : 'no') . 
                         ", readable=" . (is_readable($file_path) ? 'yes' : 'no') . 
                         ", size=" . (file_exists($file_path) ? filesize($file_path) : 'N/A'));
            }

            // Add required fields first
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Disposition: form-data; name=\"name\"\r\n\r\n";
            $payload .= $name . "\r\n";

            $payload .= "--$boundary\r\n";
            $payload .= "Content-Disposition: form-data; name=\"description\"\r\n\r\n";
            $payload .= $description . "\r\n";

            $payload .= "--$boundary\r\n";
            $payload .= "Content-Disposition: form-data; name=\"chunking_method\"\r\n\r\n";
            $payload .= $chunking_method . "\r\n";

            // Add method-specific parameters
            switch ($chunking_method) {
                case 'fixed_size':
                    // Add chunk_size
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_size\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_size']) ? intval($chunking_options['chunk_size']) : 1000) . "\r\n";

                    // Add chunk_overlap
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_overlap\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_overlap']) ? intval($chunking_options['chunk_overlap']) : 50) . "\r\n";

                    // Add single separator (no array)
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"separator\"\r\n\r\n";
                    $payload .= (isset($chunking_options['separator']) ? str_replace('\\n', "\n", $chunking_options['separator']) : "\n") . "\r\n";
                    break;

                case 'recursive':
                    // Add chunk_size
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_size\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_size']) ? intval($chunking_options['chunk_size']) : 1000) . "\r\n";

                    // Add chunk_overlap
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_overlap\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_overlap']) ? intval($chunking_options['chunk_overlap']) : 50) . "\r\n";

                    // Add separators as array
                    $separators = isset($chunking_options['separators']) ? $chunking_options['separators'] : ["\n\n", "\n", ". "];
                    if (is_string($separators)) {
                        $separators = array_filter(array_map('trim', explode("\n", $separators)));
                    }
                    if (empty($separators)) {
                        $separators = ["\n\n", "\n", ". "];
                    }
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"separators\"\r\n\r\n";
                    $payload .= json_encode($separators) . "\r\n";
                    break;

                case 'markdown':
                case 'python':
                    // Add chunk_size
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_size\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_size']) ? intval($chunking_options['chunk_size']) : 1000) . "\r\n";

                    // Add chunk_overlap
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"chunk_overlap\"\r\n\r\n";
                    $payload .= (isset($chunking_options['chunk_overlap']) ? intval($chunking_options['chunk_overlap']) : 50) . "\r\n";
                    break;

                case 'semantic':
                    // Add breakpoint_threshold_type
                    if (isset($chunking_options['breakpoint_threshold_type'])) {
                        $payload .= "--$boundary\r\n";
                        $payload .= "Content-Disposition: form-data; name=\"breakpoint_threshold_type\"\r\n\r\n";
                        $payload .= $chunking_options['breakpoint_threshold_type'] . "\r\n";
                    }

                    // Add buffer_size
                    $payload .= "--$boundary\r\n";
                    $payload .= "Content-Disposition: form-data; name=\"buffer_size\"\r\n\r\n";
                    $payload .= (isset($chunking_options['buffer_size']) ? intval($chunking_options['buffer_size']) : 100) . "\r\n";
                    break;
            }

            // Debug logging
            error_log('Chunking method: ' . $chunking_method);
            error_log('Chunking options: ' . print_r($chunking_options, true));

            // Debug log the final payload
            error_log('Straico RAG API Request Payload: ' . $payload);

            // Add files
            foreach ($files as $file_path) {
                $file_name = basename($file_path);
                $mime_type = wp_check_filetype($file_name)['type'];
                $file_content = file_get_contents($file_path, false, null, 0, null);
                
                $payload .= "--$boundary\r\n";
                $payload .= "Content-Disposition: form-data; name=\"files\"; filename=\"$file_name\"\r\n";
                $payload .= "Content-Type: $mime_type\r\n";
                $payload .= "Content-Transfer-Encoding: binary\r\n\r\n";
                $payload .= $file_content;
                $payload .= "\r\n";
            }
            
            // Add closing boundary
            $payload .= "--$boundary--\r\n";

            // Set up the request
            $args = array(
                'method' => 'POST',
                'timeout' => 300,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                    'Accept' => 'application/json'
                ),
                'body' => $payload,
                'sslverify' => true,
                '_redirection' => 5
            );

            // Log request details
            error_log('Straico RAG API Request URL: ' . $this->build_url('v0/rag'));
            error_log('Straico RAG API Request Headers: ' . print_r($args['headers'], true));
            error_log('Straico RAG API Has API Key: ' . ($this->api_key ? 'yes' : 'no'));
            
            // Make the request
            $response = wp_remote_post($this->build_url('v0/rag'), $args);

            // Handle response
            if (is_wp_error($response)) {
                error_log('Straico RAG API Error: ' . $response->get_error_message());
                error_log('Straico RAG API Error Data: ' . print_r($response->get_error_data(), true));
                return $response;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            
            error_log('Straico RAG API Response Code: ' . $response_code);
            error_log('Straico RAG API Response Headers: ' . print_r($response_headers, true));
            error_log('Straico RAG API Response Body: ' . $response_body);

            $result = json_decode($response_body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_Error(
                    'json_decode_error',
                    sprintf(
                        /* translators: 1: json error message 2: raw response */
                        __('Failed to decode API response: %1$s. Raw response: %2$s', 'straico-integration'),
                        json_last_error_msg(),
                        $response_body
                    )
                );
            }

            if ($response_code !== 200 && $response_code !== 201) {
                $error_message = isset($result['message']) ? $result['message'] : __('Unknown API error', 'straico-integration');
                return new WP_Error(
                    'api_error',
                    sprintf(
                        /* translators: 1: http code 2: error message */
                        __('API error (HTTP %1$d): %2$s', 'straico-integration'),
                        $response_code,
                        $error_message
                    )
                );
            }

            return $result;
        } finally {
            // Ensure cleanup happens in all cases
            $cleanup_temp_files();
        }
    }

    /**
     * Get a list of all RAGs for the current user.
     *
     * @since    1.0.0
     * @return   array|WP_Error    List of RAGs or error on failure.
     */
    public function list_rags() {
        return $this->get('v0/rag/user');
    }

    /**
     * Get details of a specific RAG.
     *
     * @since    1.0.0
     * @param    string    $rag_id    The ID of the RAG.
     * @return   array|WP_Error       RAG details or error on failure.
     */
    public function get_rag_by_id($rag_id) {
        return $this->get('v0/rag/' . urlencode($rag_id));
    }

    /**
     * Delete a RAG.
     *
     * @since    1.0.0
     * @param    string    $rag_id    The ID of the RAG to delete.
     * @return   array|WP_Error       The API response or error on failure.
     */
    public function delete_rag($rag_id) {
        return $this->delete('v0/rag/' . urlencode($rag_id));
    }

    /**
     * Submit a prompt to a RAG.
     *
     * @since    1.0.0
     * @param    string    $rag_id          The ID of the RAG.
     * @param    string    $prompt          The prompt text.
     * @param    string    $model           The model to use.
     * @param    array     $search_options  Optional. Search options for the RAG.
     * @return   array|WP_Error             The API response or error on failure.
     */
    public function prompt_rag($rag_id, $prompt, $model, $search_options = array()) {
        $data = array(
            'prompt' => $prompt,
            'model' => $model
        );

        // Add search options if provided
        if (!empty($search_options)) {
            $valid_options = array(
                'search_type',
                'k',
                'fetch_k',
                'lambda_mult',
                'score_threshold'
            );

            foreach ($valid_options as $option) {
                if (isset($search_options[$option])) {
                    $data[$option] = $search_options[$option];
                }
            }
        }

        return $this->post('v0/rag/' . urlencode($rag_id) . '/prompt', $data);
    }

    /**
     * Format RAG information for display.
     *
     * @since    1.0.0
     * @param    array    $rag    Raw RAG information from API.
     * @return   array            Formatted RAG information.
     */
    public function format_rag_info($rag) {
        return array(
            'id' => $rag['_id'],
            'name' => $rag['name'],
            'original_files' => explode(', ', $rag['original_filename']),
            'chunking_method' => $rag['chunking_method'],
            'chunk_size' => $rag['chunk_size'],
            'chunk_overlap' => $rag['chunk_overlap'],
            'created_at' => get_date_from_gmt($rag['createdAt']),
            'updated_at' => get_date_from_gmt($rag['updatedAt'])
        );
    }

    /**
     * Validate files for RAG creation.
     *
     * @since    1.0.0
     * @param    array     $files    Array of file paths.
     * @return   bool|WP_Error       True if valid, WP_Error if invalid.
     */
    public function validate_files($files) {
        if (empty($files)) {
            return new WP_Error(
                'no_files',
                __('No files provided for RAG creation', 'straico-integration')
            );
        }

        if (count($files) > 4) {
            return new WP_Error(
                'too_many_files',
                __('Maximum of 4 files allowed for RAG creation', 'straico-integration')
            );
        }

        $allowed_types = array(
            'pdf', 'docx', 'csv', 'txt', 'xlsx', 'py'
        );

        foreach ($files as $file) {
            if (!file_exists($file)) {
                return new WP_Error(
                    'file_not_found',
                    sprintf(
                        /* translators: %s: file path */
                        __('File not found: %s', 'straico-integration'),
                        $file
                    )
                );
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowed_types)) {
                return new WP_Error(
                    'invalid_file_type',
                    sprintf(
                        /* translators: 1: file name 2: allowed file types */
                        __('Invalid file type for %1$s. Allowed types: %2$s', 'straico-integration'),
                        basename($file),
                        implode(', ', $allowed_types)
                    )
                );
            }

            if (filesize($file) > 25 * 1024 * 1024) { // 25MB limit
                return new WP_Error(
                    'file_too_large',
                    sprintf(
                        /* translators: %s: file name */
                        __('File too large: %s (maximum size is 25MB)', 'straico-integration'),
                        basename($file)
                    )
                );
            }
        }

        return true;
    }

    /**
     * Get valid chunking methods and their parameters.
     *
     * @since    1.0.0
     * @return   array    Array of chunking methods and their parameters.
     */
    public function get_chunking_methods() {
        return array(
            'fixed_size' => array(
                'name' => __('Fixed Size', 'straico-integration'),
                'description' => __('Splits text into chunks of fixed size', 'straico-integration'),
                'parameters' => array(
                    'chunk_size' => array(
                        'type' => 'number',
                        'default' => 1000,
                        'description' => __('Size of each chunk', 'straico-integration')
                    ),
                    'chunk_overlap' => array(
                        'type' => 'number',
                        'default' => 50,
                        'description' => __('Overlap between chunks', 'straico-integration')
                    ),
                    'separator' => array(
                        'type' => 'string',
                        'default' => '\n',
                        'description' => __('Single separator between chunks. Only one separator is allowed (e.g., "\n", ". ", "\n\n").', 'straico-integration')
                    )
                )
            ),
            'recursive' => array(
                'name' => __('Recursive', 'straico-integration'),
                'description' => __('Recursively splits text using separators', 'straico-integration'),
                'parameters' => array(
                    'chunk_size' => array(
                        'type' => 'number',
                        'default' => 1000,
                        'description' => __('Size of each chunk', 'straico-integration')
                    ),
                    'chunk_overlap' => array(
                        'type' => 'number',
                        'default' => 50,
                        'description' => __('Overlap between chunks', 'straico-integration')
                    ),
                    'separators' => array(
                        'type' => 'array',
                        'default' => array("\n\n", "\n", ". "),
                        'description' => __('Enter one separator per line. Each separator can be one or more characters (e.g., "\n\n", "\n", ". "). No quotes needed.', 'straico-integration')
                    )
                )
            ),
            'markdown' => array(
                'name' => __('Markdown', 'straico-integration'),
                'description' => __('Optimized for Markdown files', 'straico-integration'),
                'parameters' => array(
                    'chunk_size' => array(
                        'type' => 'number',
                        'default' => 1000,
                        'description' => __('Size of each chunk', 'straico-integration')
                    ),
                    'chunk_overlap' => array(
                        'type' => 'number',
                        'default' => 50,
                        'description' => __('Overlap between chunks', 'straico-integration')
                    )
                )
            ),
            'python' => array(
                'name' => __('Python', 'straico-integration'),
                'description' => __('Optimized for Python files', 'straico-integration'),
                'parameters' => array(
                    'chunk_size' => array(
                        'type' => 'number',
                        'default' => 1000,
                        'description' => __('Size of each chunk', 'straico-integration')
                    ),
                    'chunk_overlap' => array(
                        'type' => 'number',
                        'default' => 50,
                        'description' => __('Overlap between chunks', 'straico-integration')
                    )
                )
            ),
            'semantic' => array(
                'name' => __('Semantic', 'straico-integration'),
                'description' => __('Splits text based on semantic meaning', 'straico-integration'),
                'parameters' => array(
                    'breakpoint_threshold_type' => array(
                        'type' => 'string',
                        'default' => 'percentile',
                        'options' => array(
                            'percentile',
                            'interquartile',
                            'standard_deviation',
                            'gradient'
                        ),
                        'description' => __('Method to determine breakpoints', 'straico-integration')
                    ),
                    'buffer_size' => array(
                        'type' => 'number',
                        'default' => 100,
                        'description' => __('Size of the buffer', 'straico-integration')
                    )
                )
            )
        );
    }
}
