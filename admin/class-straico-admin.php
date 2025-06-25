<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin
 */

class Straico_Admin {
    private $options;
    private $security;

    public function __construct() {
        $this->options = new Straico_Options();
        $this->security = new Straico_Security();

        // Register AJAX handlers
        add_action('wp_ajax_straico_force_model_update', array($this, 'handle_force_model_update'));
        add_action('wp_ajax_straico_force_balance_check', array($this, 'handle_force_balance_check'));
        add_action('wp_ajax_straico_create_rag', array($this, 'handle_create_rag'));
        add_action('wp_ajax_straico_get_rag_details', array($this, 'handle_get_rag_details'));
        add_action('wp_ajax_straico_delete_rag', array($this, 'handle_delete_rag'));
        add_action('wp_ajax_straico_create_agent', array($this, 'handle_create_agent'));
        add_action('wp_ajax_straico_delete_agent', array($this, 'handle_delete_agent'));
        add_action('wp_ajax_straico_get_agent_details', array($this, 'handle_get_agent_details'));
        add_action('wp_ajax_straico_connect_rag', array($this, 'handle_connect_rag'));
        add_action('wp_ajax_straico_prompt_completion', array($this, 'handle_prompt_completion'));
        add_action('wp_ajax_straico_upload_file', array($this, 'handle_file_upload')); // New handler for file uploads
        add_action('wp_ajax_straico_rag_prompt', array($this, 'handle_rag_prompt')); // Handler for RAG prompts
        add_action('wp_ajax_straico_agent_prompt', array($this, 'handle_agent_prompt')); // Handler for agent prompts
        add_action('wp_ajax_straico_create_shortcode', array($this, 'handle_create_shortcode')); // Handler for shortcode creation
    }

    /**
     * Handle file upload AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_file_upload() {
        error_log('Straico Admin - Processing file upload request');

        try {
            // Verify nonce
            if (!check_ajax_referer('straico_prompt_completion', 'straico_nonce', false)) {
                error_log('Straico Admin - File upload nonce verification failed');
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
                ));
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                error_log('Straico Admin - File upload user capability check failed');
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action.', 'straico-integration')
                ));
            }

            // Check if file was uploaded
            if (empty($_FILES['file']['tmp_name'])) {
                error_log('Straico Admin - No file was uploaded');
                wp_send_json_error(array(
                    'message' => __('No file was uploaded. Please ensure you provide a file using multipart/form-data and try again.', 'straico-integration')
                ));
            }

            // Get file info
            $original_name = $_FILES['file']['name'];
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $base_name = pathinfo($original_name, PATHINFO_FILENAME);

            // Initialize file API
            $file_api = new Straico_File_API();

            // Validate file before uploading
            $validation_result = $file_api->validate_file($_FILES['file']['tmp_name'], $original_name);
            if (is_wp_error($validation_result)) {
                error_log('Straico Admin - File validation failed: ' . $validation_result->get_error_message());
                wp_send_json_error(array(
                    'message' => $validation_result->get_error_message()
                ));
            }

            // Sanitize base name (remove special characters and spaces)
            $safe_name = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $base_name);
            
            // Create a unique suffix
            $unique_suffix = '_' . uniqid();
            
            // Create final filename with original extension
            $final_name = $safe_name . $unique_suffix . '.' . $file_extension;

            // Create temporary file path
            $upload_dir = wp_upload_dir();
            $temp_file = $upload_dir['basedir'] . '/' . $final_name;

            // Store original name in a temporary file for the API
            $original_file = $upload_dir['basedir'] . '/original_' . $original_name;
            
            // First move to temp file
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $temp_file)) {
                error_log('Straico Admin - Failed to move uploaded file');
                wp_send_json_error(array(
                    'message' => __('Failed to process uploaded file.', 'straico-integration')
                ));
            }

            // Then copy to original name file
            if (!copy($temp_file, $original_file)) {
                unlink($temp_file);
                error_log('Straico Admin - Failed to create original name file');
                wp_send_json_error(array(
                    'message' => __('Failed to process uploaded file.', 'straico-integration')
                ));
            }

            // Upload to Straico using the original name file
            $upload_result = $file_api->upload_file($original_file);
            
            // Clean up temporary files
            unlink($temp_file);
            unlink($original_file);

            if (is_wp_error($upload_result)) {
                error_log('Straico Admin - File upload to Straico failed: ' . $upload_result->get_error_message());
                wp_send_json_error(array(
                    'message' => $upload_result->get_error_message()
                ));
            }

            error_log('Straico Admin - File upload successful: ' . $upload_result['data']['url']);
            wp_send_json_success(array(
                'url' => $upload_result['data']['url']
            ));

        } catch (Exception $e) {
            error_log('Straico Admin - File upload exception: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: %s: error message */
                    __('An error occurred during file upload: %s', 'straico-integration'),
                    $e->getMessage()
                )
            ));
        }
    }

    /**
     * Handle the prompt completion AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_prompt_completion() {
        error_log('Straico Admin - Processing prompt completion request');

        try {
            // Verify nonce
            if (!check_ajax_referer('straico_prompt_completion', 'straico_nonce', false)) {
                error_log('Straico Admin - Nonce verification failed');
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
                ));
            }

            // Verify user capabilities
            if (!current_user_can('manage_options')) {
                error_log('Straico Admin - User capability check failed');
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action.', 'straico-integration')
                ));
            }

            // Initialize prompt API
            $prompt_api = new Straico_Prompt_API();

            // Get and validate form data
            $models = isset($_POST['models']) ? (array)$_POST['models'] : array();
            error_log('Straico Admin - Received models: ' . print_r($models, true));

            // Get file URLs if provided
            $file_urls = isset($_POST['file_urls']) ? (array)$_POST['file_urls'] : array();
            error_log('Straico Admin - Received file URLs: ' . print_r($file_urls, true));

            $data = array(
                'models' => $models,
                'message' => isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '',
                'youtube_urls' => isset($_POST['youtube_url']) && !empty($_POST['youtube_url']) ? 
                    array(esc_url_raw($_POST['youtube_url'])) : array(),
                'temperature' => isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7,
                'max_tokens' => isset($_POST['max_tokens']) && !empty($_POST['max_tokens']) ? 
                    intval($_POST['max_tokens']) : null,
                'display_transcripts' => isset($_POST['display_transcripts']) ? true : false,
                'file_urls' => $file_urls
            );

            error_log('Straico Admin - Processed request data: ' . print_r($data, true));

            // Validate the data
            $validation_result = $prompt_api->validate_prompt_data($data);
            if (is_wp_error($validation_result)) {
                error_log('Straico Admin - Data validation failed: ' . $validation_result->get_error_message());
                wp_send_json_error(array(
                    'message' => $validation_result->get_error_message()
                ));
            }

            // Submit prompt
            $result = $prompt_api->submit_prompt(
                $data['models'],
                $data['message'],
                $data['file_urls'],
                $data['youtube_urls'],
                array(
                    'temperature' => $data['temperature'],
                    'max_tokens' => $data['max_tokens'],
                    'display_transcripts' => $data['display_transcripts']
                )
            );

            if (is_wp_error($result)) {
                error_log('Straico Admin - API request failed: ' . $result->get_error_message());
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            }

            // Format and return response
            $formatted_response = $prompt_api->format_completion_response($result);
            error_log('Straico Admin - Request completed successfully');
            wp_send_json_success($formatted_response);

        } catch (Exception $e) {
            error_log('Straico Admin - Exception occurred: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: %s: error message */
                    __('An error occurred: %s', 'straico-integration'),
                    $e->getMessage()
                )
            ));
        }
    }

    /**
     * Handle the force balance check AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_force_balance_check() {
        // Verify nonce
        if (!check_ajax_referer('straico_force_balance_check', 'straico_nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'straico-integration')
            ));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'straico-integration')
            ));
        }

        // Perform balance check
        $balance_check = new Straico_Balance_Check();
        $check_result = $balance_check->force_check();

        if (is_wp_error($check_result)) {
            wp_send_json_error(array(
                'message' => $check_result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Balance updated successfully.', 'straico-integration')
        ));
    }

/**
 * Handle the create RAG AJAX request.
 *
 * @since    1.0.0
 */
