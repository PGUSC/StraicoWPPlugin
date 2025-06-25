# Straico Integration for WordPress

**Version:** 1.0.h
**Author:** Straico
**License:** GPL v2 or later
**Requires WordPress Version:** 5.0 or higher
**Requires PHP Version:** 7.4 or higher

A WordPress plugin that provides seamless integration with the Straico API, enabling Retrieval-Augmented Generation (RAG) and AI agent management, versatile prompt completions, and easy embedding of AI-powered interactions via shortcodes.

## Description

The Straico Integration plugin empowers WordPress users to leverage the full potential of Straico's advanced AI capabilities directly within their WordPress environment. This plugin offers a comprehensive suite of tools for managing RAGs, AI agents, and various prompt completion interfaces. It also includes a robust shortcode system, allowing users to embed interactive AI agent prompts into posts and pages effortlessly.

This document provides an overview of the plugin's features, installation, configuration, and basic usage. For in-depth technical details, contribution guidelines, and comprehensive API information, please refer to the [`CONTRIBUTING.md`](CONTRIBUTING.md:0) and [`STRAICO_API_DOCUMENTATION.md`](STRAICO_API_DOCUMENTATION.md:0) files.

## Features

The plugin is packed with features designed to make AI integration intuitive and powerful:

*   **Straico API Integration:**
    *   Secure management and validation of your Straico API key.
    *   Automated background checks for updates to available AI models.
    *   Configurable email notifications for low Straico coin balance and model list changes.
*   **RAG (Retrieval-Augmented Generation) Management:**
    *   **Create & Manage RAGs:** Intuitive interface to create, view, and delete RAGs.
    *   **File Uploads:** Support for uploading various file types (PDF, DOCX, TXT, CSV, XLSX, PY, etc., up to 4 files per RAG, max 25MB each) to build knowledge bases.
    *   **Advanced Chunking:** Multiple chunking methods (Fixed Size, Recursive, Markdown, Python, Semantic) with customizable options to optimize text processing for different content types.
*   **AI Agent Management:**
    *   **Create & Manage Agents:** Define custom AI agents with unique names, descriptions, system prompts (custom instructions), and default Large Language Models (LLMs).
    *   **Connect RAGs to Agents:** Enhance agents by associating them with previously created RAGs, enabling them to answer questions based on your uploaded documents.
    *   **Tagging:** Organize agents with tags for easier management.
*   **Versatile Prompt Completions (Admin Interface):**
    *   **Basic Completion:** Directly interact with multiple AI models (up to 4 simultaneously for comparison). Supports optional file uploads (via Straico API) and YouTube video URLs for context.
    *   **RAG-based Completion:** Send prompts to a specific RAG, leveraging its knowledge base to get contextual answers with source references.
    *   **Agent-based Completion:** Interact with your configured AI agents directly from the admin panel.
*   **Dynamic Shortcode System:**
    *   **Create Custom Shortcodes:** Generate unique shortcodes linked to specific AI agents.
    *   **Embed Anywhere:** Easily embed these shortcodes (`[straico_your_shortcode_name]`) into any WordPress post or page.
    *   **Customizable Frontend:** Configure placeholder text, button labels, and other display settings for each shortcode.
    *   **AJAX-Powered:** Shortcode interactions (prompt submissions, response display) are handled via AJAX for a smooth user experience.
    *   **Responsive Design:** Shortcode output is designed to be responsive.
    *   **Accessibility:** Includes ARIA attributes for improved accessibility of front-end forms.
*   **User & Model Information:**
    *   View your Straico account details (name, plan, current coin balance) within WordPress.
    *   Browse a list of currently available AI models from Straico, including their capabilities and pricing.
*   **Logging & Notifications:**
    *   Database logs for model changes and coin balance checks.
    *   Configurable email notifications for important events.

## Plugin Architecture Overview

The plugin is structured into several key directories, each with a distinct responsibility:

*   **`straico-integration.php` (Root):** The main plugin file. Handles initialization, defines constants, and loads the core plugin loader.
*   **`admin/`:** Contains all code related to the WordPress admin interface.
    *   `class-straico-admin.php`: Manages admin menu creation, page rendering, AJAX request handling for admin operations, and enqueues admin-specific scripts and styles.
    *   `partials/`: PHP template files for the various admin pages (Settings, RAG Management, Agent Management, etc.).
    *   `js/`: JavaScript files that add interactivity to the admin pages.
    *   `css/`: Stylesheets for the admin interface.
*   **`public/`:** Contains code for the public-facing side of the website.
    *   `class-straico-public.php`: Manages the enqueueing of public scripts/styles (only when a Straico shortcode is present) and handles hooks related to front-end display.
    *   `js/`: JavaScript for front-end shortcode interactivity (AJAX submissions, response display).
    *   `css/`: Styles for the front-end output of shortcodes.
*   **`includes/`:** The heart of the plugin, containing core logic and business classes.
    *   `api/`: A suite of classes for communicating with the various Straico API endpoints (e.g., for users, models, RAGs, agents, files, prompts).
    *   `cron/`: Classes for managing scheduled tasks (model updates, balance checks) via WordPress cron.
    *   `db/`: The database manager class (`class-straico-db-manager.php`) responsible for creating and interacting with custom plugin tables.
    *   `shortcodes/`: Classes for managing shortcode creation, storage, and rendering.
    *   `class-straico-loader.php`: Loads all plugin dependencies and registers WordPress hooks.
    *   `class-straico-options.php`: Manages plugin settings using the WordPress Settings API.
    *   `class-straico-security.php`: Centralizes security functions (nonce handling, sanitization, API key validation).
*   **`uninstall.php` (Root):** Script executed when the plugin is uninstalled, responsible for cleaning up database tables, options, and other data.

For a more detailed technical breakdown, please see [`CONTRIBUTING.md`](CONTRIBUTING.md:0).

## Requirements

