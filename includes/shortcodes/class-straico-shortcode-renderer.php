<?php
/**
 * Handles shortcode rendering for the plugin.
 *
 * This class manages the rendering of agent prompt completion forms
 * and responses in posts and pages via shortcodes.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/shortcodes
 */

class Straico_Shortcode_Renderer {

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
        $this->db_manager = new Straico_DB_Manager();
        $this->agent_api = new Straico_Agent_API();
        $this->security = new Straico_Security();

        // Register AJAX handlers
        add_action('wp_ajax_straico_agent_prompt', array($this, 'handle_prompt_ajax'));
        add_action('wp_ajax_nopriv_straico_agent_prompt', array($this, 'handle_prompt_ajax'));
    }

    /**
     * Render a shortcode.
     *
     * @since    1.0.0
     * @param    array     $atts       Shortcode attributes.
     * @param    string    $content    Shortcode content.
     * @param    string    $tag        Shortcode tag.
     * @return   string                The rendered shortcode output.
     */
    public function render_shortcode($atts, $content = null, $tag = '') {
        // Extract shortcode name from tag
        $shortcode_name = str_replace('straico_', '', $tag);

        // Get shortcode configuration
        $shortcode = $this->db_manager->get_shortcode_by_name($shortcode_name);
        if (!$shortcode) {
            return sprintf(
                '<p class="straico-error">%s</p>',
                esc_html__('Shortcode configuration not found.', 'straico-integration')
            );
        }

        // Get agent details
        $agent = $this->agent_api->get_agent_details($shortcode->agent_id);
        if (is_wp_error($agent)) {
            return sprintf(
                '<p class="straico-error">%s</p>',
                esc_html__('Agent not found.', 'straico-integration')
            );
        }

        // Generate unique container ID
        $container_id = 'straico-' . uniqid();

        // Enqueue required scripts and styles
        wp_enqueue_style('straico-public');
        wp_enqueue_script('straico-public');

        // Localize script with shortcode settings
        wp_localize_script('straico-public', 'straicoShortcode_' . $container_id, array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('straico_agent_prompt'),
            'shortcode_name' => $shortcode_name,
            'agent_id' => $shortcode->agent_id,
            'settings' => $shortcode->settings,
            'container_id' => $container_id
        ));

        // Start output buffering
        ob_start();
        ?>
        <div id="<?php echo esc_attr($container_id); ?>" class="straico-prompt-container">
            <form class="straico-prompt-form">
                <?php wp_nonce_field('straico_agent_prompt', 'straico_nonce'); ?>
                <input type="hidden" name="shortcode_name" value="<?php echo esc_attr($shortcode_name); ?>">
                <input type="hidden" name="agent_id" value="<?php echo esc_attr($shortcode->agent_id); ?>">
                
                <div class="straico-prompt-input">
                    <textarea 
                        name="prompt" 
                        rows="3" 
                        placeholder="<?php echo esc_attr($shortcode->settings['prompt_placeholder']); ?>"
                        required
                    ></textarea>
                </div>

                <div class="straico-prompt-actions">
                    <button type="submit" class="straico-submit-button">
                        <?php echo esc_html($shortcode->settings['submit_button_text']); ?>
                    </button>

                    <?php if ($shortcode->settings['display_reset']) : ?>
                        <button type="reset" class="straico-reset-button">
                            <?php echo esc_html($shortcode->settings['reset_button_text']); ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="straico-loading" style="display: none;">
                    <?php echo esc_html($shortcode->settings['loading_text']); ?>
                </div>

                <div class="straico-error" style="display: none;">
                    <?php echo esc_html($shortcode->settings['error_text']); ?>
                </div>

                <div class="straico-response" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX prompt submissions.
     *
     * @since    1.0.0
     */
    public function handle_prompt_ajax() {
        // Verify nonce
        if (!check_ajax_referer('straico_agent_prompt', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Invalid security token.', 'straico-integration')
            ));
        }

        // Get and validate input
        $shortcode_name = isset($_POST['shortcode_name']) ? sanitize_text_field($_POST['shortcode_name']) : '';
        $agent_id = isset($_POST['agent_id']) ? sanitize_text_field($_POST['agent_id']) : '';
        $prompt = isset($_POST['prompt']) ? $this->security->sanitize_textarea($_POST['prompt']) : '';

        if (empty($shortcode_name) || empty($agent_id) || empty($prompt)) {
            wp_send_json_error(array(
                'message' => __('Missing required parameters.', 'straico-integration')
            ));
        }

        // Get shortcode configuration
        $shortcode = $this->db_manager->get_shortcode_by_name($shortcode_name);
        if (!$shortcode) {
            wp_send_json_error(array(
                'message' => __('Shortcode configuration not found.', 'straico-integration')
            ));
        }

        // Verify agent ID matches
        if ($shortcode->agent_id !== $agent_id) {
            wp_send_json_error(array(
                'message' => __('Invalid agent ID.', 'straico-integration')
            ));
        }

        // Submit prompt to agent
        $response = $this->agent_api->prompt_agent($agent_id, $prompt, array(
            'temperature' => floatval($shortcode->settings['temperature']),
            'max_tokens' => intval($shortcode->settings['max_tokens'])
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message()
            ));
        }

        // Format the response
        $formatted = $this->agent_api->format_prompt_response($response);

        // Build the response HTML
        $html = '<div class="straico-response-content">';
        $html .= '<div class="straico-response-answer">' . wp_kses_post(nl2br($formatted['answer'])) . '</div>';

        if (!empty($formatted['references'])) {
            $html .= '<div class="straico-response-references">';
            $html .= '<h4>' . esc_html__('References:', 'straico-integration') . '</h4>';
            $html .= '<ul>';
            foreach ($formatted['references'] as $ref) {
                $html .= '<li>';
                $html .= '<div class="straico-reference-content">' . wp_kses_post(nl2br($ref['content'])) . '</div>';
                if ($ref['page']) {
                    $html .= '<div class="straico-reference-page">' . sprintf(
                        /* translators: %d: page number */
                        esc_html__('Page %d', 'straico-integration'),
                        intval($ref['page'])
                    ) . '</div>';
                }
                $html .= '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        if ($formatted['coins_used']) {
            $html .= '<div class="straico-response-cost">' . sprintf(
                /* translators: %s: number of coins */
                esc_html__('Cost: %s coins', 'straico-integration'),
                number_format($formatted['coins_used'], 2)
            ) . '</div>';
        }

        $html .= '</div>';

        wp_send_json_success(array(
            'html' => $html
        ));
    }

    /**
     * Get default styles for shortcode output.
     *
     * @since    1.0.0
     * @return   string    CSS styles for shortcode output.
     */
    public function get_default_styles() {
        return "
            .straico-prompt-container {
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .straico-prompt-input textarea {
                width: 100%;
                padding: 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                resize: vertical;
                min-height: 100px;
            }

            .straico-prompt-actions {
                margin-top: 15px;
                display: flex;
                gap: 10px;
            }

            .straico-submit-button,
            .straico-reset-button {
                padding: 8px 16px;
                border-radius: 4px;
                border: none;
                cursor: pointer;
                font-weight: 600;
            }

            .straico-submit-button {
                background: #007bff;
                color: #fff;
            }

            .straico-reset-button {
                background: #6c757d;
                color: #fff;
            }

            .straico-loading,
            .straico-error {
                margin-top: 15px;
                padding: 10px;
                border-radius: 4px;
                text-align: center;
            }

            .straico-loading {
                background: #e9ecef;
            }

            .straico-error {
                background: #f8d7da;
                color: #721c24;
            }

            .straico-response {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 4px;
            }

            .straico-response-answer {
                margin-bottom: 20px;
                line-height: 1.6;
            }

            .straico-response-references {
                border-top: 1px solid #ddd;
                padding-top: 15px;
            }

            .straico-response-references h4 {
                margin: 0 0 10px;
                font-size: 16px;
            }

            .straico-response-references ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .straico-response-references li {
                margin-bottom: 15px;
                padding: 10px;
                background: #fff;
                border-radius: 4px;
                border: 1px solid #ddd;
            }

            .straico-reference-content {
                margin-bottom: 5px;
            }

            .straico-reference-page {
                font-size: 12px;
                color: #6c757d;
            }

            .straico-response-cost {
                margin-top: 15px;
                text-align: right;
                font-size: 14px;
                color: #6c757d;
            }
        ";
    }
}