public function handle_create_rag() {
    // Verify nonce with detailed logging
    $nonce = isset($_POST['straico_nonce']) ? $_POST['straico_nonce'] : '';
    error_log('Straico RAG Creation - Nonce received: ' . ($nonce ? 'yes' : 'no'));
    
    if (!check_ajax_referer('straico_create_rag', 'straico_nonce', false)) {
        error_log('Straico RAG Creation - Nonce verification failed');
        error_log('Straico RAG Creation - POST data: ' . print_r($_POST, true));
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
        ));
    }
    error_log('Straico RAG Creation - Nonce verification passed');

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate required fields
    if (empty($_POST['rag_name']) || empty($_POST['rag_description'])) {
        wp_send_json_error(array(
            'message' => __('Required fields are missing. Please fill in all required fields.', 'straico-integration')
        ));
    }

    // Validate file upload
    if (empty($_FILES['rag_files']) || empty($_FILES['rag_files']['tmp_name']) || empty($_FILES['rag_files']['tmp_name'][0])) {
        wp_send_json_error(array(
            'message' => __('Please upload at least one file.', 'straico-integration')
        ));
    }

    // Initialize RAG API
    $rag_api = new Straico_RAG_API();

    // Get sanitized basic info
    $name = sanitize_text_field($_POST['rag_name']);
    $description = sanitize_textarea_field($_POST['rag_description']);
    $chunking_method = isset($_POST['chunking_method']) ? sanitize_text_field($_POST['chunking_method']) : 'fixed_size';

    // Validate RAG name for spaces
    if (strpos($name, ' ') !== false) {
        wp_send_json_error(array(
            'message' => __('RAG name cannot contain spaces.', 'straico-integration')
        ));
    }

    // Prepare chunking options
    $chunking_options = array();
    
    if ($chunking_method === 'fixed_size') {
        // Debug log for fixed_size parameters
        error_log('Straico RAG Creation - Fixed Size Parameters:');
        error_log('POST data: ' . print_r($_POST, true));
        
        // For fixed_size, get parameters from chunking_options array
        if (isset($_POST['chunking_options']) && is_array($_POST['chunking_options'])) {
            $chunking_options['chunk_size'] = isset($_POST['chunking_options']['chunk_size']) ? 
                intval($_POST['chunking_options']['chunk_size']) : 1000;
            $chunking_options['chunk_overlap'] = isset($_POST['chunking_options']['chunk_overlap']) ? 
                intval($_POST['chunking_options']['chunk_overlap']) : 50;
            $chunking_options['separator'] = isset($_POST['chunking_options']['separator']) ? 
                wp_unslash($_POST['chunking_options']['separator']) : "\n";
            if ($chunking_options['separator'] === '\\n' || empty($chunking_options['separator'])) {
                $chunking_options['separator'] = "\n";
            }
        } else {
            // Set default values if chunking_options is not set
            $chunking_options['chunk_size'] = 1000;
            $chunking_options['chunk_overlap'] = 50;
            $chunking_options['separator'] = "\n";
        }
        
        // Debug log the final chunking options
        error_log('Final chunking options: ' . print_r($chunking_options, true));
    } else if (isset($_POST['chunking_options']) && is_array($_POST['chunking_options'])) {
        // For other methods, process from chunking_options array
        foreach ($_POST['chunking_options'] as $key => $value) {
            if ($key === 'separators' && $chunking_method === 'recursive') {
                // Handle recursive chunking separators as array
                $chunking_options[$key] = array_map('sanitize_text_field', (array)$value);
            } elseif (in_array($key, ['chunk_size', 'chunk_overlap', 'buffer_size'])) {
                // Ensure numeric values are properly formatted
                $chunking_options[$key] = intval($value);
            } else {
                // Standard sanitization for other options
                $chunking_options[$key] = sanitize_text_field($value);
            }
        }
    }

    // Initialize arrays to track files
    $files = array();
    $temp_files = array();
    $upload_dir = wp_upload_dir();

    // Function to clean up temporary files
    $cleanup_temp_files = function() use (&$temp_files) {
        foreach ($temp_files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $temp_files = array(); // Reset array after cleanup
    };

    // Register shutdown function to ensure cleanup even on fatal errors
    register_shutdown_function($cleanup_temp_files);

    // Validate all files before moving any
    foreach ($_FILES['rag_files']['tmp_name'] as $key => $tmp_name) {
        if (empty($tmp_name)) {
            continue;
        }

        // Check for upload errors
        if ($_FILES['rag_files']['error'][$key] !== UPLOAD_ERR_OK) {
            $error_message = $this->get_upload_error_message($_FILES['rag_files']['error'][$key]);
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: 1: file name 2: error message */
                    __('Upload failed for %1$s: %2$s', 'straico-integration'),
                    $_FILES['rag_files']['name'][$key],
                    $error_message
                )
            ));
        }

        // Validate file type
        $file_info = wp_check_filetype($_FILES['rag_files']['name'][$key]);
        if (!$file_info['type']) {
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: %s: file name */
                    __('Invalid file type: %s', 'straico-integration'),
                    $_FILES['rag_files']['name'][$key]
                )
            ));
        }

        // Validate file size
        $max_size = wp_max_upload_size();
        if ($_FILES['rag_files']['size'][$key] > $max_size) {
            wp_send_json_error(array(
                'message' => sprintf(
                    /* translators: 1: file name 2: max size */
                    __('File %1$s exceeds maximum upload size of %2$s', 'straico-integration'),
                    $_FILES['rag_files']['name'][$key],
                    size_format($max_size)
                )
            ));
        }
    }

        // Now process the files
        foreach ($_FILES['rag_files']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) {
                continue;
            }

            $original_name = $_FILES['rag_files']['name'][$key];
            $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $base_name = pathinfo($original_name, PATHINFO_FILENAME);
            
            // Log file details
            error_log(sprintf(
                'Processing file: %s (tmp_name: %s, size: %d bytes)',
                $original_name,
                $tmp_name,
                $_FILES['rag_files']['size'][$key]
            ));

            // Verify file was fully uploaded
            if ($_FILES['rag_files']['size'][$key] === 0) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: file name */
                        __('File %s appears to be empty. Please try uploading again.', 'straico-integration'),
                        $original_name
                    )
                ));
            }

            // Verify file exists in temp location
            if (!file_exists($tmp_name)) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: file name */
                        __('Temporary file for %s is missing. Please try uploading again.', 'straico-integration'),
                        $original_name
                    )
                ));
            }

            // Sanitize filename
            $safe_name = preg_replace('/[^a-zA-Z0-9\-_]/', '', str_replace(' ', '_', $base_name));
            $unique_suffix = wp_generate_password(6, false);
            $temp_file = $upload_dir['basedir'] . '/' . $safe_name . '_' . $unique_suffix . '.' . $file_extension;

            // Ensure we have write permissions
            if (!is_writable($upload_dir['basedir'])) {
                error_log('Upload directory is not writable: ' . $upload_dir['basedir']);
                wp_send_json_error(array(
                    'message' => __('Upload directory is not writable. Please contact your administrator.', 'straico-integration')
                ));
            }

            // Move the file with additional verification
            if (!move_uploaded_file($tmp_name, $temp_file)) {
                error_log(sprintf(
                    'Failed to move uploaded file from %s to %s',
                    $tmp_name,
                    $temp_file
                ));
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: file name */
                        __('Failed to move uploaded file: %s', 'straico-integration'),
                        $original_name
                    )
                ));
            }

            // Verify the moved file exists and has content
            if (!file_exists($temp_file) || filesize($temp_file) === 0) {
                error_log(sprintf(
                    'Moved file verification failed - exists: %s, size: %d bytes',
                    file_exists($temp_file) ? 'yes' : 'no',
                    file_exists($temp_file) ? filesize($temp_file) : -1
                ));
                wp_send_json_error(array(
                    'message' => sprintf(
                        /* translators: %s: file name */
                        __('File %s was not properly saved. Please try uploading again.', 'straico-integration'),
                        $original_name
                    )
                ));
            }

            $files[] = $temp_file;
            $temp_files[] = $temp_file;

            error_log(sprintf(
                'Successfully processed file %s to %s (size: %d bytes)',
                $original_name,
                $temp_file,
                filesize($temp_file)
            ));
        }

    // Validate files
    $validation_result = $rag_api->validate_files($files);
    if (is_wp_error($validation_result)) {
        $cleanup_temp_files(); // Clean up any files that were created
        wp_send_json_error(array(
            'message' => $validation_result->get_error_message()
        ));
    }

    // Set longer timeout for API request
    add_filter('http_request_timeout', function() { return 300; }); // 5 minutes

    // Create RAG
    $result = $rag_api->create_rag($name, $description, $files, $chunking_method, $chunking_options);

    // Clean up temporary files
    $cleanup_temp_files();

    if (is_wp_error($result)) {
        error_log('Straico RAG Creation Error: ' . $result->get_error_message());
        error_log('Error Data: ' . print_r($result->get_error_data(), true));
        
        $error_message = $result->get_error_message();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $error_data = $result->get_error_data();
            if (!empty($error_data)) {
                $error_message .= ' Debug info: ' . print_r($error_data, true);
            }
        }
        
        wp_send_json_error(array(
            'message' => $error_message
        ));
    }

    wp_send_json_success(array(
        'message' => __('RAG created successfully.', 'straico-integration'),
        'data' => $result
    ));
}

