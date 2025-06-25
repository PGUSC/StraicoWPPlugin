<?php
/**
 * Admin manage shortcodes page template.
 *
 * This template displays the list of existing shortcodes in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get list of shortcodes
$shortcode_manager = new Straico_Shortcode_Manager();
$shortcodes = $shortcode_manager->get_shortcodes();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=straico-create-shortcode')); ?>" class="page-title-action">
        <?php _e('Add New', 'straico-integration'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="straico-manage-shortcodes">
        <?php if (empty($shortcodes)) : ?>
            <div class="notice notice-info">
                <p>
                    <?php
                    printf(
                        /* translators: %s: create shortcode URL */
                        __('No shortcodes found. <a href="%s">Create your first shortcode</a>.', 'straico-integration'),
                        esc_url(admin_url('admin.php?page=straico-create-shortcode'))
                    );
                    ?>
                </p>
            </div>
        <?php else : ?>
            <table class="widefat striped straico-shortcodes-table">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'straico-integration'); ?></th>
                        <th><?php _e('Agent', 'straico-integration'); ?></th>
                        <th><?php _e('Settings', 'straico-integration'); ?></th>
                        <th><?php _e('Created', 'straico-integration'); ?></th>
                        <th><?php _e('Actions', 'straico-integration'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcodes as $shortcode) : ?>
                        <?php $formatted = $shortcode_manager->format_shortcode_info($shortcode); ?>
                        <tr>
                            <td>
                                <code>[<?php echo esc_html($formatted['full_name']); ?>]</code>
                                <button type="button" 
                                        class="button-link straico-copy-shortcode"
                                        data-shortcode="[<?php echo esc_attr($formatted['full_name']); ?>]"
                                >
                                    <?php _e('Copy', 'straico-integration'); ?>
                                </button>
                            </td>
                            <td>
                                <?php
                                $agent_api = new Straico_Agent_API();
                                $agent = $agent_api->get_agent_details($formatted['agent_id']);
                                if (!is_wp_error($agent)) {
                                    $agent = $agent_api->format_agent_info($agent['data']);
                                    echo esc_html($agent['name']);
                                    if (isset($agent['rag_association'])) {
                                        echo ' ' . esc_html__('(RAG Connected)', 'straico-integration');
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <button type="button" 
                                        class="button-link straico-view-settings"
                                        data-settings="<?php echo esc_attr(wp_json_encode($formatted['settings'])); ?>"
                                >
                                    <?php _e('View Settings', 'straico-integration'); ?>
                                </button>
                            </td>
                            <td>
                                <?php echo esc_html($formatted['created_at']); ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <span class="edit">
                                        <a href="<?php echo esc_url(add_query_arg(array(
                                            'page' => 'straico-create-shortcode',
                                            'action' => 'edit',
                                            'shortcode_id' => $formatted['id']
                                        ), admin_url('admin.php'))); ?>" class="straico-edit-shortcode">
                                            <?php _e('Edit', 'straico-integration'); ?>
                                        </a>
                                    </span>
                                    |
                                    <span class="delete">
                                        <button type="button" 
                                                class="button-link straico-delete-shortcode"
                                                data-shortcode-id="<?php echo esc_attr($formatted['id']); ?>"
                                                data-shortcode-name="<?php echo esc_attr($formatted['name']); ?>"
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
    </div>
</div>

<!-- Settings Modal -->
<div id="straico-settings-modal" class="straico-modal" style="display: none;">
    <div class="straico-modal-content">
        <div class="straico-modal-header">
            <h2><?php _e('Shortcode Settings', 'straico-integration'); ?></h2>
            <button type="button" class="straico-modal-close">&times;</button>
        </div>
        <div class="straico-modal-body">
            <table class="widefat straico-settings-table">
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="straico-delete-confirm-modal" class="straico-modal" style="display: none;">
    <div class="straico-modal-content">
        <div class="straico-modal-header">
            <h2><?php _e('Delete Shortcode', 'straico-integration'); ?></h2>
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
.straico-manage-shortcodes {
    margin-top: 1em;
}

.straico-shortcodes-table {
    margin-top: 1em;
}

.straico-shortcodes-table code {
    font-size: 12px;
    background: #f0f0f1;
    padding: 2px 4px;
}

.straico-copy-shortcode {
    margin-left: 0.5em;
    color: #2271b1;
}

.straico-copy-shortcode:hover {
    color: #135e96;
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

.straico-settings-table {
    margin-top: 1em;
}

.straico-settings-table th {
    width: 30%;
    text-align: left;
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-settings-table td {
    padding: 0.5em;
    border-bottom: 1px solid #eee;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Copy shortcode to clipboard
    $('.straico-copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();

        var $button = $(this);
        var originalText = $button.text();
        $button.text('<?php _e('Copied!', 'straico-integration'); ?>');
        setTimeout(function() {
            $button.text(originalText);
        }, 1000);
    });

    // View settings
    $('.straico-view-settings').on('click', function() {
        var settings = $(this).data('settings');
        var $modal = $('#straico-settings-modal');
        var $table = $modal.find('.straico-settings-table tbody');

        $table.empty();

        // Add settings rows
        $.each(settings, function(key, value) {
            var label = key.replace(/_/g, ' ');
            label = label.charAt(0).toUpperCase() + label.slice(1);

            var row = '<tr>' +
                '<th>' + label + '</th>' +
                '<td>' + (typeof value === 'boolean' ? (value ? 'Yes' : 'No') : value) + '</td>' +
                '</tr>';

            $table.append(row);
        });

        $modal.show();
    });

    // Delete shortcode
    $('.straico-delete-shortcode').on('click', function() {
        var shortcodeId = $(this).data('shortcode-id');
        var shortcodeName = $(this).data('shortcode-name');
        var $modal = $('#straico-delete-confirm-modal');
        var $message = $modal.find('.straico-delete-message');
        var $loading = $modal.find('.straico-loading');
        var $error = $modal.find('.straico-error');
        var $actions = $modal.find('.straico-modal-actions');

        $message.html(
            '<?php _e('Are you sure you want to delete the shortcode:', 'straico-integration'); ?> ' +
            '<strong>[straico_' + shortcodeName + ']</strong>?' +
            '<br><br>' +
            '<?php _e('This action cannot be undone.', 'straico-integration'); ?>'
        );

        $error.hide();
        $loading.hide();
        $actions.show();
        $modal.show();

        // Store shortcode ID for deletion
        $modal.data('shortcode-id', shortcodeId);
    });

    // Confirm shortcode deletion
    $('.straico-confirm-delete').on('click', function() {
        var $modal = $('#straico-delete-confirm-modal');
        var shortcodeId = $modal.data('shortcode-id');
        var $loading = $modal.find('.straico-loading');
        var $error = $modal.find('.straico-error');
        var $actions = $modal.find('.straico-modal-actions');

        $error.hide();
        $actions.hide();
        $loading.show();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'straico_delete_shortcode',
                nonce: '<?php echo wp_create_nonce('straico_delete_shortcode'); ?>',
                shortcode_id: shortcodeId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    $loading.hide();
                    $error.text(response.data.message).show();
                    $actions.show();
                }
            },
            error: function() {
                $loading.hide();
                $error.text('<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>').show();
                $actions.show();
            }
        });
    });

    // Close modals
    $('.straico-modal-close').on('click', function() {
        $(this).closest('.straico-modal').hide();
    });

    // Close modals when clicking outside
    $('.straico-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    // Prevent modal content clicks from closing modal
    $('.straico-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
