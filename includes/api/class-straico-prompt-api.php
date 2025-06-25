<?php
/**
 * Handles prompt-related API interactions with Straico.
 *
 * This class provides methods for submitting prompts and handling responses,
 * including file uploads and multi-model completions.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_Prompt_API extends Straico_API_Base {

    /**
     * The security class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Security    $security    Handles security operations.
     */
    private $security;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
        $this->security = new Straico_Security();
    }

    /**
     * Submit a prompt completion request (v1).
     *
     * @since    1.0.0
     * @param    array     $models          Array of model identifiers.
     * @param    string    $message         The prompt text.
     * @param    array     $file_urls       Optional. Array of file URLs.
     * @param    array     $youtube_urls    Optional. Array of YouTube URLs.
     * @param    array     $options         Optional. Additional options.
     * @return   array|WP_Error            The API response or error on failure.
     */
    public function submit_prompt($models, $message, $file_urls = array(), $youtube_urls = array(), $options = array()) {
        // Test API key validity first
        $api_key_test = $this->security->test_api_key($this->api_key);
        if (is_wp_error($api_key_test)) {
            error_log('Straico Prompt API - API Key Test Failed: ' . $api_key_test->get_error_message());
            return $api_key_test;
        }

        // Log the request data for debugging
        error_log('Straico Prompt API - Request Data:');
        error_log('Models: ' . print_r($models, true));
        error_log('Message: ' . $message);
        error_log('File URLs: ' . print_r($file_urls, true));
        error_log('YouTube URLs: ' . print_r($youtube_urls, true));
        error_log('Options: ' . print_r($options, true));

        // Validate request data
        $validation_result = $this->validate_prompt_data(array(
            'models' => $models,
            'message' => $message,
            'file_urls' => $file_urls,
            'youtube_urls' => $youtube_urls,
            'display_transcripts' => isset($options['display_transcripts']) ? $options['display_transcripts'] : false
        ));

        if (is_wp_error($validation_result)) {
            error_log('Straico Prompt API - Validation Failed: ' . $validation_result->get_error_message());
            return $validation_result;
        }

        $data = array(
            'models' => $models,
            'message' => $message
        );

        if (!empty($file_urls)) {
            $data['file_urls'] = $file_urls;
        }

        if (!empty($youtube_urls)) {
            $data['youtube_urls'] = $youtube_urls;
        }

        // Add optional parameters if provided
        $valid_options = array(
            'temperature',
            'max_tokens'
        );

        // Only include display_transcripts if it's true
        if (!empty($options['display_transcripts'])) {
            $data['display_transcripts'] = true;
        }

        foreach ($valid_options as $option) {
            if (isset($options[$option])) {
                $data[$option] = $options[$option];
            }
        }

        try {
            $response = $this->post('v1/prompt/completion', $data);
            
            if (is_wp_error($response)) {
                error_log('Straico Prompt API - API Request Failed: ' . $response->get_error_message());
                return $response;
            }

            // Validate response structure
            if (!isset($response['success']) || !$response['success'] || !isset($response['data'])) {
                error_log('Straico Prompt API - Invalid Response Structure: ' . print_r($response, true));
                return new WP_Error(
                    'invalid_response',
                    __('Invalid response from Straico API. Please try again.', 'straico-integration')
                );
            }

            // Check for specific error messages in the response
            if (isset($response['error'])) {
                error_log('Straico Prompt API - API Error: ' . $response['error']);
                return new WP_Error(
                    'api_error',
                    $response['error']
                );
            }

            return $response;

        } catch (Exception $e) {
            error_log('Straico Prompt API - Exception: ' . $e->getMessage());
            return new WP_Error(
                'api_exception',
                sprintf(
                    /* translators: %s: error message */
                    __('An error occurred while processing your request: %s', 'straico-integration'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Format prompt completion response for display.
     *
     * @since    1.0.0
     * @param    array    $response    Raw API response.
     * @return   array                 Formatted response.
     */
    public function format_completion_response($response) {
        error_log('Straico Prompt API - Formatting Response: ' . print_r($response, true));

        if (!isset($response['data'])) {
            error_log('Straico Prompt API - Error: Invalid response format (missing data)');
            return array(
                'overall_price' => array(
                    'input' => 0,
                    'output' => 0,
                    'total' => 0
                ),
                'overall_words' => array(
                    'input' => 0,
                    'output' => 0,
                    'total' => 0
                ),
                'completions' => array(),
                'transcripts' => array()
            );
        }

        try {
            $formatted = array(
                'overall_price' => $response['data']['overall_price'],
                'overall_words' => $response['data']['overall_words'],
                'completions' => array(),
                'transcripts' => isset($response['data']['transcripts']) ? 
                    $response['data']['transcripts'] : array()
            );

            foreach ($response['data']['completions'] as $model => $completion) {
                $formatted['completions'][$model] = array(
                    'completion' => $completion['completion'],
                    'price' => $completion['price'],
                    'words' => $completion['words']
                );
            }

            error_log('Straico Prompt API - Formatted Response: ' . print_r($formatted, true));
            return $formatted;

        } catch (Exception $e) {
            error_log('Straico Prompt API - Format Error: ' . $e->getMessage());
            return array(
                'overall_price' => array('input' => 0, 'output' => 0, 'total' => 0),
                'overall_words' => array('input' => 0, 'output' => 0, 'total' => 0),
                'completions' => array(),
                'transcripts' => array(),
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * Validate prompt completion request data.
     *
     * @since    1.0.0
     * @param    array     $data    The request data to validate.
     * @return   bool|WP_Error      True if valid, WP_Error if invalid.
     */
    public function validate_prompt_data($data) {
        error_log('Straico Prompt API - Validating Data: ' . print_r($data, true));

        // Validate models
        if (!isset($data['models']) || !is_array($data['models']) || empty($data['models'])) {
            error_log('Straico Prompt API - Validation Error: Invalid or missing models');
            return new WP_Error(
                'invalid_models',
                __('Please select at least one model.', 'straico-integration')
            );
        }

        if (count($data['models']) > 4) {
            error_log('Straico Prompt API - Validation Error: Too many models');
            return new WP_Error(
                'too_many_models',
                __('Maximum of 4 models allowed per request.', 'straico-integration')
            );
        }

        // Validate message
        if (!isset($data['message']) || empty(trim($data['message']))) {
            error_log('Straico Prompt API - Validation Error: Invalid or missing message');
            return new WP_Error(
                'invalid_message',
                __('Please enter a message.', 'straico-integration')
            );
        }

        // Only validate URLs if display_transcripts is true
        if (!empty($data['display_transcripts'])) {
            // Validate file URLs if provided
            if (isset($data['file_urls']) && !empty($data['file_urls'])) {
                if (!is_array($data['file_urls'])) {
                    error_log('Straico Prompt API - Validation Error: Invalid file URLs format');
                    return new WP_Error(
                        'invalid_file_urls',
                        __('Invalid file URL format.', 'straico-integration')
                    );
                }

                if (count($data['file_urls']) > 4) {
                    error_log('Straico Prompt API - Validation Error: Too many files');
                    return new WP_Error(
                        'too_many_files',
                        __('Maximum of 4 files allowed per request.', 'straico-integration')
                    );
                }

                foreach ($data['file_urls'] as $url) {
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {
                        error_log('Straico Prompt API - Validation Error: Invalid file URL: ' . $url);
                        return new WP_Error(
                            'invalid_file_url',
                            sprintf(
                                /* translators: %s: invalid URL */
                                __('Invalid file URL: %s', 'straico-integration'),
                                $url
                            )
                        );
                    }
                }
            }

            // Validate YouTube URLs if provided
            if (isset($data['youtube_urls']) && !empty($data['youtube_urls'])) {
                if (!is_array($data['youtube_urls'])) {
                    error_log('Straico Prompt API - Validation Error: Invalid YouTube URLs format');
                    return new WP_Error(
                        'invalid_youtube_urls',
                        __('Invalid YouTube URL format.', 'straico-integration')
                    );
                }

                if (count($data['youtube_urls']) > 4) {
                    error_log('Straico Prompt API - Validation Error: Too many YouTube URLs');
                    return new WP_Error(
                        'too_many_youtube_urls',
                        __('Maximum of 4 YouTube videos allowed per request.', 'straico-integration')
                    );
                }

                foreach ($data['youtube_urls'] as $url) {
                    if (!preg_match('/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+$/', $url)) {
                        error_log('Straico Prompt API - Validation Error: Invalid YouTube URL: ' . $url);
                        return new WP_Error(
                            'invalid_youtube_url',
                            sprintf(
                                /* translators: %s: invalid URL */
                                __('Invalid YouTube URL: %s', 'straico-integration'),
                                $url
                            )
                        );
                    }
                }
            }

            // If display_transcripts is true, require either file URLs or YouTube URLs
            if (empty($data['file_urls']) && empty($data['youtube_urls'])) {
                error_log('Straico Prompt API - Validation Error: No files or YouTube URLs provided with display_transcripts enabled');
                return new WP_Error(
                    'missing_urls',
                    __('Either File URLs or YouTube URLs are required to display transcripts.', 'straico-integration')
                );
            }
        }

        error_log('Straico Prompt API - Validation Passed');
        return true;
    }

    /**
     * Get default prompt completion options.
     *
     * @since    1.0.0
     * @return   array    Default options for prompt completion.
     */
    public function get_default_options() {
        return array(
            'temperature' => 0.7,
            'max_tokens' => null,
            'display_transcripts' => false
        );
    }
}