/**
 * Handle the delete RAG AJAX request.
 *
 * @since    1.0.0
 */
public function handle_delete_rag() {
    // Verify nonce
    if (!check_ajax_referer('straico_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate RAG ID
    if (empty($_POST['rag_id'])) {
        wp_send_json_error(array(
            'message' => __('RAG ID is required.', 'straico-integration')
        ));
    }

    // Delete RAG
    $rag_api = new Straico_RAG_API();
    $result = $rag_api->delete_rag(sanitize_text_field($_POST['rag_id']));

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('RAG deleted successfully.', 'straico-integration')
    ));
}
/**
 * Handle the get RAG details AJAX request.
 *
 * @since    1.0.0
 */
public function handle_get_rag_details() {
    // Verify nonce
    if (!check_ajax_referer('straico_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate RAG ID
    if (empty($_POST['rag_id'])) {
        wp_send_json_error(array(
            'message' => __('RAG ID is required.', 'straico-integration')
        ));
    }

    $rag_id = sanitize_text_field($_POST['rag_id']);

    // Get RAG details
    $rag_api = new Straico_RAG_API();
    $result = $rag_api->get_rag_by_id($rag_id);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    if (!isset($result['data']) || empty($result['data'])) {
        wp_send_json_error(array(
            'message' => __('RAG details not found or an error occurred.', 'straico-integration')
        ));
        return;
    }

    // Format RAG details using the API's formatting method
    $rag_data = $rag_api->format_rag_info($result['data']);

    // Format RAG details into HTML
    ob_start();
    ?>
    <table class="widefat">
        <tr>
            <th><?php _e('ID', 'straico-integration'); ?></th>
            <td><code><?php echo esc_html($rag_data['id']); ?></code></td>
        </tr>
        <tr>
            <th><?php _e('Name', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['name']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Original Files', 'straico-integration'); ?></th>
            <td>
                <?php if (!empty($rag_data['original_files'])) : ?>
                    <ul>
                        <?php foreach ($rag_data['original_files'] as $file) : ?>
                            <li><?php echo esc_html($file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <?php _e('N/A', 'straico-integration'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Chunking Method', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['chunking_method']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Chunk Size', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['chunk_size']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Chunk Overlap', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['chunk_overlap']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Created', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['created_at']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Last Updated', 'straico-integration'); ?></th>
            <td><?php echo esc_html($rag_data['updated_at']); ?></td>
        </tr>
    </table>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html
    ));
}

/**
 * Handle the force model update AJAX request.
 *
 * @since    1.0.0
 */
public function handle_force_model_update() {
    // Verify nonce
    if (!check_ajax_referer('straico_force_model_update', 'straico_nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Get updated models
    $models_api = new Straico_Models_API();
    $result = $models_api->get_models();

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('Models updated successfully.', 'straico-integration')
    ));
}

/**
 * Register the stylesheets for the admin area.
 *
 * @since    1.0.0
 */
public function enqueue_styles() {
    $screen = get_current_screen();
    if (!$this->is_plugin_page($screen)) {
        return;
    }

    wp_enqueue_style(
        'straico-admin',
        STRAICO_INTEGRATION_URL . 'admin/css/straico-admin.css',
        array(),
        STRAICO_INTEGRATION_VERSION,
        'all'
    );

    // Enqueue specific styles based on the current page
    if (strpos($screen->id, 'straico-prompt-completion') !== false) {
        wp_enqueue_style(
            'straico-prompt-completion',
            STRAICO_INTEGRATION_URL . 'admin/css/prompt-completion.css',
            array('straico-admin'),
            STRAICO_INTEGRATION_VERSION,
            'all'
        );
    }
}

/**
 * Register the JavaScript for the admin area.
 *
 * @since    1.0.0
 */
public function enqueue_scripts() {
    $screen = get_current_screen();
    if (!$this->is_plugin_page($screen)) {
        return;
    }

    wp_enqueue_script(
        'straico-admin',
        STRAICO_INTEGRATION_URL . 'admin/js/straico-admin.js',
        array('jquery'),
        STRAICO_INTEGRATION_VERSION,
        true
    );

    // Enqueue specific scripts based on the current page
    if (strpos($screen->id, 'straico-rag-management') !== false) {
        wp_enqueue_script(
            'straico-rag-management',
            STRAICO_INTEGRATION_URL . 'admin/js/rag-management.js',
            array('jquery'),
            STRAICO_INTEGRATION_VERSION,
            true
        );
    } elseif (strpos($screen->id, 'straico-agent-management') !== false || strpos($screen->id, 'straico-create-agent') !== false) {
        wp_enqueue_script(
            'straico-agent-management',
            STRAICO_INTEGRATION_URL . 'admin/js/agent-management.js',
            array('jquery'),
            STRAICO_INTEGRATION_VERSION,
            true
        );
        
    } elseif (strpos($screen->id, 'straico-prompt-completion') !== false) {
        wp_enqueue_script(
            'straico-prompt-completion',
            STRAICO_INTEGRATION_URL . 'admin/js/prompt-completion.js',
            array('jquery'),
            STRAICO_INTEGRATION_VERSION,
            true
        );
    }

    // Localize scripts with necessary data
    $localization_data = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('straico_admin_nonce'),
        'strings' => array(
            'confirmDelete' => __('Are you sure you want to delete this item? This action cannot be undone.', 'straico-integration'),
            'error' => __('An error occurred. Please try again.', 'straico-integration'),
            'success' => __('Operation completed successfully.', 'straico-integration'),
            'remove' => __('Remove', 'straico-integration'),
            'selectModel' => __('Please select at least one model.', 'straico-integration'),
            'invalidYouTubeUrl' => __('Please enter a valid YouTube video URL.', 'straico-integration'),
            'input' => __('Input', 'straico-integration'),
            'output' => __('Output', 'straico-integration'),
            'words' => __('words', 'straico-integration'),
            'cost' => __('Cost', 'straico-integration'),
            'coins' => __('coins', 'straico-integration'),
            'transcripts' => __('Transcripts', 'straico-integration'),
            'totalUsage' => __('Total Usage', 'straico-integration'),
            'totalCost' => __('Total Cost', 'straico-integration')
        )
    );

    wp_localize_script('straico-admin', 'straicoAdmin', $localization_data);

    // Add agent-specific localization if on agent management page
    if (strpos($screen->id, 'straico-agent-management') !== false || strpos($screen->id, 'straico-create-agent') !== false) {
        $agent_data = array(
            'agentListUrl' => admin_url('admin.php?page=straico-agent-management'),
            'strings' => array(
                'deleteConfirmPrefix' => __('Are you sure you want to delete the agent:', 'straico-integration'),
                'deleteConfirmSuffix' => __('This action cannot be undone.', 'straico-integration'),
                'error' => __('An error occurred. Please try again.', 'straico-integration'),
                'loading' => __('Loading...', 'straico-integration'),
                'deleting' => __('Deleting...', 'straico-integration')
            )
        );
        wp_localize_script('straico-agent-management', 'straicoAdmin', array_merge($localization_data, $agent_data));
    }

    // Also localize RAG management script if on RAG management page
    if (strpos($screen->id, 'straico-rag-management') !== false) {
        $localization_data['strings']['confirmDelete'] = __('Are you sure you want to delete the RAG: {name}?<br><br>This action cannot be undone.', 'straico-integration');
        wp_localize_script('straico-rag-management', 'straicoAdmin', $localization_data);
    }
}

/**
 * Add plugin admin menu items.
 *
 * @since    1.0.0
 */
public function add_plugin_admin_menu() {
    // Add top level menu
    add_menu_page(
        __('Straico', 'straico-integration'),
        __('Straico', 'straico-integration'),
        'manage_options',
        'straico-settings',
        array($this, 'display_settings_page'),
        'dashicons-rest-api',
        30
    );

    // Add settings submenu
    add_submenu_page(
        'straico-settings',
        __('Straico Settings', 'straico-integration'),
        __('Settings', 'straico-integration'),
        'manage_options',
        'straico-settings',
        array($this, 'display_settings_page')
    );

    // Add models information submenu
    add_submenu_page(
        'straico-settings',
        __('Models Information', 'straico-integration'),
        __('Models Information', 'straico-integration'),
        'manage_options',
        'straico-models-info',
        array($this, 'display_models_info_page')
    );

    // Add user information submenu
    add_submenu_page(
        'straico-settings',
        __('User Information', 'straico-integration'),
        __('User Information', 'straico-integration'),
        'manage_options',
        'straico-user-info',
        array($this, 'display_user_info_page')
    );

    // Add RAG management section
    add_submenu_page(
        'straico-settings',
        __('Manage RAGs', 'straico-integration'),
        __('RAG Management', 'straico-integration'),
        'manage_options',
        'straico-rag-management',
        array($this, 'display_rag_management_page')
    );

    add_submenu_page(
        'straico-settings',
        __('Create RAG', 'straico-integration'),
        __('Create RAG', 'straico-integration'),
        'manage_options',
        'straico-create-rag',
        array($this, 'display_create_rag_page')
    );

    // Add agent management section
    add_submenu_page(
        'straico-settings',
        __('Manage Agents', 'straico-integration'),
        __('Agent Management', 'straico-integration'),
        'manage_options',
        'straico-agent-management',
        array($this, 'display_agent_management_page')
    );

    add_submenu_page(
        'straico-settings',
        __('Create Agent', 'straico-integration'),
        __('Create Agent', 'straico-integration'),
        'manage_options',
        'straico-create-agent',
        array($this, 'display_create_agent_page')
    );

    add_submenu_page(
        'straico-settings',
        __('Connect RAG to Agent', 'straico-integration'),
        __('Connect RAG to Agent', 'straico-integration'),
        'manage_options',
        'straico-connect-rag',
        array($this, 'display_connect_rag_page')
    );

    // Add prompt completion section
    add_submenu_page(
        'straico-settings',
        __('Basic Prompt Completion', 'straico-integration'),
        __('Prompt Completions', 'straico-integration'),
        'manage_options',
        'straico-prompt-completion',
        array($this, 'display_prompt_completion_page')
    );

    add_submenu_page(
        'straico-settings',
        __('RAG Prompt Completion', 'straico-integration'),
        __('RAG Prompt', 'straico-integration'),
        'manage_options',
        'straico-rag-prompt',
        array($this, 'display_rag_prompt_page')
    );

    add_submenu_page(
        'straico-settings',
        __('Agent Prompt Completion', 'straico-integration'),
        __('Agent Prompt', 'straico-integration'),
        'manage_options',
        'straico-agent-prompt',
        array($this, 'display_agent_prompt_page')
    );

    // Add shortcode section
    add_submenu_page(
        'straico-settings',
        __('Create Shortcodes', 'straico-integration'),
        __('Agent Shortcodes', 'straico-integration'),
        'manage_options',
        'straico-create-shortcode',
        array($this, 'display_create_shortcode_page')
    );

    add_submenu_page(
        'straico-settings',
        __('Manage Shortcodes', 'straico-integration'),
        __('Manage Shortcodes', 'straico-integration'),
        'manage_options',
        'straico-manage-shortcodes',
        array($this, 'display_manage_shortcodes_page')
    );
}

/**
 * Register plugin settings.
 *
 * @since    1.0.0
 */
public function register_settings() {
    $this->options->register_settings();
}

/**
 * Display the settings page.
 *
 * @since    1.0.0
 */
public function display_settings_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/settings/settings-page.php';
}

/**
 * Display the models information page.
 *
 * @since    1.0.0
 */
public function display_models_info_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/models-info/models-info-page.php';
}

