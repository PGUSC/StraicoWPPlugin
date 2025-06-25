<?php
/**
 * Handles agent-related API interactions with Straico.
 *
 * This class provides methods for managing agents, including creation,
 * listing, updating, deletion, and connecting RAGs to agents.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_Agent_API extends Straico_API_Base {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new agent.
     *
     * @since    1.0.0
     * @param    string    $name           The name of the agent.
     * @param    string    $custom_prompt  The custom prompt for the agent.
     * @param    string    $default_llm    The default LLM model for the agent.
     * @param    string    $description    The description of the agent.
     * @param    array     $tags           Optional. Tags for the agent.
     * @return   array|WP_Error           The API response or error on failure.
     */
    public function create_agent($name, $custom_prompt, $default_llm, $description, $tags = array()) {
        $data = array(
            'name' => $name,
            'custom_prompt' => $custom_prompt,
            'default_llm' => $default_llm,
            'description' => $description,
            'tags' => $tags
        );

        return $this->post('v0/agent', $data);
    }

    /**
     * Get a list of all agents for the current user.
     *
     * @since    1.0.0
     * @return   array|WP_Error    List of agents or error on failure.
     */
    public function list_agents() {
        return $this->get('v0/agent/');
    }

    /**
     * Get details of a specific agent.
     *
     * @since    1.0.0
     * @param    string    $agent_id    The ID of the agent.
     * @return   array|WP_Error         Agent details or error on failure.
     */
    public function get_agent_details($agent_id) {
        return $this->get('v0/agent/' . urlencode($agent_id));
    }

    /**
     * Update an existing agent.
     *
     * @since    1.0.0
     * @param    string    $agent_id       The ID of the agent to update.
     * @param    array     $update_data    The data to update.
     * @return   array|WP_Error            The API response or error on failure.
     */
    public function update_agent($agent_id, $update_data) {
        return $this->put('v0/agent/' . urlencode($agent_id), $update_data);
    }

    /**
     * Delete an agent.
     *
     * @since    1.0.0
     * @param    string    $agent_id    The ID of the agent to delete.
     * @return   array|WP_Error         The API response or error on failure.
     */
    public function delete_agent($agent_id) {
        return $this->delete('v0/agent/' . urlencode($agent_id));
    }

    /**
     * Connect a RAG to an agent.
     *
     * @since    1.0.0
     * @param    string    $agent_id    The ID of the agent.
     * @param    string    $rag_id      The ID of the RAG to connect.
     * @return   array|WP_Error         The API response or error on failure.
     */
    public function add_rag_to_agent($agent_id, $rag_id) {
        $data = array(
            'rag' => $rag_id
        );

        return $this->post('v0/agent/' . urlencode($agent_id) . '/rag', $data);
    }

    /**
     * Submit a prompt to an agent.
     *
     * @since    1.0.0
     * @param    string    $agent_id         The ID of the agent.
     * @param    string    $prompt           The prompt text.
     * @param    array     $search_options   Optional. Search options for the RAG.
     * @return   array|WP_Error              The API response or error on failure.
     */
    public function prompt_agent($agent_id, $prompt, $search_options = array()) {
        $data = array(
            'prompt' => $prompt
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

        return $this->post('v0/agent/' . urlencode($agent_id) . '/prompt', $data);
    }

    /**
     * Format agent information for display.
     *
     * @since    1.0.0
     * @param    array    $agent    Raw agent information from API.
     * @return   array             Formatted agent information.
     */
    public function format_agent_info($agent) {
        $formatted = array(
            'id' => $agent['_id'],
            'uuid' => $agent['uuidv4'],
            'name' => $agent['name'],
            'description' => $agent['description'],
            'default_llm' => $agent['default_llm'],
            'custom_prompt' => $agent['custom_prompt'],
            'status' => $agent['status'],
            'tags' => $agent['tags'],
            'interaction_count' => $agent['interaction_count'],
            'visibility' => $agent['visibility'],
            'created_at' => get_date_from_gmt($agent['createdAt']),
            'updated_at' => get_date_from_gmt($agent['updatedAt'])
        );

        if (isset($agent['rag_association'])) {
            $formatted['rag_association'] = $agent['rag_association'];
        }

        if (isset($agent['last_interaction'])) {
            $formatted['last_interaction'] = $agent['last_interaction'] ? 
                get_date_from_gmt($agent['last_interaction']) : null;
        }

        return $formatted;
    }

    /**
     * Validate agent creation/update data.
     *
     * @since    1.0.0
     * @param    array     $data    The agent data to validate.
     * @return   bool|WP_Error      True if valid, WP_Error if invalid.
     */
    public function validate_agent_data($data) {
        $required_fields = array(
            'name' => __('Name is required', 'straico-integration'),
            'custom_prompt' => __('Custom prompt is required', 'straico-integration'),
            'default_llm' => __('Default LLM model is required', 'straico-integration'),
            'description' => __('Description is required', 'straico-integration')
        );

        foreach ($required_fields as $field => $message) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return new WP_Error(
                    'missing_required_field',
                    $message
                );
            }
        }

        // Validate name length
        if (strlen($data['name']) > 100) {
            return new WP_Error(
                'invalid_name',
                __('Name must be 100 characters or less', 'straico-integration')
            );
        }

        // Validate description length
        if (strlen($data['description']) > 500) {
            return new WP_Error(
                'invalid_description',
                __('Description must be 500 characters or less', 'straico-integration')
            );
        }

        // Validate custom prompt length
        if (strlen($data['custom_prompt']) > 1000) {
            return new WP_Error(
                'invalid_custom_prompt',
                __('Custom prompt must be 1000 characters or less', 'straico-integration')
            );
        }

        // Validate tags if provided
        if (isset($data['tags']) && !empty($data['tags'])) {
            if (!is_array($data['tags'])) {
                return new WP_Error(
                    'invalid_tags',
                    __('Tags must be provided as an array', 'straico-integration')
                );
            }

            foreach ($data['tags'] as $tag) {
                if (!is_string($tag) || empty(trim($tag))) {
                    return new WP_Error(
                        'invalid_tag',
                        __('Each tag must be a non-empty string', 'straico-integration')
                    );
                }

                if (strlen($tag) > 50) {
                    return new WP_Error(
                        'invalid_tag_length',
                        __('Each tag must be 50 characters or less', 'straico-integration')
                    );
                }
            }
        }

        return true;
    }

    /**
     * Get default agent settings.
     *
     * @since    1.0.0
     * @return   array    Default settings for agent creation.
     */
    public function get_default_settings() {
        return array(
            'status' => 'active',
            'visibility' => 'private',
            'tags' => array('assistant'),
            'interaction_count' => 0,
            'last_interaction' => null
        );
    }

    /**
     * Format agent prompt completion response for display.
     *
     * @since    1.0.0
     * @param    array    $response    Raw API response.
     * @return   array                 Formatted response.
     */
    public function format_prompt_response($response) {
        if (!isset($response['response'])) {
            return array(
                'answer' => __('No response received from the agent', 'straico-integration'),
                'references' => array(),
                'coins_used' => 0
            );
        }

        return array(
            'answer' => $response['response']['answer'],
            'references' => isset($response['response']['references']) ? 
                $this->format_references($response['response']['references']) : array(),
            'coins_used' => $response['response']['coins_used']
        );
    }

    /**
     * Format reference information from prompt completion.
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $references    Raw reference information.
     * @return   array                   Formatted references.
     */
    private function format_references($references) {
        $formatted = array();
        foreach ($references as $ref) {
            $formatted[] = array(
                'content' => $ref['page_content'],
                'page' => isset($ref['page']) ? $ref['page'] : null
            );
        }
        return $formatted;
    }
}
