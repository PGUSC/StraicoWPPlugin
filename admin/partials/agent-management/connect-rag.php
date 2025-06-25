<?php
/**
 * Admin connect RAG to agent page template.
 *
 * This template displays the form for connecting a RAG to an agent in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/agent-management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get list of agents
$agent_api = new Straico_Agent_API();
$agents = $agent_api->list_agents();

// Get list of RAGs
$rag_api = new Straico_RAG_API();
$rags = $rag_api->list_rags();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-connect-rag">
        <?php if (is_wp_error($agents) || is_wp_error($rags)) : ?>
            <div class="notice notice-error">
                <p>
                    <?php
                    if (is_wp_error($agents)) {
                        echo esc_html($agents->get_error_message());
                    }
                    if (is_wp_error($rags)) {
                        echo esc_html($rags->get_error_message());
                    }
                    ?>
                </p>
            </div>
        <?php else : ?>
            <?php if (empty($agents['data'])) : ?>
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
            <?php elseif (empty($rags['data'])) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: create RAG URL */
                            __('No RAGs found. <a href="%s">Create a RAG</a> first.', 'straico-integration'),
                            esc_url(admin_url('admin.php?page=straico-create-rag'))
                        );
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <form method="post" class="straico-connect-form">
                    <?php wp_nonce_field('straico_connect_rag', 'straico_nonce'); ?>
                    <input type="hidden" name="action" value="straico_connect_rag">

                    <div class="straico-form-section">
                        <h2><?php _e('Select Agent and RAG', 'straico-integration'); ?></h2>
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
                                            <option value="<?php echo esc_attr($formatted['id']); ?>">
                                                <?php echo esc_html($formatted['name']); ?>
                                                <?php if (isset($formatted['rag_association'])) : ?>
                                                    <?php _e('(RAG Connected)', 'straico-integration'); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Select the agent to connect a RAG to.', 'straico-integration'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="rag_id">
                                        <?php _e('RAG', 'straico-integration'); ?>
                                        <span class="required">*</span>
                                    </label>
                                </th>
                                <td>
                                    <select id="rag_id" 
                                            name="rag_id" 
                                            class="regular-text" 
                                            required
                                    >
                                        <option value=""><?php _e('Select a RAG...', 'straico-integration'); ?></option>
                                        <?php foreach ($rags['data'] as $rag) : ?>
                                            <?php $formatted = $rag_api->format_rag_info($rag); ?>
                                            <option value="<?php echo esc_attr($formatted['id']); ?>">
                                                <?php echo esc_html($formatted['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        <?php _e('Select the RAG to connect to the agent.', 'straico-integration'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="straico-form-actions">
                        <button type="submit" class="button button-primary">
                            <?php _e('Connect RAG to Agent', 'straico-integration'); ?>
                        </button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=straico-agent-management')); ?>" class="button">
                            <?php _e('Cancel', 'straico-integration'); ?>
                        </a>
                        <div class="straico-loading" style="display: none;">
                            <?php _e('Connecting...', 'straico-integration'); ?>
                        </div>
                        <div class="straico-error" style="display: none;"></div>
                    </div>
                </form>

                <div class="straico-connection-warning">
                    <p>
                        <strong><?php _e('Note:', 'straico-integration'); ?></strong>
                        <?php _e('If the selected agent already has a RAG connected, the existing connection will be replaced with the new one.', 'straico-integration'); ?>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.straico-connect-rag {
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

.straico-connection-warning {
    margin: 2em 0;
    padding: 1em;
    background: #fff8e5;
    border-left: 4px solid #ffb900;
}

.straico-connection-warning p {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('.straico-connect-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        var $loading = $form.find('.straico-loading');
        var $error = $form.find('.straico-error');

        // Reset state
        $error.hide();
        $submit.prop('disabled', true);
        $loading.show();

        // Submit form via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=straico-agent-management')); ?>';
                } else {
                    $error.text(response.data.message).show();
                    $submit.prop('disabled', false);
                    $loading.hide();
                }
            },
            error: function() {
                $error.text('<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>').show();
                $submit.prop('disabled', false);
                $loading.hide();
            }
        });
    });

    // Show warning when selecting an agent with existing RAG
    $('#agent_id').on('change', function() {
        var $selected = $(this).find('option:selected');
        if ($selected.text().indexOf('(RAG Connected)') !== -1) {
            $('.straico-connection-warning').show();
        } else {
            $('.straico-connection-warning').hide();
        }
    });
});
</script>