/**
 * Display the user information page.
 *
 * @since    1.0.0
 */
public function display_user_info_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/user-info/user-info-page.php';
}

/**
 * Display the RAG management page.
 *
 * @since    1.0.0
 */
public function display_rag_management_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/rag-management/manage-rags.php';
}

/**
 * Display the create RAG page.
 *
 * @since    1.0.0
 */
public function display_create_rag_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/rag-management/create-rag.php';
}

/**
 * Display the agent management page.
 *
 * @since    1.0.0
 */
public function display_agent_management_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/agent-management/manage-agents.php';
}

/**
 * Display the create agent page.
 *
 * @since    1.0.0
 */
public function display_create_agent_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/agent-management/create-agent.php';
}

/**
 * Display the connect RAG to agent page.
 *
 * @since    1.0.0
 */
public function display_connect_rag_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/agent-management/connect-rag.php';
}

/**
 * Display the prompt completion page.
 *
 * @since    1.0.0
 */
public function display_prompt_completion_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/prompt-completions/basic-completion.php';
}

/**
 * Display the RAG prompt completion page.
 *
 * @since    1.0.0
 */
public function display_rag_prompt_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/prompt-completions/rag-completion.php';
}

/**
 * Display the agent prompt completion page.
 *
 * @since    1.0.0
 */
