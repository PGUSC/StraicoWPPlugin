# Straico Integration Developer Documentation

This document provides comprehensive technical documentation for developers contributing to the Straico Integration WordPress plugin. Its goal is to equip any developer or LLM with the necessary understanding of the plugin's files, functions, structure, features, and other elements to begin contributing effectively.

## Table of Contents
- [Purpose of This Document](#purpose-of-this-document)
- [Getting Started / Development Environment Setup](#getting-started--development-environment-setup)
- [Architecture Overview](#architecture-overview)
  - [Logical Layers](#logical-layers)
  - [Key Design Principles & Patterns](#key-design-principles--patterns)
- [Directory Structure](#directory-structure)
- [Core Components Deep Dive](#core-components-deep-dive)
  - [Main Plugin File (`straico-integration.php`)](#main-plugin-file-straico-integrationphp)
  - [Loader (`includes/class-straico-loader.php`)](#loader-includesclass-straico-loaderphp)
  - [Admin Handler (`admin/class-straico-admin.php`)](#admin-handler-adminclass-straico-adminphp)
  - [Public Handler (`public/class-straico-public.php`)](#public-handler-publicclass-straico-publicphp)
  - [Options Manager (`includes/class-straico-options.php`)](#options-manager-includesclass-straico-optionsphp)
  - [Security Handler (`includes/class-straico-security.php`)](#security-handler-includesclass-straico-securityphp)
  - [Database Manager (`includes/db/class-straico-db-manager.php`)](#database-manager-includesdbclass-straico-db-managerphp)
  - [Cron Manager (`includes/cron/class-straico-cron-manager.php`)](#cron-manager-includescronclass-straico-cron-managerphp)
  - [Shortcode Manager & Renderer (`includes/shortcodes/`)](#shortcode-manager--renderer-includesshortcodes)
- [API Integration](#api-integration)
  - [Base API Class (`includes/api/class-straico-api-base.php`)](#base-api-class-includesapiclass-straico-api-basephp)
  - [Specific API Client Classes (`includes/api/class-straico-*-api.php`)](#specific-api-client-classes-includesapiclass-straico--apiphp)
- [JavaScript Interactions](#javascript-interactions)
  - [Admin JavaScript (`admin/js/`)](#admin-javascript-adminjs)
  - [Public JavaScript (`public/js/straico-public.js`)](#public-javascript-publicjsstraico-publicjs)
- [Key Feature Flows (Developer Perspective)](#key-feature-flows-developer-perspective)
  - [User Submitting a Prompt via Shortcode (Frontend)](#user-submitting-a-prompt-via-shortcode-frontend)
  - [Admin Creating a New RAG](#admin-creating-a-new-rag)
  - [Admin Creating a New Agent](#admin-creating-a-new-agent)
  - [Model Update Cron Job Execution](#model-update-cron-job-execution)
  - [Coin Balance Check Cron Job Execution](#coin-balance-check-cron-job-execution)
- [Database Schema](#database-schema)
  - [Shortcodes Table (`wp_straico_shortcodes`)](#shortcodes-table-wp_straico_shortcodes)
  - [Model Changes Table (`wp_straico_model_changes`)](#model-changes-table-wp_straico_model_changes)
  - [Coin History Table (`wp_straico_coin_history`)](#coin-history-table-wp_straico_coin_history)
- [Development Guidelines](#development-guidelines)
  - [Coding Standards](#coding-standards)
  - [File Organization](#file-organization)
  - [AJAX Handlers](#ajax-handlers)
  - [Best Practices](#best-practices)
- [Adding New Features - Conceptual Walkthroughs](#adding-new-features---conceptual-walkthroughs)
  - [Adding a New Admin Page](#adding-a-new-admin-page)
  - [Integrating a New Straico API Endpoint](#integrating-a-new-straico-api-endpoint)
- [Debugging Tips](#debugging-tips)
- [Security Considerations](#security-considerations)
  - [API Key Management](#api-key-management)
  - [Input Validation & Output Escaping](#input-validation--output-escaping)
  - [Access Control & Nonces](#access-control--nonces)
- [Updating This Documentation](#updating-this-documentation)
- [Additional Resources](#additional-resources)

## Purpose of This Document
This document serves as the primary technical guide for developers working on the Straico Integration WordPress plugin. It details the plugin's architecture, core components, data flows, and development practices. The aim is to provide a solid foundation for understanding the existing codebase, contributing new features, and maintaining the plugin effectively.

## Getting Started / Development Environment Setup

1.  **Prerequisites:**
    *   A local WordPress installation (e.g., via LocalWP, Docker, XAMPP, MAMP).
    *   PHP 7.4 or higher (as specified in `README.md`).
    *   MySQL 5.6 or higher.
    *   A code editor (e.g., VS Code).
    *   Access to a Straico account and an API key for testing integration features.

2.  **Cloning the Repository:**
    Clone the plugin repository into your WordPress installation's `wp-content/plugins/` directory.

3.  **WordPress Debug Mode:**
    It is highly recommended to enable WordPress debugging features in your `wp-config.php` file:
    ```php
    define( 'WP_DEBUG', true );
    define( 'WP_DEBUG_LOG', true ); // Errors will be logged to wp-content/debug.log
    define( 'WP_DEBUG_DISPLAY', false ); // Don't display errors on screen for production-like testing
    @ini_set( 'display_errors', 0 );
    define( 'SCRIPT_DEBUG', true ); // Use non-minified JS/CSS core files
    ```

4.  **Recommended Tools:**
    *   **Xdebug:** For step-debugging PHP code.
    *   **Browser Developer Tools:** Essential for inspecting HTML, CSS, JavaScript, and network requests.
    *   **WP-CLI:** Useful for various WordPress management tasks from the command line.

5.  **Activation:**
    Activate the plugin through the WordPress admin area. Navigate to "Straico Settings" to enter your API key.

## Architecture Overview

The plugin is designed with a modular architecture, emphasizing separation of concerns to enhance maintainability and scalability.

### Logical Layers

1.  **Initialization & Orchestration Layer (`straico-integration.php`, `includes/class-straico-loader.php`):**
    *   Handles the initial plugin setup, defines constants, and loads all necessary dependencies.
    *   The `Straico_Loader` class is central to registering WordPress actions and filters, connecting various plugin components to the WordPress core event system.

2.  **Admin Interface Layer (`admin/`):**
    *   Manages all aspects of the WordPress admin interface for the plugin.
    *   `class-straico-admin.php` sets up admin menus, enqueues scripts/styles, and handles AJAX requests originating from admin pages.
    *   `admin/partials/` contains the HTML/PHP templates for admin pages.
    *   `admin/js/` provides client-side interactivity for admin UIs.

3.  **Public Interface Layer (`public/`):**
    *   Handles the plugin's functionality on the public-facing side of the website.
    *   `class-straico-public.php` enqueues public scripts/styles (conditionally, if a shortcode is present) and manages hooks related to front-end display.
    *   `public/js/straico-public.js` powers the interactivity of shortcodes (e.g., AJAX form submissions for prompts).

4.  **Core Services Layer (`includes/` - various sub-components):**
    *   **API Clients (`includes/api/`):** A set of classes dedicated to communicating with the external Straico API. Each class typically maps to a group of related API endpoints.
    *   **Database Management (`includes/db/class-straico-db-manager.php`):** Manages custom database tables for storing plugin-specific data like shortcode configurations and logs.
    *   **Cron Jobs (`includes/cron/`):** Handles scheduled tasks such as fetching model updates and checking user coin balances.
    *   **Shortcode Logic (`includes/shortcodes/`):** Manages the registration, storage, and rendering of shortcodes.
    *   **Settings Management (`includes/class-straico-options.php`):** Utilizes the WordPress Settings API to store and retrieve plugin configurations.
    *   **Security (`includes/class-straico-security.php`):** Centralizes security-related functions like nonce handling, input sanitization, and API key validation.

### Key Design Principles & Patterns

*   **WordPress Plugin API & Hooks:** The plugin extensively uses WordPress actions and filters (`add_action`, `add_filter`) for integration and extensibility. This is a form of **Event-Driven Architecture**.
*   **Separation of Concerns:** Different functionalities (admin, public, API communication, DB, cron) are handled by distinct classes and organized into logical directories.
*   **Facade/Adapter (API Classes):** The classes in `includes/api/` act as facades or adapters to the external Straico API, simplifying interactions from other parts of the plugin.
*   **Dependency Management (via `Straico_Loader`):** While not a full dependency injection container, the loader centralizes the inclusion (`require_once`) of class files.
*   **AJAX for Dynamic UI:** Both admin and public interfaces use AJAX for operations like form submissions, fetching details, and deletions to provide a smoother user experience.
*   **Modularity in JavaScript:** Client-side JavaScript is broken down into modules/objects for better organization (e.g., `Modal`, `Form`, `FileUpload` in `straico-admin.js`).
*   **Use of Transients:** WordPress transients are used for caching data that doesn't change frequently, like the list of available models (`straico_previous_models`) and the status of the last balance notification (`straico_last_balance_notification`), to reduce API calls.

## Directory Structure

(The directory structure provided in the original `CONTRIBUTING.md` is largely accurate and detailed. This section will be maintained and verified for any new additions.)

```
straico-integration/
├── straico-integration.php        # Plugin initialization and hooks
├── uninstall.php                  # Cleanup on plugin uninstall
├── README.md                      # User-facing documentation
├── CONTRIBUTING.md                # This developer documentation
├── admin/                         # Admin interface files
│   ├── class-straico-admin.php    # Admin initialization, menu setup, AJAX handlers
│   ├── css/
│   │   ├── straico-admin.css      # General admin styles
│   │   └── prompt-completion.css  # Styles specific to prompt completion pages
│   ├── js/
│   │   ├── straico-admin.js       # Common admin JavaScript utilities (modals, forms, etc.)
│   │   ├── agent-management.js    # JS for agent management pages
│   │   ├── prompt-completion.js   # JS for prompt completion interfaces
│   │   └── rag-management.js      # JS for RAG management pages
│   └── partials/                  # Admin page PHP templates (views)
│       ├── agent-management/      # Templates for agent creation, management, connect RAG
│       ├── models-info/           # Template for displaying models information
│       ├── prompt-completions/    # Templates for basic, RAG, and agent prompt UIs
│       ├── rag-management/        # Templates for RAG creation and management
│       ├── settings/              # Template for the main plugin settings page
│       ├── shortcodes/            # Templates for shortcode creation and management
│       └── user-info/             # Template for displaying user account information
├── includes/                      # Core functionality, libraries, and business logic
│   ├── class-straico-loader.php   # Loads dependencies and registers WordPress hooks
│   ├── class-straico-security.php # Handles security functions (nonce, sanitization, etc.)
│   ├── class-straico-options.php  # Manages plugin settings via WordPress Options API
│   ├── api/                       # Classes for interacting with the Straico API
│   │   ├── class-straico-api-base.php    # Base class for API communication
│   │   ├── class-straico-user-api.php    # Handles /user endpoint (balance, plan)
│   │   ├── class-straico-models-api.php  # Handles /models endpoint
│   │   ├── class-straico-rag-api.php     # Handles /rag endpoints
│   │   ├── class-straico-agent-api.php   # Handles /agent endpoints
│   │   ├── class-straico-prompt-api.php  # Handles /prompt/completion endpoint
│   │   └── class-straico-file-api.php    # Handles /file/upload endpoint
│   ├── cron/                      # Classes for managing WordPress cron jobs
│   │   ├── class-straico-cron-manager.php   # Registers and manages cron schedules
│   │   ├── class-straico-model-updates.php  # Cron job for checking model updates
│   │   └── class-straico-balance-check.php  # Cron job for checking coin balance
│   ├── db/                        # Database management
│   │   └── class-straico-db-manager.php     # Creates and manages custom plugin tables
│   └── shortcodes/                # Shortcode functionality
│       ├── class-straico-shortcode-manager.php   # Manages shortcode creation, storage, registration
│       └── class-straico-shortcode-renderer.php  # Renders shortcode HTML and handles AJAX
└── public/                        # Public-facing functionality
    ├── class-straico-public.php   # Initializes public hooks, enqueues public assets
    ├── css/
    │   └── straico-public.css     # Styles for front-end shortcode output
    └── js/
        └── straico-public.js      # JavaScript for front-end shortcode interactivity
```

## Core Components Deep Dive

### Main Plugin File (`straico-integration.php`)
*   **Responsibilities:** Standard WordPress plugin header, defines global constants (`STRAICO_INTEGRATION_VERSION`, `_PATH`, `_URL`), includes `class-straico-loader.php`, and instantiates `Straico_Loader` to start the plugin. Registers activation hook for DB setup (`Straico_DB_Manager::install()`).
*   **Key Function:** `run_straico_integration()`

### Loader (`includes/class-straico-loader.php`)
*   **Responsibilities:** Central point for loading all plugin class dependencies and registering WordPress actions and filters.
*   **Key Methods:**
    *   `__construct()`: Calls methods to load dependencies and define admin/public hooks.
    *   `load_dependencies()`: Includes all necessary class files from `includes/`, `admin/`, and `public/`.
    *   `define_admin_hooks()`: Instantiates `Straico_Admin`, `Straico_Options`, and cron classes, adding their respective hooks.
    *   `define_public_hooks()`: Instantiates `Straico_Public` and `Straico_Shortcode_Manager`, adding their hooks.
    *   `add_action()`, `add_filter()`: Internal helpers to store hook definitions.
    *   `run()`: Iterates stored hooks and registers them with WordPress using `add_action()` and `add_filter()`.

### Admin Handler (`admin/class-straico-admin.php`)
*   **Responsibilities:** Manages the entire admin-side experience.
*   **Key Methods:**
    *   `__construct()`: Registers numerous AJAX action handlers (e.g., `wp_ajax_straico_create_rag`, `wp_ajax_straico_prompt_completion`).
    *   `handle_*()` AJAX methods: Perform nonce/capability checks, sanitize input, interact with API or DB classes, and return JSON responses.
    *   `enqueue_styles()`, `enqueue_scripts()`: Load admin-specific CSS and JS, including `wp_localize_script` for passing data to JS.
    *   `add_plugin_admin_menu()`: Creates the "Straico" top-level menu and all its submenus.
    *   `display_*_page()`: Callbacks for admin menu pages, typically `require_once` a template from `admin/partials/`.
    *   `register_settings()`: Delegates to `Straico_Options::register_settings()`.

### Public Handler (`public/class-straico-public.php`)
*   **Responsibilities:** Manages front-end display and interactivity, primarily for shortcodes.
*   **Key Methods:**
    *   `__construct()`: Instantiates `Straico_Shortcode_Manager` and `Straico_Shortcode_Renderer`.
    *   `enqueue_styles()`, `enqueue_scripts()`: Conditionally load public CSS/JS if a Straico shortcode is detected on the page (via `has_shortcode()`). Localizes script with AJAX URL and nonce.
    *   `has_shortcode()`: Checks post content for registered Straico shortcodes.
    *   `register_ajax_handlers()`: Registers AJAX handlers for shortcode prompt submissions (`wp_ajax_nopriv_straico_agent_prompt`, `wp_ajax_straico_agent_prompt`), delegating to `Straico_Shortcode_Renderer`.

### Options Manager (`includes/class-straico-options.php`)
*   **Responsibilities:** Manages all plugin settings using the WordPress Settings API.
*   **Key Methods:**
    *   `register_settings()`: Defines settings sections and registers individual settings (API key, frequencies, notification emails, etc.) with their sanitization callbacks and defaults.
    *   `get_*()` methods (e.g., `get_api_key()`): Provide access to option values.
    *   `sanitize_email_list()`, `sanitize_checkbox()`: Custom sanitization callbacks.
    *   `update_cron_schedules()`: Hooked to `update_option_*` for relevant settings, calls `Straico_Cron_Manager` to update cron job schedules.

### Security Handler (`includes/class-straico-security.php`)
*   **Responsibilities:** Centralizes security-related operations.
*   **Key Methods:**
    *   `verify_nonce()`, `nonce_field()`: Nonce management.
    *   `sanitize_*()` methods: Wrappers for WordPress sanitization functions.
    *   `escape_*()` methods: Wrappers for WordPress escaping functions.
    *   `encrypt_data()`, `decrypt_data()`: AES-256-CBC encryption/decryption (Note: Key management for this is not explicitly detailed in the class itself; keys would be passed as parameters).
    *   `validate_api_key()`: Validates format and length of the Straico API key.
    *   `test_api_key()`: Performs a live API call to `v0/user` to verify key validity.

### Database Manager (`includes/db/class-straico-db-manager.php`)
*   **Responsibilities:** Handles all direct database interactions, including custom table creation and CRUD.
*   **Key Methods:**
    *   `install()`: Creates `wp_straico_shortcodes`, `wp_straico_model_changes`, `wp_straico_coin_history` tables using `dbDelta()`.
    *   `uninstall()`: Drops these custom tables.
    *   CRUD methods for shortcodes (`save_shortcode`, `get_shortcode`, etc.).
    *   Logging methods for model changes and coin balance (`log_model_change`, `log_coin_balance`).
    *   `cleanup_old_records()`: Deletes old log entries.

### Cron Manager (`includes/cron/class-straico-cron-manager.php`)
*   **Responsibilities:** Manages scheduling and execution of WordPress cron jobs.
*   **Key Methods:**
    *   `add_cron_intervals()`: Defines custom cron schedules based on plugin settings.
    *   `activate_cron_jobs()`, `deactivate_cron_jobs()`: Schedules/clears cron events on plugin activation/deactivation.
    *   `update_cron_schedules()`: Re-schedules jobs when settings change.
    *   `get_cron_status()`: Provides status information for display in admin.
    *   `log_cron_failure()`: Logs errors and optionally notifies admin.

### Shortcode Manager & Renderer (`includes/shortcodes/`)
*   **`class-straico-shortcode-manager.php`:**
    *   **Responsibilities:** Manages the lifecycle of shortcodes (creation, storage in DB, registration with WordPress).
    *   **Key Methods:** `register_shortcodes()`, `create_shortcode()`, `update_shortcode()`, `delete_shortcode()`, `get_shortcode()`.
*   **`class-straico-shortcode-renderer.php`:**
    *   **Responsibilities:** Renders the HTML output for shortcodes and handles their AJAX interactions on the front end.
    *   **Key Methods:** `render_shortcode()` (generates form HTML), `handle_prompt_ajax()` (processes AJAX prompt submissions from the frontend).

## API Integration

Communication with the external Straico API is a core part of the plugin.

### Base API Class (`includes/api/class-straico-api-base.php`)
*   **Purpose:** Provides common functionality for all Straico API interactions.
*   **Key Features:**
    *   Stores base API URL (`https://api.straico.com`) and retrieves API key via `Straico_Options`.
    *   Protected methods for HTTP requests: `get()`, `post()`, `put()`, `delete()`, which wrap WordPress HTTP API functions.
    *   `build_url()`: Constructs full request URLs.
    *   `get_headers()`: Prepares standard request headers (Authorization Bearer token, Content-Type).
    *   `handle_response()`: Processes API responses, decodes JSON, and handles errors by returning `WP_Error` objects. Includes logging.
    *   `upload_file()`: Specific method for `multipart/form-data` file uploads to `v0/file/upload`.

### Specific API Client Classes (`includes/api/class-straico-*-api.php`)
Each class extends `Straico_API_Base` and implements methods for specific Straico API resources:
*   **`Straico_User_API`:** Interacts with `v0/user` for user info, coin balance, plan.
*   **`Straico_Models_API`:** Interacts with `v1/models` for fetching available AI models, detecting changes, and sending notifications.
*   **`Straico_RAG_API`:** Manages RAGs via `v0/rag/*` endpoints (create, list, get, delete, prompt). Handles complex multipart RAG creation requests.
*   **`Straico_Agent_API`:** Manages agents via `v0/agent/*` endpoints (create, list, get, update, delete, connect RAG, prompt).
*   **`Straico_Prompt_API`:** Handles direct prompt completions via `v1/prompt/completion`, supporting file/YouTube URLs.
*   **`Straico_File_API`:** Dedicated to file uploads (`v0/file/upload`) and file validation.

For a complete reference of all Straico API endpoints, including detailed request and response schemas, authentication methods, and usage examples relevant to this plugin's integration, please consult the [`STRAICO_API_DOCUMENTATION.md`](STRAICO_API_DOCUMENTATION.md) file located in the project root. This document is the primary source for understanding direct API interactions.

## JavaScript Interactions

### Admin JavaScript (`admin/js/`)
*   **`straico-admin.js`:**
    *   **Purpose:** Provides common UI utilities for admin pages.
    *   **Modules:** `Modal` (dialogs), `Form` (generic AJAX form submission), `FileUpload` (dynamic file inputs), `Confirm` (delete confirmations), `Clipboard` (copy to clipboard).
*   **`agent-management.js`:**
    *   **Purpose:** Handles specific interactions for agent management pages.
    *   **Functionality:** AJAX for viewing agent details in a modal, deleting agents (with confirmation modal), submitting agent creation/update forms, and connecting RAGs.
*   **`prompt-completion.js`:**
    *   **Purpose:** Manages the UI for the various prompt completion interfaces in admin.
    *   **Functionality:** Limits model selection, handles AJAX form submission (including optional pre-upload of a single file), dynamically builds and displays API responses (model outputs, costs, transcripts).
*   **`rag-management.js`:**
    *   **Purpose:** Handles specific interactions for RAG management pages.
    *   **Functionality:** AJAX for viewing RAG details in a modal, deleting RAGs (with confirmation modal). RAG creation form submission is likely handled by the generic form handler in `straico-admin.js` due to its `FormData` capabilities for file uploads.

### Public JavaScript (`public/js/straico-public.js`)
*   **Purpose:** Powers the interactivity of Straico shortcodes on the website's front end.
*   **Modules:**
    *   `PromptForm`: Handles AJAX submission of prompts entered into shortcode forms, displays responses (answer, references, cost), manages UI state (loading, errors), and provides reset functionality.
    *   `Accessibility`: Enhances shortcode forms with ARIA attributes for better screen reader support.

## Key Feature Flows (Developer Perspective)

Understanding these flows is crucial for debugging and extending the plugin.

### User Submitting a Prompt via Shortcode (Frontend)
1.  **User Interaction:** User types a prompt into the form rendered by a `[straico_*]` shortcode and clicks "Submit".
2.  **JavaScript Handling (`public/js/straico-public.js`):**
    *   The `PromptForm.handleSubmit()` function is triggered.
    *   It serializes the form data (including hidden fields like `action`, `shortcode_name`, `agent_id`, and the nonce).
    *   An AJAX POST request is made to `straico_public.ajaxurl` (which is `wp-admin/admin-ajax.php`).
3.  **WordPress AJAX Hook (`includes/shortcodes/class-straico-shortcode-renderer.php`):**
    *   The `handle_prompt_ajax()` method (hooked to `wp_ajax_straico_agent_prompt` and `wp_ajax_nopriv_straico_agent_prompt`) receives the request.
    *   **Security:** Verifies the nonce (`check_ajax_referer('straico_agent_prompt', 'nonce')`).
    *   **Data Retrieval:** Sanitizes input (`shortcode_name`, `agent_id`, `prompt`).
    *   Fetches shortcode configuration from the database using `Straico_DB_Manager::get_shortcode_by_name()`.
    *   Verifies that the submitted `agent_id` matches the one in the shortcode configuration.
4.  **API Call (`includes/api/class-straico-agent-api.php`):**
    *   `Straico_Shortcode_Renderer` calls `Straico_Agent_API::prompt_agent()` with the agent ID, prompt, and settings from the shortcode (e.g., temperature, max_tokens).
    *   `Straico_Agent_API::prompt_agent()` constructs the request payload.
    *   It then calls `Straico_API_Base::post()` to send the request to the Straico API endpoint `v0/agent/{agent_id}/prompt`.
5.  **Response Processing:**
    *   `Straico_API_Base::handle_response()` processes the raw API response.
    *   `Straico_Agent_API::format_prompt_response()` formats the successful API data.
    *   `Straico_Shortcode_Renderer::handle_prompt_ajax()` further formats this into an HTML string.
6.  **Return to Frontend:**
    *   `wp_send_json_success()` (or `wp_send_json_error()`) sends the HTML (or error message) back to `public/js/straico-public.js`.
    *   The JavaScript's `handleSuccess()` (or `handleError()`) updates the DOM to display the response or error.

### Admin Creating a New RAG
1.  **User Interaction (Admin):** Admin fills out the "Create RAG" form (template: `admin/partials/rag-management/create-rag.php`) including name, description, files, and chunking options.
2.  **JavaScript Handling (likely `admin/js/straico-admin.js` `Form.handleSubmit` due to `FormData` for files):**
    *   Form submission is intercepted.
    *   `FormData` is created to include all fields and uploaded files.
    *   AJAX POST request to `admin-ajax.php` with `action: 'straico_create_rag'`.
3.  **WordPress AJAX Hook (`admin/class-straico-admin.php`):**
    *   The `handle_create_rag()` method receives the request.
    *   **Security:** Verifies nonce (`check_ajax_referer('straico_create_rag', 'straico_nonce')`) and user capability (`current_user_can('manage_options')`).
    *   **Data Validation:** Validates required fields (name, description, files).
    *   **File Handling:**
        *   Iterates through `$_FILES['rag_files']`.
        *   Performs validation (upload errors, file type, size using `wp_check_filetype` and `wp_max_upload_size`).
        *   Moves uploaded files to a temporary location in `wp_upload_dir()`.
    *   **API Call (`includes/api/class-straico-rag-api.php`):**
        *   Calls `Straico_RAG_API::validate_files()` for further validation (allowed types, count, size).
        *   Calls `Straico_RAG_API::create_rag()` with RAG details and paths to temporary files.
4.  **RAG API (`includes/api/class-straico-rag-api.php`):**
    *   `create_rag()`:
        *   Constructs a `multipart/form-data` payload including RAG name, description, chunking method, chunking options, and the file contents. This is a manual construction of the multipart body.
        *   Makes a POST request to the Straico API endpoint `v0/rag` using `wp_remote_post()` (via `Straico_API_Base`).
        *   Cleans up temporary files after the API call (using a `finally` block or `register_shutdown_function`).
5.  **Response Processing:**
    *   `Straico_API_Base::handle_response()` processes the raw API response.
    *   `Straico_Admin::handle_create_rag()` sends a JSON success/error message back to the JavaScript.
6.  **Return to Frontend:**
    *   JavaScript handles the response, typically showing a success message and redirecting or reloading, or displaying an error.

### Admin Creating a New Agent
1.  **User Interaction (Admin):** Fills out the "Create Agent" form (template: `admin/partials/agent-management/create-agent.php`).
2.  **JavaScript Handling (`admin/js/agent-management.js`):**
    *   The `$('.straico-agent-form').on('submit', ...)` handler intercepts submission.
    *   Serializes form data.
    *   AJAX POST request to `admin-ajax.php` with `action: 'straico_create_agent'`.
3.  **WordPress AJAX Hook (`admin/class-straico-admin.php`):**
    *   The `handle_create_agent()` method receives the request.
    *   **Security:** Verifies nonce (`check_ajax_referer('straico_create_agent', 'straico_nonce')`) and user capability.
    *   **Data Validation:** Sanitizes input. Calls `Straico_Agent_API::validate_agent_data()`.
4.  **API Call (`includes/api/class-straico-agent-api.php`):**
    *   `Straico_Admin` calls `Straico_Agent_API::create_agent()` with agent data.
    *   `Straico_Agent_API::create_agent()` calls `Straico_API_Base::post()` to `v0/agent`.
5.  **Response Processing & Return:**
    *   API response is handled. JSON success/error sent to JS.
    *   JS redirects to agent list page on success or shows error.

### Model Update Cron Job Execution
1.  **Scheduling (`includes/cron/class-straico-cron-manager.php`):**
    *   `activate_cron_jobs()` (on plugin activation) or `update_cron_schedules()` (on settings change) schedules an event: `wp_schedule_event(time(), 'straico_model_update', 'straico_model_update_event')`.
    *   The `'straico_model_update'` interval is defined in `add_cron_intervals()` based on plugin settings.
2.  **WordPress Cron Trigger:** WordPress's cron system triggers the `straico_model_update_event` at the scheduled interval.
3.  **Cron Callback (`includes/cron/class-straico-model-updates.php`):**
    *   The `check_model_updates()` method (hooked to `straico_model_update_event`) is executed.
    *   **Fetch Models:** Calls `Straico_Models_API::get_models()` to get the current list from `v1/models`.
    *   **Compare:** Retrieves the previous model list from `get_transient('straico_previous_models')`.
    *   Calls `Straico_Models_API::detect_model_changes()` to find differences.
    *   **Handle Changes:**
        *   If changes are detected and notifications are enabled (via `Straico_Options`):
            *   Logs changes to the `wp_straico_model_changes` table via `Straico_DB_Manager::log_model_change()`.
            *   Sends an email notification via `Straico_Models_API::send_model_changes_notification()`.
    *   **Update Cache:** Stores the new model list in the `straico_previous_models` transient using `set_transient()`.

### Coin Balance Check Cron Job Execution
1.  **Scheduling (`includes/cron/class-straico-cron-manager.php`):**
    *   Similar to model updates, `straico_coin_check_event` is scheduled if low coin notifications are enabled. Interval `'straico_coin_check'` is based on settings.
2.  **WordPress Cron Trigger:** Triggers `straico_coin_check_event`.
3.  **Cron Callback (`includes/cron/class-straico-balance-check.php`):**
    *   The `check_coin_balance()` method (hooked to `straico_coin_check_event`) is executed.
    *   **Check Prerequisite:** Exits if low coin notifications are disabled (via `Straico_Options`).
    *   **Fetch Balance:** Calls `Straico_User_API::get_coin_balance()` (which calls `get_user_info()` for `v0/user`).
    *   **Get Threshold:** Retrieves threshold from `Straico_Options`.
    *   **Compare & Notify Logic:**
        *   Checks if balance is below threshold.
        *   Uses `get_transient('straico_last_balance_notification')` to avoid re-notifying if balance hasn't dropped further.
        *   Logs the check to `wp_straico_coin_history` via `Straico_DB_Manager::log_coin_balance()`.
        *   If notification is needed, sends email via `Straico_User_API::send_low_balance_notification()`.
        *   Updates/deletes the transient accordingly.

## Database Schema

The plugin utilizes custom database tables prefixed with `{$wpdb->prefix}straico_`.

### Shortcodes Table (`wp_straico_shortcodes`)
Stores configurations for user-created shortcodes.
```sql
CREATE TABLE {$prefix}straico_shortcodes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    name varchar(100) NOT NULL,         -- Unique name for the shortcode (e.g., 'my_agent_chat')
    agent_id varchar(100) NOT NULL,    -- ID of the Straico Agent this shortcode is linked to
    settings longtext NOT NULL,        -- JSON encoded string of shortcode-specific settings (e.g., temperature, max_tokens, display options)
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    UNIQUE KEY name (name)
);
```

### Model Changes Table (`wp_straico_model_changes`)
Logs when AI models are detected as added or removed from the Straico API.
```sql
CREATE TABLE {$prefix}straico_model_changes (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    model_id varchar(100) NOT NULL,     -- The unique identifier of the model from Straico API
    model_name varchar(255) NOT NULL,   -- Human-readable name of the model
    model_type varchar(50) NOT NULL,    -- Type of model (e.g., 'chat', 'image')
    change_type enum('added', 'removed') NOT NULL, -- Nature of the change
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id),
    KEY model_id (model_id),
    KEY change_type (change_type)
);
```

### Coin History Table (`wp_straico_coin_history`)
Logs results of periodic coin balance checks.
```sql
CREATE TABLE {$prefix}straico_coin_history (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    balance decimal(10,2) NOT NULL,        -- The coin balance at the time of check
    threshold decimal(10,2) NOT NULL,      -- The configured low balance threshold at the time of check
    notification_sent tinyint(1) NOT NULL DEFAULT 0, -- 1 if a low balance notification was sent for this check, 0 otherwise
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY  (id)
);
```

## Development Guidelines

### Coding Standards
Adherence to established coding standards is crucial for maintainability and collaboration.

1.  **PHP:**
    *   Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
    *   PHP 7.4+ syntax is used. Consider PHP 8.x features where appropriate and if backward compatibility allows.
    *   Implement robust error handling (e.g., using `WP_Error` for functions that can fail).
    *   Document all classes, methods, and complex code blocks using PHPDoc.
2.  **JavaScript:**
    *   Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).
    *   Utilize ES6+ features where appropriate, ensuring browser compatibility or transpilation if necessary (though current JS seems to be directly usable).
    *   Implement client-side error handling and provide clear user feedback.
    *   Document functions and modules.
3.  **CSS:**
    *   Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/).
    *   Use a consistent naming convention (BEM is suggested but verify current practice).
    *   Ensure styles are responsive and consider RTL language support.
    *   Avoid utility-first frameworks like Tailwind CSS to keep generated CSS lean, as per project guidelines.

### File Organization
1.  **Class Files:**
    *   One class per file.
    *   File names should follow WordPress conventions: `class-{classname}.php` (e.g., `class-straico-loader.php`).
    *   Aim to keep files focused and manageable in size (current guideline: max 400 lines, though some API classes might exceed this due to extensive method definitions).
2.  **Template Files (`admin/partials/`):**
    *   Separate PHP logic from HTML presentation as much as feasible.
    *   Use WordPress escaping functions (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`, etc.) for all dynamic output.
    *   Implement form validation on both client-side (JS) and server-side (PHP).

### AJAX Handlers
The plugin extensively uses WordPress AJAX for dynamic interactions.

1.  **Registration:**
    *   Admin-only AJAX actions: `add_action('wp_ajax_{action_name}', 'callback_function');`
    *   AJAX actions for both logged-in and logged-out users (e.g., for public shortcodes):
        `add_action('wp_ajax_nopriv_{action_name}', 'callback_function');`
        `add_action('wp_ajax_{action_name}', 'callback_function');`
2.  **Security:**
    *   **Nonces:** Always use `check_ajax_referer('your_nonce_action', 'your_nonce_field_name');` at the beginning of your AJAX callback. Nonces should be passed from the client-side.
    *   **Capability Checks:** For actions that modify data or access sensitive information, verify user permissions using `current_user_can('your_capability');`.
    *   **Input Sanitization:** Sanitize all `$_POST` or `$_GET` data using appropriate WordPress functions (e.g., `sanitize_text_field`, `absint`, `sanitize_email`).
3.  **Response:**
    *   Use `wp_send_json_success($data);` or `wp_send_json_error($data);` to return responses. This ensures proper JSON formatting and includes a `success: true/false` flag. `$data` can be an array or object.
    *   Always `wp_die()` at the end of your AJAX callback to prevent extra output.

**Key AJAX Actions:**
(This list is already well-covered in the existing document and my previous analysis. I will ensure it's complete.)
*   `straico_force_model_update`
*   `straico_force_balance_check`
*   `straico_create_rag`
*   `straico_get_rag_details`
*   `straico_delete_rag`
*   `straico_create_agent`
*   `straico_delete_agent`
*   `straico_get_agent_details`
*   `straico_connect_rag`
*   `straico_prompt_completion` (admin basic prompt)
*   `straico_upload_file` (used by basic prompt completion)
*   `straico_rag_prompt` (admin RAG prompt)
*   `straico_agent_prompt` (admin agent prompt AND public shortcode prompt)
*   `straico_create_shortcode`

### Best Practices
1.  **Security:** Prioritize security in all development. (See [Security Considerations](#security-considerations)).
2.  **Performance:**
    *   Use WordPress Transients API (`set_transient`, `get_transient`, `delete_transient`) to cache data from expensive operations or frequent API calls (e.g., model lists).
    *   Minimize direct database queries; use WordPress functions where possible. Optimize custom queries.
    *   Conditionally load scripts and styles only on pages where they are needed.
3.  **Accessibility (A11Y):**
    *   Follow WCAG guidelines.
    *   Use ARIA attributes correctly for dynamic content and interactive elements.
    *   Ensure keyboard navigability for all interactive elements.
    *   Test with screen readers.
4.  **Internationalization (i18n) & Localization (L10n):**
    *   Use WordPress localization functions for all translatable strings (e.g., `__('Text', 'straico-integration')`, `_e('Text', 'straico-integration')`, `sprintf()`).
    *   The text domain is `straico-integration`.
5.  **Error Handling:**
    *   Use `WP_Error` objects for functions that can fail, allowing callers to check `is_wp_error()`.
    *   Log errors appropriately using `error_log()` for server-side issues, especially for cron jobs and API interactions.
    *   Provide clear, user-friendly error messages on the client-side.

## Adding New Features - Conceptual Walkthroughs

### Adding a New Admin Page
1.  **Define Menu Item:** In `admin/class-straico-admin.php`, within the `add_plugin_admin_menu()` method, use `add_submenu_page()` to add your new page under the "Straico" main menu.
    ```php
    add_submenu_page(
        'straico-settings', // Parent slug
        __('My New Page Title', 'straico-integration'), // Page title
        __('My New Menu Title', 'straico-integration'), // Menu title
        'manage_options', // Capability required
        'straico-my-new-page', // Menu slug (unique)
        array($this, 'display_my_new_page') // Callback function to render the page
    );
    ```
2.  **Create Display Callback:** In `admin/class-straico-admin.php`, create the callback method.
    ```php
    public function display_my_new_page() {
        // Perform any data fetching or logic needed for the page
        // For example, $data = $this->some_api_class->get_data();
        require_once STRAICO_INTEGRATION_PATH . 'admin/partials/my-feature/my-new-page-template.php';
    }
    ```
3.  **Create Template File:** Create the PHP file `admin/partials/my-feature/my-new-page-template.php`. This file will contain the HTML structure for your page. Use WordPress functions for forms, tables, etc.
4.  **Enqueue Assets (if needed):** If your page requires custom CSS or JS:
    *   Create your CSS file (e.g., `admin/css/my-new-page.css`).
    *   Create your JS file (e.g., `admin/js/my-new-page.js`).
    *   In `admin/class-straico-admin.php`, modify `enqueue_styles()` and `enqueue_scripts()` to conditionally load these assets based on the current screen ID.
    ```php
    // In enqueue_scripts()
    $screen = get_current_screen();
    if ($screen && $screen->id === 'straico_page_straico-my-new-page') { // Note: screen ID format
        wp_enqueue_script(
            'straico-my-new-page-js',
            STRAICO_INTEGRATION_URL . 'admin/js/my-new-page.js',
            array('jquery', 'straico-admin'), // Dependencies
            STRAICO_INTEGRATION_VERSION,
            true
        );
        // wp_localize_script if needed
    }
    ```
5.  **Handle AJAX (if needed):**
    *   In `admin/class-straico-admin.php` `__construct()`, add your AJAX action:
        `add_action('wp_ajax_straico_my_new_action', array($this, 'handle_my_new_action'));`
    *   Implement the `handle_my_new_action()` method in `Straico_Admin`, including nonce checks, capability checks, sanitization, logic, and `wp_send_json_success/error`.
    *   In your `admin/js/my-new-page.js`, make AJAX calls to this action.

### Integrating a New Straico API Endpoint
1.  **Identify/Create API Client Class:** Determine if the new endpoint fits within an existing class in `includes/api/` or if a new class is warranted (e.g., `class-straico-newservice-api.php`).
2.  **Add Method to API Client Class:**
    *   The method should accept necessary parameters for the API call.
    *   Use the appropriate method from `Straico_API_Base` (e.g., `$this->get('v0/new-service/endpoint', $params);`).
    *   Document the expected request parameters and response structure.
    ```php
    // In includes/api/class-straico-newservice-api.php (extends Straico_API_Base)
    public function get_new_service_data($service_id) {
        if (empty($service_id)) {
            return new WP_Error('missing_param', __('Service ID is required.', 'straico-integration'));
        }
        return $this->get('v0/new-service/' . urlencode($service_id));
    }
    ```
3.  **Call from Plugin Logic:** Instantiate your API client class and call the new method from where it's needed (e.g., an admin page callback in `Straico_Admin`, an AJAX handler, or a cron job).
4.  **Handle Response:** Process the returned data or `WP_Error` object.
5.  **Update Documentation:** Add details about the new endpoint and its usage to this `CONTRIBUTING.md` file.

## Debugging Tips

*   **WordPress Debugging:** Ensure `WP_DEBUG` and `WP_DEBUG_LOG` are `true` in `wp-config.php`. Check `wp-content/debug.log` for PHP errors, warnings, and notices.
*   **Browser Developer Tools:**
    *   **Console:** Check for JavaScript errors. Use `console.log()` extensively during JS development.
    *   **Network Tab:** Inspect AJAX requests. Check the URL, request payload (headers, form data), and the raw response from `admin-ajax.php`. This is invaluable for debugging AJAX issues.
*   **PHP Error Logs:** The plugin uses `error_log()` in many places, especially within API classes and cron jobs. Check your server's PHP error log for these custom messages.
*   **Straico API Documentation:** Refer to the [official Straico API Documentation](https://documenter.getpostman.com/view/5900072/2s9YyzddrR) to understand expected request/response formats, error codes, and rate limits.
*   **Xdebug (or similar):** For complex PHP issues, step-through debugging is highly effective.
*   **Deactivating Other Plugins/Switching Themes:** To rule out conflicts, test with a default WordPress theme (like Twenty Twenty-One) and all other plugins deactivated.
*   **WP-CLI:** Can be used to trigger cron events (`wp cron event run straico_model_update_event`), check option values (`wp option get straico_api_key`), etc.
*   **Query Monitor Plugin:** Useful for inspecting database queries, hooks, HTTP API calls, and more.

## Security Considerations

Security is paramount. Follow these principles:

### API Key Management
*   The API key is stored as a WordPress option (`straico_api_key`).
*   It is sanitized using `Straico_Security::validate_api_key()` which checks format and length.
*   The `Straico_Security::test_api_key()` method performs a live check against the `/v0/user` endpoint to confirm validity.
*   **Never log the full API key.** The code currently logs a preview.
*   Consider implications if the database is compromised. While WordPress options are generally secure, direct DB access could expose the key. (Encryption at rest for the option itself is not implemented by default in WordPress).

### Input Validation & Output Escaping
*   **Validate All User Input:** Server-side validation is critical. Sanitize data from `$_GET`, `$_POST`, and any other external sources using appropriate WordPress functions (e.g., `sanitize_text_field`, `absint`, `sanitize_email`, `wp_kses_post` for rich text).
*   **File Uploads:** Validate file types, sizes, and use `wp_check_filetype()` and WordPress's upload handling mechanisms where possible. The plugin implements custom validation in `Straico_File_API::validate_file()` and `Straico_RAG_API::validate_files()`.
*   **Escape All Output:** Prevent XSS by escaping data before rendering it in HTML. Use `esc_html()`, `esc_attr()`, `esc_js()`, `esc_url()`, `wp_kses_post()` as appropriate.

### Access Control & Nonces
*   **Capability Checks:** Use `current_user_can('capability_name')` before performing actions that require specific user roles or permissions, especially for admin-ajax actions and admin page rendering. `manage_options` is commonly used.
*   **Nonces (Numbers Used Once):** Protect against CSRF attacks.
    *   Generate nonces for forms using `wp_nonce_field()` or `wp_create_nonce()`.
    *   Verify nonces in form processing and AJAX handlers using `check_admin_referer()` (for form submissions processed on admin pages) or `check_ajax_referer()` (for AJAX handlers).

## Updating This Documentation
As the plugin evolves, it is crucial to keep this `CONTRIBUTING.md` file and the user-facing `README.md` accurate and up-to-date. When adding new features, modifying existing ones, or changing architectural aspects:

1.  Update the relevant sections in this document.
2.  Add new sections if necessary (e.g., for new core components or significant features).
3.  Ensure the Table of Contents is current.
4.  Verify that code examples and directory structures are correct.

Clear, comprehensive documentation is vital for the long-term health and collaborative success of the project.

## Additional Resources
*   [`STRAICO_API_DOCUMENTATION.md`](STRAICO_API_DOCUMENTATION.md) - Comprehensive documentation of the Straico API endpoints, request/response formats, and authentication details specific to this project's integration.
*   [Official General Straico API Documentation (Postman)](https://documenter.getpostman.com/view/5900072/2s9YyzddrR#f16fc347-a9fb-4f93-b26d-682875b975a3) - The official, general-purpose Straico API documentation.
*   [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
*   [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
*   [WordPress Theme Developer Handbook - Data Validation](https://developer.wordpress.org/themes/theme-security/data-validation/)
*   [PHP Standards Recommendations (PSR)](https://www.php-fig.org/psr/)
