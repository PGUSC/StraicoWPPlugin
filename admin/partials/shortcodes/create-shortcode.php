<?php
/**
 * Admin create shortcode page template.
 *
 * This template displays the form for creating agent shortcodes in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get list of agents
$agent_api = new Straico_Agent_API();
$agents = $agent_api->list_agents();

// Get default settings
$shortcode_manager = new Straico_Shortcode_Manager();
$default_settings = $shortcode_manager->get_default_settings();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-create-shortcode">
        <?php if (is_wp_error($agents)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($agents->get_error_message()); ?></p>
            </div>
        <?php elseif (empty($agents['data'])) : ?>
            <div class="notice notice-info">
                <p>
                    <?php
                    printf(
                        /* translators: %s: create agent URL */
                        __('No agents found. <a href="%s">Create an agent</a> first.', 'straico-integration'),
                        esc_url(admin_url('admin.php?page=straico-create-agent'))
                    );
                    ?>
                </p>
            </div>
        <?php else : ?>
            <form method="post" class="straico-shortcode-form">
                <?php wp_nonce_field('straico_create_shortcode', 'straico_nonce'); ?>
                <input type="hidden" name="action" value="straico_create_shortcode">

                <div class="straico-form-section">
                    <h2><?php _e('Basic Information', 'straico-integration'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="shortcode_name">
                                    <?php _e('Name', 'straico-integration'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="shortcode_name" 
                                       name="shortcode_name" 
                                       class="regular-text" 
                                       required
                                >
                                <p class="description">
                                    <?php _e('Enter a name for this shortcode. The actual shortcode will be [straico_name].', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="agent_id">
                                    <?php _e('Agent', 'straico-integration'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <select id="agent_id" 
                                        name="agent_id" 
                                        class="regular-text" 
                                        required
                                >
                                    <option value=""><?php _e('Select an agent...', 'straico-integration'); ?></option>
                                    <?php foreach ($agents['data'] as $agent) : ?>
                                        <?php $formatted = $agent_api->format_agent_info($agent); ?>
                                        <option value="<?php echo esc_attr($formatted['id']); ?>"
                                                data-model="<?php echo esc_attr($formatted['default_llm']); ?>"
                                                data-prompt="<?php echo esc_attr($formatted['custom_prompt']); ?>"
                                                data-rag="<?php echo isset($formatted['rag_association']) ? '1' : '0'; ?>"
                                        >
                                            <?php echo esc_html($formatted['name']); ?>
                                            <?php if (isset($formatted['rag_association'])) : ?>
                                                <?php _e('(RAG Connected)', 'straico-integration'); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description straico-agent-info"></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="straico-form-section">
                    <h2><?php _e('Display Settings', 'straico-integration'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="prompt_placeholder">
                                    <?php _e('Prompt Placeholder', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="prompt_placeholder" 
                                       name="prompt_placeholder" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($default_settings['prompt_placeholder']); ?>"
                                >
                                <p class="description">
                                    <?php _e('Placeholder text for the prompt input field.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="submit_button_text">
                                    <?php _e('Submit Button Text', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="submit_button_text" 
                                       name="submit_button_text" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($default_settings['submit_button_text']); ?>"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="reset_button_text">
                                    <?php _e('Reset Button Text', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="reset_button_text" 
                                       name="reset_button_text" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($default_settings['reset_button_text']); ?>"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="loading_text">
                                    <?php _e('Loading Text', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="loading_text" 
                                       name="loading_text" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($default_settings['loading_text']); ?>"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="error_text">
                                    <?php _e('Error Text', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="error_text" 
                                       name="error_text" 
                                       class="regular-text" 
                                       value="<?php echo esc_attr($default_settings['error_text']); ?>"
                                >
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e('Display Reset Button', 'straico-integration'); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <label for="display_reset">
                                        <input type="checkbox" 
                                               id="display_reset" 
                                               name="display_reset" 
                                               value="1" 
                                               <?php checked($default_settings['display_reset']); ?>
                                        >
                                        <?php _e('Show reset button after completion', 'straico-integration'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="straico-form-section">
                    <h2><?php _e('Completion Settings', 'straico-integration'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="temperature">
                                    <?php _e('Temperature', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="temperature" 
                                       name="temperature" 
                                       class="small-text" 
                                       value="<?php echo esc_attr($default_settings['temperature']); ?>" 
                                       min="0" 
                                       max="2" 
                                       step="0.1"
                                >
                                <p class="description">
                                    <?php _e('Controls randomness in the response (0-2). Higher values make the output more random.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="max_tokens">
                                    <?php _e('Max Tokens', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_tokens" 
                                       name="max_tokens" 
                                       class="small-text" 
                                       value="<?php echo esc_attr($default_settings['max_tokens']); ?>" 
                                       min="1"
                                >
                                <p class="description">
                                    <?php _e('Maximum number of tokens to generate.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="straico-form-section straico-search-options" style="display: none;">
                    <h2><?php _e('Search Options', 'straico-integration'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="search_type">
                                    <?php _e('Search Type', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <select id="search_type" 
                                        name="search_type" 
                                        class="regular-text"
                                >
                                    <option value="similarity"><?php _e('Similarity', 'straico-integration'); ?></option>
                                    <option value="mmr"><?php _e('Maximum Marginal Relevance (MMR)', 'straico-integration'); ?></option>
                                    <option value="similarity_score_threshold"><?php _e('Similarity Score Threshold', 'straico-integration'); ?></option>
                                </select>
                                <p class="description straico-search-description">
                                    <?php _e('Basic similarity search between the query and documents.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="k">
                                    <?php _e('Number of Results (k)', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="k" 
                                       name="k" 
                                       class="small-text" 
                                       value="4" 
                                       min="1"
                                >
                                <p class="description">
                                    <?php _e('Number of documents to return.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="straico-mmr-options" style="display: none;">
                            <th scope="row">
                                <label for="fetch_k">
                                    <?php _e('Fetch k', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="fetch_k" 
                                       name="fetch_k" 
                                       class="small-text" 
                                       value="20" 
                                       min="1"
                                >
                                <p class="description">
                                    <?php _e('Number of documents to pass to MMR algorithm.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="straico-mmr-options" style="display: none;">
                            <th scope="row">
                                <label for="lambda_mult">
                                    <?php _e('Lambda Multiplier', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="lambda_mult" 
                                       name="lambda_mult" 
                                       class="small-text" 
                                       value="0.5" 
                                       min="0" 
                                       max="1" 
                                       step="0.1"
                                >
                                <p class="description">
                                    <?php _e('Diversity of results (0-1). 0 for maximum diversity, 1 for minimum.', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="straico-threshold-options" style="display: none;">
                            <th scope="row">
                                <label for="score_threshold">
                                    <?php _e('Score Threshold', 'straico-integration'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="score_threshold" 
                                       name="score_threshold" 
                                       class="small-text" 
                                       value="0.5" 
                                       min="0" 
                                       max="1" 
                                       step="0.1"
                                >
                                <p class="description">
                                    <?php _e('Minimum relevance threshold (0-1).', 'straico-integration'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="straico-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Create Shortcode', 'straico-integration'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=straico-manage-shortcodes')); ?>" class="button">
                        <?php _e('Cancel', 'straico-integration'); ?>
                    </a>
                    <div class="straico-loading" style="display: none;">
                        <?php _e('Creating shortcode...', 'straico-integration'); ?>
                    </div>
                    <div class="straico-error" style="display: none;"></div>
                </div>
            </form>

            <div class="straico-preview" style="display: none;">
                <div class="straico-preview-header">
                    <h2><?php _e('Shortcode Preview', 'straico-integration'); ?></h2>
                    <button type="button" class="button straico-edit-shortcode">
                        <?php _e('Edit Settings', 'straico-integration'); ?>
                    </button>
                </div>
                <div class="straico-preview-content"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.straico-create-shortcode {
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

.straico-preview {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1em;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-preview-header h2 {
    margin: 0;
    padding: 0;
}

.straico-preview-content {
    padding: 1em;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.straico-shortcode {
    font-family: monospace;
    background: #fff;
    padding: 0.5em;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.straico-settings-preview {
    margin-top: 1em;
}

.straico-settings-preview table {
    width: 100%;
    border-collapse: collapse;
}

.straico-settings-preview th {
    text-align: left;
    width: 30%;
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-settings-preview td {
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Update agent info and options
    $('#agent_id').on('change', function() {
        var $selected = $(this).find('option:selected');
        var model = $selected.data('model');
        var prompt = $selected.data('prompt');
        var hasRag = $selected.data('rag') === 1;
        var $description = $('.straico-agent-info');
        var $searchOptions = $('.straico-search-options');

        if (model && prompt) {
            $description.html(
                '<strong><?php _e('Model:', 'straico-integration'); ?></strong> ' + model + '<br>' +
                '<strong><?php _e('Custom Prompt:', 'straico-integration'); ?></strong> ' + prompt
            );
        } else {
            $description.empty();
        }

        $searchOptions.toggle(hasRag);
    });

    // Update search type description and options
    $('#search_type').on('change', function() {
        var type = $(this).val();
        var descriptions = {
            'similarity': '<?php _e('Basic similarity search between the query and documents.', 'straico-integration'); ?>',
            'mmr': '<?php _e('Maximizes relevance while maintaining diversity in results.', 'straico-integration'); ?>',
            'similarity_score_threshold': '<?php _e('Returns only documents above a similarity threshold.', 'straico-integration'); ?>'
        };

        $('.straico-search-description').text(descriptions[type]);
        $('.straico-mmr-options').toggle(type === 'mmr');
        $('.straico-threshold-options').toggle(type === 'similarity_score_threshold');
    });

    // Handle form submission
    $('.straico-shortcode-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');
        var $preview = $('.straico-preview');
        var $previewContent = $('.straico-preview-content');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $loading.show();
        $preview.hide();
        $previewContent.empty();

        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Format and display preview
                    var html = '';

                    // Add shortcode
                    html += '<div class="straico-shortcode">';
                    html += '[straico_' + response.data.shortcode.name + ']';
                    html += '</div>';

                    // Add settings preview
                    html += '<div class="straico-settings-preview">';
                    html += '<h3><?php _e('Settings', 'straico-integration'); ?></h3>';
                    html += '<table>';
                    html += '<tr>';
                    html += '<th><?php _e('Agent', 'straico-integration'); ?></th>';
                    html += '<td>' + response.data.agent.name + '</td>';
                    html += '</tr>';
                    html += '<tr>';
                    html += '<th><?php _e('Model', 'straico-integration'); ?></th>';
                    html += '<td>' + response.data.agent.default_llm + '</td>';
                    html += '</tr>';
                    html += '<tr>';
                    html += '<th><?php _e('Temperature', 'straico-integration'); ?></th>';
                    html += '<td>' + response.data.shortcode.settings.temperature + '</td>';
                    html += '</tr>';
                    html += '<tr>';
                    html += '<th><?php _e('Max Tokens', 'straico-integration'); ?></th>';
                    html += '<td>' + response.data.shortcode.settings.max_tokens + '</td>';
                    html += '</tr>';
                    html += '</table>';
                    html += '</div>';

                    $previewContent.html(html);
                    $form.hide();
                    $preview.show();
                } else {
                    $error.text(response.data.message).show();
                    $submit.prop('disabled', false);
                }
                $loading.hide();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.log('Response:', xhr.responseText);
                $error.text('<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>').show();
                $submit.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Handle edit button
    $('.straico-edit-shortcode').on('click', function() {
        $('.straico-shortcode-form').show();
        $('.straico-preview').hide();
    });

    // Validate shortcode name
    $('#shortcode_name').on('input', function() {
        var value = $(this).val();
        value = value.replace(/[^a-zA-Z0-9_-]/g, ''); // Allow letters, numbers, underscores, and hyphens
        $(this).val(value);
    });

    // Initialize search type options
    $('#search_type').trigger('change');
});
</script>