public function display_agent_prompt_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/prompt-completions/agent-completion.php';
}

/**
 * Display the create shortcode page.
 *
 * @since    1.0.0
 */
public function display_create_shortcode_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/shortcodes/create-shortcode.php';
}

/**
 * Display the manage shortcodes page.
 *
 * @since    1.0.0
 */
public function display_manage_shortcodes_page() {
    require_once STRAICO_INTEGRATION_PATH . 'admin/partials/shortcodes/manage-shortcodes.php';
}

/**
 * Check if the current screen is a plugin page.
 *
 * @since    1.0.0
 * @param    WP_Screen    $screen    The current screen object.
 * @return   bool                    Whether this is a plugin page.
 */
private function is_plugin_page($screen) {
    if (!$screen) {
        return false;
    }

    return strpos($screen->id, 'straico') !== false;
}

/**
 * Add settings action link to the plugins page.
 *
 * @since    1.0.0
 * @param    array    $links    Plugin action links.
 * @return   array              Modified action links.
 */
public function add_action_links($links) {
    $settings_link = array(
        '<a href="' . admin_url('admin.php?page=straico-settings') . '">' . 
        __('Settings', 'straico-integration') . '</a>'
    );
    return array_merge($settings_link, $links);
}

/**
 * Get human-readable error message for file upload errors.
 *
 * @since    1.0.0
 * @param    int       $error_code    The PHP file upload error code.
 * @return   string                   Human-readable error message.
 */

