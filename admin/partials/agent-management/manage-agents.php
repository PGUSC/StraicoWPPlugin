<?php
/**
 * Admin agent management page template.
 *
 * This template displays the list of existing agents in the WordPress admin area.
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
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=straico-create-agent')); ?>" class="page-title-action">
        <?php _e('Add New', 'straico-integration'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="straico-agent-management">
        <?php if (is_wp_error($agents)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($agents->get_error_message()); ?></p>
            </div>
        <?php else : ?>
            <?php if (empty($agents['data'])) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: create agent URL */
                            __('No agents found. <a href="%s">Create your first agent</a>.', 'straico-integration'),
                            esc_url(admin_url('admin.php?page=straico-create-agent'))
                        );
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <table class="widefat striped straico-agents-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'straico-integration'); ?></th>
                            <th><?php _e('Description', 'straico-integration'); ?></th>
                            <th><?php _e('Default LLM', 'straico-integration'); ?></th>
                            <th><?php _e('RAG', 'straico-integration'); ?></th>
                            <th><?php _e('Status', 'straico-integration'); ?></th>
                            <th><?php _e('Actions', 'straico-integration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents['data'] as $agent) : ?>
                            <?php $formatted = $agent_api->format_agent_info($agent); ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($formatted['name']); ?></strong>
                                    <br>
                                    <code><?php echo esc_html($formatted['uuid']); ?></code>
                                </td>
                                <td>
                                    <?php echo esc_html($formatted['description']); ?>
                                </td>
                                <td>
                                    <code><?php echo esc_html($formatted['default_llm']); ?></code>
                                </td>
                                <td>
                                    <?php if (isset($formatted['rag_association'])) : ?>
                                        <span class="straico-rag-connected">
                                            <?php _e('Connected', 'straico-integration'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="straico-rag-not-connected">
                                            <?php _e('Not Connected', 'straico-integration'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="straico-status-<?php echo esc_attr($formatted['status']); ?>">
                                        <?php echo esc_html(ucfirst($formatted['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="details">
                                            <button type="button" 
                                                    class="button-link straico-view-details"
                                                    data-agent-id="<?php echo esc_attr($formatted['id']); ?>"
                                            >
                                                <?php _e('View Details', 'straico-integration'); ?>
                                            </button>
                                        </span>
                                        |
                                        <span class="edit">
                                            <a href="<?php echo esc_url(add_query_arg(array(
                                                'page' => 'straico-create-agent',
                                                'action' => 'edit',
                                                'agent_id' => $formatted['id']
                                            ), admin_url('admin.php'))); ?>" class="straico-edit-agent">
                                                <?php _e('Edit', 'straico-integration'); ?>
                                            </a>
                                        </span>
                                        |
                                        <span class="delete">
                                            <button type="button" 
                                                    class="button-link straico-delete-agent"
                                                    data-agent-id="<?php echo esc_attr($formatted['id']); ?>"
                                                    data-agent-name="<?php echo esc_attr($formatted['name']); ?>"
                                            >
                                                <?php _e('Delete', 'straico-integration'); ?>
                                            </button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Agent Details Modal -->
<div id="straico-agent-details-modal" class="straico-modal" style="display: none;">
    <div class="straico-modal-content">
        <div class="straico-modal-header">
            <h2><?php _e('Agent Details', 'straico-integration'); ?></h2>
            <button type="button" class="straico-modal-close">&times;</button>
        </div>
        <div class="straico-modal-body">
            <div class="straico-loading">
                <?php _e('Loading...', 'straico-integration'); ?>
            </div>
            <div class="straico-details-content" style="display: none;"></div>
            <div class="straico-error" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="straico-delete-confirm-modal" class="straico-modal" style="display: none;">
    <div class="straico-modal-content">
        <div class="straico-modal-header">
            <h2><?php _e('Delete Agent', 'straico-integration'); ?></h2>
            <button type="button" class="straico-modal-close">&times;</button>
        </div>
        <div class="straico-modal-body">
            <p class="straico-delete-message"></p>
            <div class="straico-modal-actions">
                <button type="button" class="button button-primary straico-confirm-delete">
                    <?php _e('Delete', 'straico-integration'); ?>
                </button>
                <button type="button" class="button straico-modal-close">
                    <?php _e('Cancel', 'straico-integration'); ?>
                </button>
            </div>
            <div class="straico-loading" style="display: none;">
                <?php _e('Deleting...', 'straico-integration'); ?>
            </div>
            <div class="straico-error" style="display: none;"></div>
        </div>
    </div>
</div>

<style>
.straico-agent-management {
    margin-top: 1em;
}

.straico-agents-table {
    margin-top: 1em;
}

.straico-agents-table code {
    font-size: 12px;
    background: #f0f0f1;
    padding: 2px 4px;
}

.straico-status-active {
    color: #46b450;
    font-weight: 600;
}

.straico-status-inactive {
    color: #dc3232;
    font-weight: 600;
}

.straico-rag-connected {
    color: #46b450;
    font-weight: 600;
}

.straico-rag-not-connected {
    color: #72777c;
}

.straico-modal {
    display: none;
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.straico-modal-content {
    position: relative;
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    width: 70%;
    max-width: 800px;
    border-radius: 4px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.straico-modal-header {
    padding: 1em;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.straico-modal-header h2 {
    margin: 0;
    padding: 0;
}

.straico-modal-close {
    background: none;
    border: none;
    font-size: 1.5em;
    line-height: 1;
    padding: 0;
    cursor: pointer;
    color: #666;
}

.straico-modal-body {
    padding: 1em;
}

.straico-modal-actions {
    margin-top: 1em;
    text-align: right;
}

.straico-modal-actions .button {
    margin-left: 0.5em;
}

.straico-loading,
.straico-error {
    text-align: center;
    padding: 1em;
}

.straico-error {
    color: #dc3232;
}

.straico-details-content {
    max-height: 400px;
    overflow-y: auto;
}

.straico-details-content table {
    width: 100%;
    border-collapse: collapse;
}

.straico-details-content th {
    text-align: left;
    width: 30%;
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-details-content td {
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}
</style>
