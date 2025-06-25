<?php
/**
 * Admin create/edit agent page template.
 *
 * This template displays the form for creating or editing an agent in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/agent-management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get models API instance for LLM options
$models_api = new Straico_Models_API();
$models = $models_api->get_models();

// Check if we're editing an existing agent
$is_edit = isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['agent_id']);
$agent = null;

if ($is_edit) {
    $agent_api = new Straico_Agent_API();
    $agent = $agent_api->get_agent_details($_GET['agent_id']);
    if (is_wp_error($agent)) {
        $agent = null;
        $is_edit = false;
    } else {
        $agent = $agent_api->format_agent_info($agent['data']);
    }
}
?>

<div class="wrap">
    <h1>
        <?php echo esc_html($is_edit ? __('Edit Agent', 'straico-integration') : get_admin_page_title()); ?>
    </h1>

    <div class="straico-create-agent">
        <form method="post" class="straico-agent-form">
            <?php wp_nonce_field('straico_' . ($is_edit ? 'edit' : 'create') . '_agent', 'straico_nonce'); ?>
            <input type="hidden" name="action" value="straico_<?php echo $is_edit ? 'edit' : 'create'; ?>_agent">
            <?php if ($is_edit) : ?>
                <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent['id']); ?>">
            <?php endif; ?>

            <div class="straico-form-section">
                <h2><?php _e('Basic Information', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="agent_name">
                                <?php _e('Name', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="agent_name" 
                                   name="agent_name" 
                                   class="regular-text" 
                                   value="<?php echo $is_edit ? esc_attr($agent['name']) : ''; ?>"
                                   required
                            >
                            <p class="description">
                                <?php _e('Enter a name for this agent.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="agent_description">
                                <?php _e('Description', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <textarea id="agent_description" 
                                      name="agent_description" 
                                      class="large-text" 
                                      rows="3" 
                                      required
                            ><?php echo $is_edit ? esc_textarea($agent['description']) : ''; ?></textarea>
                            <p class="description">
                                <?php _e('Describe the purpose of this agent.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-section">
                <h2><?php _e('Agent Configuration', 'straico-integration'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="default_llm">
                                <?php _e('Default LLM', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <?php if (is_wp_error($models)) : ?>
                                <div class="notice notice-error inline">
                                    <p><?php echo esc_html($models->get_error_message()); ?></p>
                                </div>
                            <?php else : ?>
                                <select id="default_llm" 
                                        name="default_llm" 
                                        class="regular-text" 
                                        required
                                >
                                    <option value=""><?php _e('Select a model...', 'straico-integration'); ?></option>
                                    <?php foreach ($models['data']['chat'] as $model) : ?>
                                        <option value="<?php echo esc_attr($model['model']); ?>"
                                                <?php selected($is_edit && $agent['default_llm'] === $model['model']); ?>
                                        >
                                            <?php echo esc_html($model['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Select the default LLM model for this agent.', 'straico-integration'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="custom_prompt">
                                <?php _e('Custom Prompt', 'straico-integration'); ?>
                                <span class="required">*</span>
                            </label>
                        </th>
                        <td>
                            <textarea id="custom_prompt" 
                                      name="custom_prompt" 
                                      class="large-text code" 
                                      rows="5" 
                                      required
                            ><?php echo $is_edit ? esc_textarea($agent['custom_prompt']) : ''; ?></textarea>
                            <p class="description">
                                <?php _e('Enter a custom prompt that defines the agent\'s behavior.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Tags', 'straico-integration'); ?>
                        </th>
                        <td>
                            <input type="text" 
                                   id="agent_tags" 
                                   name="agent_tags" 
                                   class="regular-text" 
                                   value="<?php echo $is_edit ? esc_attr(implode(', ', $agent['tags'])) : ''; ?>"
                            >
                            <p class="description">
                                <?php _e('Optional. Enter tags separated by commas.', 'straico-integration'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="straico-form-actions">
                <button type="submit" class="button button-primary">
                    <?php echo $is_edit ? 
                        esc_html__('Update Agent', 'straico-integration') : 
                        esc_html__('Create Agent', 'straico-integration'); 
                    ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=straico-agent-management')); ?>" class="button">
                    <?php _e('Cancel', 'straico-integration'); ?>
                </a>
                <div class="straico-loading" style="display: none;">
                    <?php echo $is_edit ? 
                        esc_html__('Updating agent...', 'straico-integration') : 
                        esc_html__('Creating agent...', 'straico-integration'); 
                    ?>
                </div>
                <div class="straico-error" style="display: none;"></div>
            </div>
        </form>
    </div>
</div>

<style>
.straico-create-agent {
    margin-top: 1em;
}

.straico-form-section {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-form-section h2 {
    margin-top: 0;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.required {
    color: #dc3232;
}

.straico-form-actions {
    margin: 2em 0;
}

.straico-form-actions .button {
    margin-right: 0.5em;
}

.straico-loading,
.straico-error {
    margin-top: 1em;
    padding: 1em;
    background: #f8f9fa;
    border-radius: 4px;
}

.straico-error {
    color: #dc3232;
    background: #f8d7da;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle tag input only - form submission is handled in agent-management.js
    $('#agent_tags').on('input', function() {
        var value = $(this).val();
        value = value.replace(/[^a-zA-Z0-9,\s-]/g, ''); // Allow letters, numbers, commas, spaces, and hyphens
        $(this).val(value);
    });
});
</script>