/**
 * Handle the RAG prompt completion AJAX request.
 *
 * @since    1.0.0
 */
public function handle_rag_prompt() {
    error_log('Straico Admin - Processing RAG prompt request');

    try {
        // Verify nonce
        if (!check_ajax_referer('straico_rag_prompt', 'straico_nonce', false)) {
            error_log('Straico Admin - RAG prompt nonce verification failed');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
            ));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            error_log('Straico Admin - RAG prompt user capability check failed');
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'straico-integration')
            ));
        }

        // Get and validate required fields
        $rag_id = isset($_POST['rag_id']) ? sanitize_text_field($_POST['rag_id']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if (empty($rag_id) || empty($model) || empty($prompt)) {
            error_log('Straico Admin - RAG prompt missing required fields');
            wp_send_json_error(array(
                'message' => __('Required fields are missing.', 'straico-integration')
            ));
        }

        // Get optional search parameters
        $search_options = array(
            'search_type' => isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : 'similarity',
            'k' => isset($_POST['k']) ? intval($_POST['k']) : 4
        );

        // Add MMR-specific options if using MMR search type
        if ($search_options['search_type'] === 'mmr') {
            $fetch_k = isset($_POST['fetch_k']) ? sanitize_text_field($_POST['fetch_k']) : '9';
            $search_options['fetch_k'] = is_string($fetch_k) ? trim($fetch_k) : '9';

            $lambda_mult = isset($_POST['lambda_mult']) ? sanitize_text_field($_POST['lambda_mult']) : '0.5';
            $search_options['lambda_mult'] = is_string($lambda_mult) ? trim($lambda_mult) : '0.5';
        }

        // Add threshold option if using similarity_score_threshold search type
        if ($search_options['search_type'] === 'similarity_score_threshold') {
            $score_threshold = isset($_POST['score_threshold']) ? sanitize_text_field($_POST['score_threshold']) : '0.5';
            $search_options['score_threshold'] = is_string($score_threshold) ? trim($score_threshold) : '0.5';
        }

        // Initialize RAG API
        $rag_api = new Straico_RAG_API();

        // Submit prompt
        $result = $rag_api->prompt_rag($rag_id, $prompt, $model, $search_options);

        if (is_wp_error($result)) {
            error_log('Straico Admin - RAG prompt API request failed: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        error_log('Straico Admin - RAG prompt request completed successfully');
        error_log('Straico Admin - RAG prompt raw response: ' . print_r($result, true));

        // Validate response structure
        if (!isset($result['response']) || !is_array($result['response'])) {
            error_log('Straico Admin - Invalid response structure: missing response array');
            wp_send_json_error(array(
                'message' => __('Invalid response from API', 'straico-integration')
            ));
            return;
        }

        if (!isset($result['response']['answer'])) {
            error_log('Straico Admin - Invalid response structure: missing answer');
            wp_send_json_error(array(
                'message' => __('Invalid response from API: missing answer', 'straico-integration')
            ));
            return;
        }

        // Format response for the frontend
        $formatted_response = array(
            'response' => array(
                'answer' => $result['response']['answer'],
                'references' => array(),
                'coins_used' => isset($result['response']['coins_used']) ? $result['response']['coins_used'] : 0
            )
        );

        // Add references if they exist
        if (isset($result['response']['references']) && is_array($result['response']['references'])) {
            $formatted_response['response']['references'] = array_map(function($ref) {
                return array(
                    'page_content' => isset($ref['page_content']) ? $ref['page_content'] : '',
                    'page' => isset($ref['page']) ? $ref['page'] : ''
                );
            }, $result['response']['references']);
        }

        error_log('Straico Admin - Formatted response: ' . print_r($formatted_response, true));
        wp_send_json_success($formatted_response);

    } catch (Exception $e) {
        error_log('Straico Admin - RAG prompt exception: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => sprintf(
                /* translators: %s: error message */
                __('An error occurred: %s', 'straico-integration'),
                $e->getMessage()
            )
        ));
    }
}

/**
 * Handle the agent prompt completion AJAX request.
 *
 * @since    1.0.0
 */
