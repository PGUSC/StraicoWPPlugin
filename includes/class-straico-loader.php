<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes
 */

class Straico_Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Core plugin classes
        require_once STRAICO_INTEGRATION_PATH . 'includes/class-straico-security.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/class-straico-options.php';

        // API classes
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-api-base.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-user-api.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-models-api.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-rag-api.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-agent-api.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-prompt-api.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/api/class-straico-file-api.php';

        // Admin and public classes
        require_once STRAICO_INTEGRATION_PATH . 'admin/class-straico-admin.php';
        require_once STRAICO_INTEGRATION_PATH . 'public/class-straico-public.php';

        // Database manager
        require_once STRAICO_INTEGRATION_PATH . 'includes/db/class-straico-db-manager.php';

        // Cron manager and jobs
        require_once STRAICO_INTEGRATION_PATH . 'includes/cron/class-straico-cron-manager.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/cron/class-straico-model-updates.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/cron/class-straico-balance-check.php';

        // Shortcode classes
        require_once STRAICO_INTEGRATION_PATH . 'includes/shortcodes/class-straico-shortcode-manager.php';
        require_once STRAICO_INTEGRATION_PATH . 'includes/shortcodes/class-straico-shortcode-renderer.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Straico_Admin();

        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->add_action('admin_init', $plugin_admin, 'register_settings');

        // Initialize options and register cron update hooks
        $options = new Straico_Options();
        $this->add_action('admin_init', $options, 'register_cron_update_hooks');

        // Initialize cron functionality
        $cron_manager = new Straico_Cron_Manager();
        $model_updates = new Straico_Model_Updates();
        $balance_check = new Straico_Balance_Check();

        // Register activation/deactivation hooks for cron jobs
        register_activation_hook(STRAICO_INTEGRATION_PATH . 'straico-integration.php', array($cron_manager, 'activate_cron_jobs'));
        register_deactivation_hook(STRAICO_INTEGRATION_PATH . 'straico-integration.php', array($cron_manager, 'deactivate_cron_jobs'));
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Straico_Public();

        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $shortcode_manager = new Straico_Shortcode_Manager();
        $this->add_action('init', $shortcode_manager, 'register_shortcodes');
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string    $hook             The name of the WordPress action that is being registered.
     * @param    object    $component        A reference to the instance of the object on which the action is defined.
     * @param    string    $callback         The name of the function definition on the $component.
     * @param    int       $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int       $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string    $hook             The name of the WordPress filter that is being registered.
     * @param    object    $component        A reference to the instance of the object on which the filter is defined.
     * @param    string    $callback         The name of the function definition on the $component.
     * @param    int       $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int       $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string    $hook             The name of the WordPress filter that is being registered.
     * @param    object    $component        A reference to the instance of the object on which the filter is defined.
     * @param    string    $callback         The name of the function definition on the $component.
     * @param    int       $priority         The priority at which the function should be fired.
     * @param    int       $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                       The collection of actions and filters registered with WordPress.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        // Register all actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        // Register all filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
