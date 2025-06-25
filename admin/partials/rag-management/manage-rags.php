<?php
/**
 * Admin RAG management page template.
 *
 * This template displays the list of existing RAGs in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/rag-management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get list of RAGs
$rag_api = new Straico_RAG_API();
$rags = $rag_api->list_rags();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=straico-create-rag')); ?>" class="page-title-action">
        <?php _e('Add New', 'straico-integration'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="straico-rag-management">
        <?php if (is_wp_error($rags)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($rags->get_error_message()); ?></p>
            </div>
        <?php else : ?>
            <?php if (empty($rags['data'])) : ?>
                <div class="notice notice-info">
                    <p>
                        <?php
                        printf(
                            /* translators: %s: create RAG URL */
                            __('No RAGs found. <a href="%s">Create your first RAG</a>.', 'straico-integration'),
                            esc_url(admin_url('admin.php?page=straico-create-rag'))
                        );
                        ?>
                    </p>
                </div>
            <?php else : ?>
                <table class="widefat striped straico-rags-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'straico-integration'); ?></th>
                            <th><?php _e('Files', 'straico-integration'); ?></th>
                            <th><?php _e('Chunking Method', 'straico-integration'); ?></th>
                            <th><?php _e('Created', 'straico-integration'); ?></th>
                            <th><?php _e('Actions', 'straico-integration'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rags['data'] as $rag) : ?>
                            <?php $formatted = $rag_api->format_rag_info($rag); ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($formatted['name']); ?></strong>
                                    <br>
                                    <code><?php echo esc_html($formatted['id']); ?></code>
                                </td>
                                <td>
                                    <ul class="straico-file-list">
                                        <?php foreach ($formatted['original_files'] as $file) : ?>
                                            <li><?php echo esc_html($file); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <?php echo esc_html(ucfirst($formatted['chunking_method'])); ?>
                                    <br>
                                    <small>
                                        <?php
                                        printf(
                                            /* translators: 1: chunk size 2: chunk overlap */
                                            __('Size: %1$d, Overlap: %2$d', 'straico-integration'),
                                            $formatted['chunk_size'],
                                            $formatted['chunk_overlap']
                                        );
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo esc_html($formatted['created_at']); ?>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <span class="details">
                                            <button type="button" 
                                                    class="button-link straico-view-details"
                                                    data-rag-id="<?php echo esc_attr($formatted['id']); ?>"
                                            >
                                                <?php _e('View Details', 'straico-integration'); ?>
                                            </button>
                                        </span>
                                        |
                                        <span class="delete">
                                            <button type="button" 
                                                    class="button-link straico-delete-rag"
                                                    data-rag-id="<?php echo esc_attr($formatted['id']); ?>"
                                                    data-rag-name="<?php echo esc_attr($formatted['name']); ?>"
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

<!-- RAG Details Modal -->
<div id="straico-rag-details-modal" class="straico-modal" style="display: none;">
    <div class="straico-modal-content">
        <div class="straico-modal-header">
            <h2><?php _e('RAG Details', 'straico-integration'); ?></h2>
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
            <h2><?php _e('Delete RAG', 'straico-integration'); ?></h2>
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
.straico-rag-management {
    margin-top: 1em;
}

.straico-rags-table {
    margin-top: 1em;
}

.straico-rags-table code {
    font-size: 12px;
    background: #f0f0f1;
    padding: 2px 4px;
}

.straico-file-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.straico-file-list li {
    margin-bottom: 0.25em;
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