public function handle_agent_prompt() {
    error_log('Straico Admin - Processing agent prompt request');

    try {
        // Verify nonce
        if (!check_ajax_referer('straico_agent_prompt', 'straico_nonce', false)) {
            error_log('Straico Admin - Agent prompt nonce verification failed');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
            ));
        }

        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            error_log('Straico Admin - Agent prompt user capability check failed');
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'straico-integration')
            ));
        }

        // Get and validate required fields
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';

        if (empty($agent_id) || empty($prompt)) {
            error_log('Straico Admin - Agent prompt missing required fields');
            wp_send_json_error(array(
                'message' => __('Required fields are missing.', 'straico-integration')
            ));
        }

        // Get optional search parameters for RAG-enabled agents
        $search_options = array(
            'search_type' => isset($_POST['search_type']) ? sanitize_text_field($_POST['search_type']) : 'similarity',
            'k' => isset($_POST['k']) ? intval($_POST['k']) : 4
        );

        // Add MMR-specific options if using MMR search type
        if ($search_options['search_type'] === 'mmr') {
            $search_options['fetch_k'] = isset($_POST['fetch_k']) ? intval($_POST['fetch_k']) : 20;
            $search_options['lambda_mult'] = isset($_POST['lambda_mult']) ? floatval($_POST['lambda_mult']) : 0.5;
        }

        // Add threshold option if using similarity_score_threshold search type
        if ($search_options['search_type'] === 'similarity_score_threshold') {
            $search_options['score_threshold'] = isset($_POST['score_threshold']) ? floatval($_POST['score_threshold']) : 0.5;
        }

        // Initialize agent API
        $agent_api = new Straico_Agent_API();

        // Submit prompt
        $result = $agent_api->prompt_agent($agent_id, $prompt, $search_options);

        if (is_wp_error($result)) {
            error_log('Straico Admin - Agent prompt API request failed: ' . $result->get_error_message());
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        error_log('Straico Admin - Agent prompt request completed successfully');

        // Validate response structure
        if (!isset($result['response']) || !is_array($result['response'])) {
            error_log('Straico Admin - Invalid response structure: missing response array');
            wp_send_json_error(array(
                'message' => __('Invalid response from API', 'straico-integration')
            ));
            return;
        }

        if (!isset($result['response']['answer'])) {
            error_log('Straico Admin - Invalid response structure: missing answer');
            wp_send_json_error(array(
                'message' => __('Invalid response from API: missing answer', 'straico-integration')
            ));
            return;
        }

        // Format response for the frontend
        $formatted_response = array(
            'response' => array(
                'answer' => $result['response']['answer'],
                'references' => array(),
                'coins_used' => isset($result['response']['coins_used']) ? $result['response']['coins_used'] : 0
            )
        );

        // Add references if they exist
        if (isset($result['response']['references']) && is_array($result['response']['references'])) {
            $formatted_response['response']['references'] = array_map(function($ref) {
                return array(
                    'page_content' => isset($ref['page_content']) ? $ref['page_content'] : '',
                    'page' => isset($ref['page']) ? $ref['page'] : ''
                );
            }, $result['response']['references']);
        }

        wp_send_json_success($formatted_response);

    } catch (Exception $e) {
        error_log('Straico Admin - Agent prompt exception: ' . $e->getMessage());
        wp_send_json_error(array(
            'message' => sprintf(
                /* translators: %s: error message */
                __('An error occurred: %s', 'straico-integration'),
                $e->getMessage()
            )
        ));
    }
}

/**
 * Handle the create agent AJAX request.
 *
 * @since    1.0.0
 */
public function handle_create_agent() {
    // Verify nonce
    if (!check_ajax_referer('straico_create_agent', 'straico_nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Get and sanitize form data
    $agent_data = array(
        'name' => isset($_POST['agent_name']) ? sanitize_text_field($_POST['agent_name']) : '',
        'description' => isset($_POST['agent_description']) ? sanitize_textarea_field($_POST['agent_description']) : '',
        'custom_prompt' => isset($_POST['custom_prompt']) ? sanitize_textarea_field($_POST['custom_prompt']) : '',
        'default_llm' => isset($_POST['default_llm']) ? sanitize_text_field($_POST['default_llm']) : '',
        'tags' => isset($_POST['agent_tags']) ? array_map('trim', explode(',', sanitize_text_field($_POST['agent_tags']))) : array()
    );

    // Initialize agent API
    $agent_api = new Straico_Agent_API();

    // Validate agent data
    $validation_result = $agent_api->validate_agent_data($agent_data);
    if (is_wp_error($validation_result)) {
        wp_send_json_error(array(
            'message' => $validation_result->get_error_message()
        ));
    }

    // Create agent
    $result = $agent_api->create_agent(
        $agent_data['name'],
        $agent_data['custom_prompt'],
        $agent_data['default_llm'],
        $agent_data['description'],
        $agent_data['tags']
    );

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('Agent created successfully.', 'straico-integration'),
        'data' => $result
    ));
}

/**
 * Handle the delete agent AJAX request.
 *
 * @since    1.0.0
 */
public function handle_delete_agent() {
    // Verify nonce
    if (!check_ajax_referer('straico_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate agent ID
    if (empty($_POST['agent_id'])) {
        wp_send_json_error(array(
            'message' => __('Agent ID is required.', 'straico-integration')
        ));
    }

    // Delete agent
    $agent_api = new Straico_Agent_API();
    $result = $agent_api->delete_agent(sanitize_text_field($_POST['agent_id']));

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('Agent deleted successfully.', 'straico-integration')
    ));
}

/**
 * Handle the connect RAG to agent AJAX request.
 *
 * @since    1.0.0
 */
public function handle_connect_rag() {
    // Verify nonce
    if (!check_ajax_referer('straico_connect_rag', 'straico_nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate required fields
    if (empty($_POST['agent_id']) || empty($_POST['rag_id'])) {
        wp_send_json_error(array(
            'message' => __('Both Agent and RAG must be selected.', 'straico-integration')
        ));
    }

    // Initialize agent API
    $agent_api = new Straico_Agent_API();

    // Connect RAG to agent
    $result = $agent_api->add_rag_to_agent(
        sanitize_text_field($_POST['agent_id']),
        sanitize_text_field($_POST['rag_id'])
    );

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('RAG connected to agent successfully.', 'straico-integration'),
        'data' => $result
    ));
}

/**
 * Handle the get agent details AJAX request.
 *
 * @since    1.0.0
 */