*   WordPress Version: 5.0 or higher
*   PHP Version: 7.4 or higher
*   MySQL Version: 5.6 or higher (as per standard WordPress requirements)
*   A valid Straico API Key (obtainable from your [Straico Dashboard](https://straico.com/dashboard))

## Installation

1.  **Download:** Download the plugin ZIP file.
2.  **Upload:** In your WordPress admin panel, go to `Plugins` > `Add New` > `Upload Plugin`. Choose the downloaded ZIP file and click `Install Now`.
    Alternatively, unzip the package and upload the `straico-integration` directory to the `/wp-content/plugins/` directory of your WordPress installation.
3.  **Activate:** Activate the plugin through the 'Plugins' menu in WordPress.
4.  **Configure API Key:**
    *   Navigate to `Straico` > `Settings` in your WordPress admin menu.
    *   Enter your Straico API Key in the "API Key" field.
    *   Click `Save Changes`. The plugin will attempt to validate the key.
5.  **Configure Notifications (Optional):** Adjust model update and low coin balance notification settings as needed on the same page.

## Configuration

All plugin settings can be found under `Straico` > `Settings` in the WordPress admin menu.

### API Settings
*   **API Key:** (Required) Your Straico API key.

### Model Update Settings
*   **Update Frequency:** How often (in hours) the plugin checks for updates to the list of available LLM models from Straico. (Default: 12 hours)
*   **Model Change Notifications:** Enable/disable email notifications when models are added or removed.
*   **Notification Emails:** Comma-separated list of email addresses to receive model change notifications.

### Low Coins Notification Settings
*   **Low Coins Notifications:** Enable/disable email notifications when your Straico coin balance is low.
*   **Low Coin Threshold:** The coin balance amount below which a notification will be triggered. (Default: 100)
*   **Check Frequency:** How often (in minutes) the plugin checks your coin balance. (Default: 360 minutes / 6 hours)
*   **Notification Emails:** Comma-separated list of email addresses to receive low coin notifications.

## Usage Examples

### Creating a RAG
1.  Navigate to `Straico` > `RAG Management` > `Create RAG`.
2.  Enter a **Name** and **Description** for your RAG.
3.  **Upload Files:** Click "Choose Files" to select up to 4 documents (PDF, DOCX, TXT, etc.) that will form the knowledge base for this RAG.
4.  **Chunking Options:** Select a `Chunking Method` (e.g., Fixed Size, Recursive) and configure its specific parameters (e.g., Chunk Size, Overlap).
5.  Click `Create RAG`.

### Creating an Agent
1.  Navigate to `Straico` > `Agent Management` > `Create Agent`.
2.  Enter an **Agent Name**, **Description**, and **Custom Prompt** (system message/instructions for the agent).
3.  Select a **Default LLM Model** for the agent.
4.  Optionally, add **Tags** for organization.
5.  Click `Create Agent`.

### Connecting a RAG to an Agent
1.  Navigate to `Straico` > `Agent Management` > `Connect RAG to Agent`.
2.  Select an existing **Agent** from the dropdown.
3.  Select an existing **RAG** from the dropdown.
4.  Click `Connect RAG`. The selected agent will now use the RAG's knowledge base.

### Using Prompt Completions in Admin
*   **Basic Completion:** Go to `Straico` > `Prompt Completions`. Select model(s), enter your message, optionally add a file/YouTube URL, and submit.
*   **RAG Prompt:** Go to `Straico` > `RAG Prompt`. Select a RAG, a model, enter your prompt, and submit to query the RAG.
*   **Agent Prompt:** Go to `Straico` > `Agent Prompt`. Select an Agent, enter your prompt, and submit to interact with the agent.

### Creating and Using a Shortcode
1.  Navigate to `Straico` > `Agent Shortcodes` > `Create Shortcode`.
2.  Enter a **Shortcode Name** (e.g., `my_support_agent`). This will become part of the shortcode tag.
3.  Select the **Agent** this shortcode will use.
4.  Configure **Display Settings** (placeholder text, button labels, etc.).
5.  Click `Create Shortcode`.
6.  **Embed:** Copy the generated shortcode (e.g., `[straico_my_support_agent]`) and paste it into any WordPress post or page editor.

## Security
The plugin implements several security measures:
*   **API Key Handling:** API keys are stored as WordPress options and validated. Communication with the Straico API is over HTTPS.
*   **Nonce Verification:** WordPress nonces are used to protect against CSRF attacks on forms and AJAX actions in the admin area and for public shortcode submissions.
*   **Capability Checks:** Admin functionalities are protected by WordPress capability checks (typically `manage_options`) to ensure only authorized users can access them.
*   **Input Sanitization:** All user inputs (form fields, URL parameters) are sanitized on the server-side using appropriate WordPress functions (e.g., `sanitize_text_field`, `sanitize_email`, `absint`).
*   **Output Escaping:** All data output to the browser is escaped using functions like `esc_html()`, `esc_attr()`, `esc_url()`, and `wp_kses_post()` to prevent XSS vulnerabilities.
*   **File Upload Validation:** Uploaded files (for RAGs or prompt context) are validated for type and size.

## Customization
The visual appearance of the plugin's elements can be customized using CSS:
*   **Admin Styles:** Modify or override styles in [`admin/css/straico-admin.css`](admin/css/straico-admin.css:0) and [`admin/css/prompt-completion.css`](admin/css/prompt-completion.css:0).
*   **Public (Shortcode) Styles:** Modify or override styles in [`public/css/straico-public.css`](public/css/straico-public.css:0). You can also add your own CSS rules in your theme's stylesheet to target the specific classes used by the shortcode output (e.g., `.straico-prompt-container`, `.straico-response-answer`).

## Support
For support, bug reports, or feature requests, please refer to the plugin's official support channels or repository.
*   **Straico Website:** [https://straico.com](https://straico.com)
*   **Straico Support:** [https://straico.com/support](https://straico.com/support)

## Contributing
We welcome contributions to the Straico Integration plugin! Please see the [`CONTRIBUTING.md`](CONTRIBUTING.md:0) file for detailed technical information and development guidelines. For developers working with the API, the [`STRAICO_API_DOCUMENTATION.md`](STRAICO_API_DOCUMENTATION.md:0) file provides essential details on endpoints, requests, and responses.
