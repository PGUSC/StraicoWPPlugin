<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the public-facing side of the site.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/public
 */

class Straico_Public {

    /**
     * The shortcode manager class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Shortcode_Manager    $shortcode_manager    Handles shortcode operations.
     */
    private $shortcode_manager;

    /**
     * The shortcode renderer class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Shortcode_Renderer    $shortcode_renderer    Handles shortcode rendering.
     */
    private $shortcode_renderer;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->shortcode_manager = new Straico_Shortcode_Manager();
        $this->shortcode_renderer = new Straico_Shortcode_Renderer();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Only enqueue if a shortcode is present
        if (!$this->has_shortcode()) {
            return;
        }

        wp_enqueue_style(
            'straico-public',
            STRAICO_INTEGRATION_URL . 'public/css/straico-public.css',
            array(),
            STRAICO_INTEGRATION_VERSION,
            'all'
        );

        // Add inline styles from shortcode renderer
        wp_add_inline_style(
            'straico-public',
            $this->shortcode_renderer->get_default_styles()
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue if a shortcode is present
        if (!$this->has_shortcode()) {
            return;
        }

        wp_enqueue_script(
            'straico-public',
            STRAICO_INTEGRATION_URL . 'public/js/straico-public.js',
            array('jquery'),
            STRAICO_INTEGRATION_VERSION,
            true
        );

        wp_localize_script('straico-public', 'straicoPublic', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('straico_public_nonce'),
            'strings' => array(
                'error' => __('An error occurred. Please try again.', 'straico-integration'),
                'loading' => __('Processing...', 'straico-integration')
            )
        ));
    }

    /**
     * Check if the current post/page contains a Straico shortcode.
     *
     * @since    1.0.0
     * @access   private
     * @return   bool    Whether a shortcode is present.
     */
    private function has_shortcode() {
        global $post;

        if (!is_singular() || !is_a($post, 'WP_Post')) {
            return false;
        }

        // Get all registered shortcodes
        $shortcodes = $this->shortcode_manager->get_shortcodes();
        if (empty($shortcodes)) {
            return false;
        }

        // Check each shortcode
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, 'straico_' . $shortcode->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Register AJAX handlers for public-facing functionality.
     *
     * @since    1.0.0
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_nopriv_straico_agent_prompt', array($this->shortcode_renderer, 'handle_prompt_ajax'));
        add_action('wp_ajax_straico_agent_prompt', array($this->shortcode_renderer, 'handle_prompt_ajax'));
    }

    /**
     * Add custom classes to the body tag when a shortcode is present.
     *
     * @since    1.0.0
     * @param    array    $classes    Array of body classes.
     * @return   array               Modified array of body classes.
     */
    public function add_body_classes($classes) {
        if ($this->has_shortcode()) {
            $classes[] = 'has-straico-shortcode';
        }
        return $classes;
    }

    /**
     * Clean up shortcode output.
     *
     * @since    1.0.0
     * @param    string    $content    The content to clean.
     * @return   string               The cleaned content.
     */
    public function clean_shortcode_output($content) {
        // Remove empty paragraphs around shortcodes
        $content = preg_replace('/<p>\s*\[straico_.*?\]\s*<\/p>/s', '[\\0]', $content);
        $content = preg_replace('/<p>\s*<div class="straico-.*?<\/div>\s*<\/p>/s', '\\0', $content);

        return $content;
    }

    /**
     * Add custom query vars for AJAX requests.
     *
     * @since    1.0.0
     * @param    array    $vars    Array of query vars.
     * @return   array            Modified array of query vars.
     */
    public function add_query_vars($vars) {
        $vars[] = 'straico_action';
        $vars[] = 'straico_nonce';
        return $vars;
    }

    /**
     * Handle front-end form submissions.
     *
     * @since    1.0.0
     */
    public function handle_form_submissions() {
        $action = get_query_var('straico_action');
        if (!$action) {
            return;
        }

        // Verify nonce
        $nonce = get_query_var('straico_nonce');
        if (!wp_verify_nonce($nonce, 'straico_public_nonce')) {
            wp_die(__('Invalid security token.', 'straico-integration'));
        }

        // Handle different actions
        switch ($action) {
            case 'agent_prompt':
                $this->shortcode_renderer->handle_prompt_ajax();
                break;
            default:
                wp_die(__('Invalid action.', 'straico-integration'));
        }
    }

    /**
     * Add custom meta tags to the head.
     *
     * @since    1.0.0
     */
    public function add_meta_tags() {
        if (!$this->has_shortcode()) {
            return;
        }

        echo '<meta name="straico-integration" content="' . esc_attr(STRAICO_INTEGRATION_VERSION) . '">' . "\n";
    }

    /**
     * Add custom HTML classes to shortcode containers.
     *
     * @since    1.0.0
     * @param    array     $classes    Array of HTML classes.
     * @param    string    $context    The context for the classes.
     * @return   array                Modified array of HTML classes.
     */
    public function add_html_classes($classes, $context = '') {
        if ($context === 'straico-prompt-container') {
            $classes[] = 'straico-theme-default';
            if (wp_is_mobile()) {
                $classes[] = 'straico-mobile';
            }
        }
        return $classes;
    }

    /**
     * Filter shortcode attributes before rendering.
     *
     * @since    1.0.0
     * @param    array     $atts       Shortcode attributes.
     * @param    string    $content    Shortcode content.
     * @param    string    $tag        Shortcode tag.
     * @return   array                Modified shortcode attributes.
     */
    public function filter_shortcode_atts($atts, $content, $tag) {
        if (strpos($tag, 'straico_') !== 0) {
            return $atts;
        }

        // Add default attributes
        $defaults = array(
            'class' => '',
            'style' => ''
        );

        return wp_parse_args($atts, $defaults);
    }

    /**
     * Add custom error handling for shortcodes.
     *
     * @since    1.0.0
     * @param    callable    $callback     Error callback function.
     * @param    string     $shortcode    Shortcode tag.
     * @param    array      $atts         Shortcode attributes.
     */
    public function handle_shortcode_error($callback, $shortcode, $atts) {
        if (strpos($shortcode, 'straico_') !== 0) {
            return;
        }

        set_error_handler(function($errno, $errstr) use ($shortcode) {
            error_log(sprintf(
                '[Straico] Shortcode error in %s: %s',
                $shortcode,
                $errstr
            ));
            return true;
        });

        call_user_func($callback);

        restore_error_handler();
    }
}