public function handle_get_agent_details() {
    // Verify nonce
    if (!check_ajax_referer('straico_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Validate agent ID
    if (empty($_POST['agent_id'])) {
        wp_send_json_error(array(
            'message' => __('Agent ID is required.', 'straico-integration')
        ));
    }

    // Get agent details
    $agent_api = new Straico_Agent_API();
    $result = $agent_api->get_agent_details(sanitize_text_field($_POST['agent_id']));

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    // Format agent details using the API's formatting method
    $agent_data = $agent_api->format_agent_info($result['data']);

    // Format agent details into HTML
    ob_start();
    ?>
    <table class="widefat">
        <tr>
            <th><?php _e('ID', 'straico-integration'); ?></th>
            <td><code><?php echo esc_html($agent_data['id']); ?></code></td>
        </tr>
        <tr>
            <th><?php _e('Name', 'straico-integration'); ?></th>
            <td><?php echo esc_html($agent_data['name']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Description', 'straico-integration'); ?></th>
            <td><?php echo esc_html($agent_data['description']); ?></td>
        </tr>
        <tr>
            <th><?php _e('Custom Prompt', 'straico-integration'); ?></th>
            <td><pre><?php echo esc_html($agent_data['custom_prompt']); ?></pre></td>
        </tr>
        <tr>
            <th><?php _e('Default LLM', 'straico-integration'); ?></th>
            <td><?php echo esc_html($agent_data['default_llm']); ?></td>
        </tr>
        <?php if (!empty($agent_data['tags'])) : ?>
        <tr>
            <th><?php _e('Tags', 'straico-integration'); ?></th>
            <td>
                <?php echo esc_html(implode(', ', $agent_data['tags'])); ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php if (isset($result['data']['rag']) && !empty($result['data']['rag']['name'])) : ?>
        <tr>
            <th><?php _e('Associated RAG', 'straico-integration'); ?></th>
            <td><?php echo esc_html($result['data']['rag']['name']); ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><?php _e('Created', 'straico-integration'); ?></th>
            <td><?php echo esc_html($agent_data['created_at']); ?></td>
        </tr>
    </table>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html
    ));
}



private function get_upload_error_message($error_code) {
switch ($error_code) {
    case UPLOAD_ERR_INI_SIZE:
        return __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'straico-integration');
    case UPLOAD_ERR_FORM_SIZE:
        return __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', 'straico-integration');
    case UPLOAD_ERR_PARTIAL:
        return __('The uploaded file was only partially uploaded', 'straico-integration');
    case UPLOAD_ERR_NO_FILE:
        return __('No file was uploaded', 'straico-integration');
    case UPLOAD_ERR_NO_TMP_DIR:
        return __('Missing a temporary folder', 'straico-integration');
    case UPLOAD_ERR_CANT_WRITE:
        return __('Failed to write file to disk', 'straico-integration');
    case UPLOAD_ERR_EXTENSION:
        return __('A PHP extension stopped the file upload', 'straico-integration');
    default:
        return __('Unknown upload error', 'straico-integration');
}
}

/**
 * Handle the create shortcode AJAX request.
 *
 * @since    1.0.0
 */
public function handle_create_shortcode() {
    // Verify nonce
    if (!check_ajax_referer('straico_create_shortcode', 'straico_nonce', false)) {
        wp_send_json_error(array(
            'message' => __('Security check failed. Please refresh the page and try again.', 'straico-integration')
        ));
    }

    // Verify user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to perform this action.', 'straico-integration')
        ));
    }

    // Get and validate required fields
    $name = isset($_POST['shortcode_name']) ? sanitize_text_field($_POST['shortcode_name']) : '';
    $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
    
    if (empty($name) || empty($agent_id)) {
        wp_send_json_error(array(
            'message' => __('Name and agent are required fields.', 'straico-integration')
        ));
    }

    // Build settings array
    $settings = array(
        'temperature' => isset($_POST['temperature']) ? floatval($_POST['temperature']) : 0.7,
        'max_tokens' => isset($_POST['max_tokens']) ? intval($_POST['max_tokens']) : 2048,
        'display_reset' => isset($_POST['display_reset']),
        'prompt_placeholder' => isset($_POST['prompt_placeholder']) ? sanitize_text_field($_POST['prompt_placeholder']) : '',
        'submit_button_text' => isset($_POST['submit_button_text']) ? sanitize_text_field($_POST['submit_button_text']) : '',
        'reset_button_text' => isset($_POST['reset_button_text']) ? sanitize_text_field($_POST['reset_button_text']) : '',
        'loading_text' => isset($_POST['loading_text']) ? sanitize_text_field($_POST['loading_text']) : '',
        'error_text' => isset($_POST['error_text']) ? sanitize_text_field($_POST['error_text']) : ''
    );

    // Add search options if present
    if (isset($_POST['search_type'])) {
        $settings['search_type'] = sanitize_text_field($_POST['search_type']);
        $settings['k'] = isset($_POST['k']) ? intval($_POST['k']) : 4;

        if ($settings['search_type'] === 'mmr') {
            $settings['fetch_k'] = isset($_POST['fetch_k']) ? intval($_POST['fetch_k']) : 20;
            $settings['lambda_mult'] = isset($_POST['lambda_mult']) ? floatval($_POST['lambda_mult']) : 0.5;
        } elseif ($settings['search_type'] === 'similarity_score_threshold') {
            $settings['score_threshold'] = isset($_POST['score_threshold']) ? floatval($_POST['score_threshold']) : 0.5;
        }
    }

    // Initialize database and shortcode managers
    $db_manager = new Straico_DB_Manager();
    $shortcode_manager = new Straico_Shortcode_Manager();

    // Ensure tables exist
    $db_manager->install();

    // Create shortcode
    $result = $shortcode_manager->create_shortcode($name, $agent_id, $settings);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    if (!$result) {
        wp_send_json_error(array(
            'message' => __('Failed to create shortcode. Please try again.', 'straico-integration')
        ));
    }

    // Get the created shortcode and agent details for the response
    $shortcode = $shortcode_manager->get_shortcode($result);
    $agent_api = new Straico_Agent_API();
    $agent = $agent_api->get_agent_details($agent_id);

    wp_send_json_success(array(
        'shortcode' => $shortcode_manager->format_shortcode_info($shortcode),
        'agent' => $agent_api->format_agent_info($agent['data'])
    ));
}

/**
* Add plugin row meta links.
*
* @since    1.0.0
* @param    array     $links    Plugin row meta links.
* @param    string    $file     Plugin base file.
* @return   array               Modified plugin row meta links.
*/
public function add_row_meta($links, $file) {
if (plugin_basename(STRAICO_INTEGRATION_PATH . 'straico-integration.php') === $file) {
    $row_meta = array(
        'docs' => '<a href="https://straico.com/docs" target="_blank">' . 
            __('Documentation', 'straico-integration') . '</a>',
        'support' => '<a href="https://straico.com/support" target="_blank">' . 
            __('Support', 'straico-integration') . '</a>'
    );

    return array_merge($links, $row_meta);
}

return $links;

}
}
