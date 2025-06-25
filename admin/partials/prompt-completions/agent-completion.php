<?php
/**
 * Admin agent prompt completion page template.
 *
 * This template displays the form for submitting agent prompt completions in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/prompt-completions
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get list of agents
$agent_api = new Straico_Agent_API();
$agents = $agent_api->list_agents();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-prompt-completion">
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
            <form method="post" class="straico-prompt-form">
                <?php wp_nonce_field('straico_agent_prompt', 'straico_nonce'); ?>
                <input type="hidden" name="action" value="straico_agent_prompt">

                <div class="straico-form-section">
                    <h2><?php _e('Agent Selection', 'straico-integration'); ?></h2>
                    <table class="form-table">
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
                    <h2><?php _e('Prompt', 'straico-integration'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="prompt_message">
                                    <?php _e('Message', 'straico-integration'); ?>
                                    <span class="required">*</span>
                                </label>
                            </th>
                            <td>
                                <textarea id="prompt_message" 
                                          name="prompt" 
                                          class="large-text code" 
                                          rows="5" 
                                          required
                                ></textarea>
                                <p class="description">
                                    <?php _e('Enter your prompt message.', 'straico-integration'); ?>
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
                        <?php _e('Submit Prompt', 'straico-integration'); ?>
                    </button>
                    <button type="reset" class="button">
                        <?php _e('Reset', 'straico-integration'); ?>
                    </button>
                    <div class="straico-loading" style="display: none;">
                        <?php _e('Processing prompt...', 'straico-integration'); ?>
                    </div>
                    <div class="straico-error" style="display: none;"></div>
                </div>
            </form>

            <div class="straico-response" style="display: none;">
                <div class="straico-response-header">
                    <h2><?php _e('Response', 'straico-integration'); ?></h2>
                    <button type="button" class="button straico-reset-prompt">
                        <?php _e('New Prompt', 'straico-integration'); ?>
                    </button>
                </div>
                <div class="straico-response-content"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.straico-prompt-completion {
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

.straico-response {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-response-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1em;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-response-header h2 {
    margin: 0;
    padding: 0;
}

.straico-response-content {
    max-height: 500px;
    overflow-y: auto;
    padding: 1em;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.straico-answer {
    white-space: pre-wrap;
    margin-bottom: 1em;
}

.straico-references {
    margin-top: 1em;
    padding-top: 1em;
    border-top: 1px solid #eee;
}

.straico-references h3 {
    margin-top: 0;
}

.straico-reference {
    margin-bottom: 1em;
    padding: 1em;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.straico-reference-content {
    white-space: pre-wrap;
}

.straico-reference-page {
    margin-top: 0.5em;
    color: #666;
    font-size: 0.9em;
}

.straico-usage {
    margin-top: 1em;
    padding-top: 1em;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 0.9em;
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
    $('.straico-prompt-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $reset = $form.find('button[type="reset"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');
        var $response = $('.straico-response');
        var $responseContent = $('.straico-response-content');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $reset.prop('disabled', true);
        $loading.show();
        $response.hide();
        $responseContent.empty();

        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    // Format and display response
                    var html = '';

                    // Add answer
                    html += '<div class="straico-answer">' + response.data.response.answer + '</div>';

                    // Add references
                    if (response.data.response.references && response.data.response.references.length > 0) {
                        html += '<div class="straico-references">';
                        html += '<h3><?php _e('References', 'straico-integration'); ?></h3>';
                        $.each(response.data.response.references, function(i, ref) {
                            html += '<div class="straico-reference">';
                            html += '<div class="straico-reference-content">' + ref.page_content + '</div>';
                            if (ref.page) {
                                html += '<div class="straico-reference-page">';
                                html += '<?php _e('Page:', 'straico-integration'); ?> ' + ref.page;
                                html += '</div>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }

                    // Add usage information
                    html += '<div class="straico-usage">';
                    html += '<strong><?php _e('Cost:', 'straico-integration'); ?></strong> ';
                    html += response.data.response.coins_used.toFixed(2) + ' <?php _e('coins', 'straico-integration'); ?>';
                    html += '</div>';

                    $responseContent.html(html);
                    $response.show();
                    $form.hide();
                } else {
                    $error.text(response.data.message).show();
                    $submit.prop('disabled', false);
                    $reset.prop('disabled', false);
                }
                $loading.hide();
            },
            error: function() {
                $error.text('<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>').show();
                $submit.prop('disabled', false);
                $reset.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Handle form reset
    $('.straico-prompt-form button[type="reset"]').on('click', function() {
        var $form = $(this).closest('form');
        $form.find('.straico-error').hide();
        $('.straico-agent-info').empty();
        $('#search_type').trigger('change');
    });

    // Handle new prompt button
    $('.straico-reset-prompt').on('click', function() {
        $('.straico-prompt-form').show().trigger('reset');
        $('.straico-response').hide();
        $('.straico-error').hide();
        $('.straico-agent-info').empty();
        $('#search_type').trigger('change');
    });

    // Initialize search type options
    $('#search_type').trigger('change');
});
</script>
