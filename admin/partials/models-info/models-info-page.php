<?php
/**
 * Admin models information page template.
 *
 * This template displays the list of available LLM models in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/models-info
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get models information
$models_api = new Straico_Models_API();
$models = $models_api->get_models();

// Get cron status
$cron_manager = new Straico_Cron_Manager();
$status = $cron_manager->get_cron_status();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-models-info">
        <?php if (is_wp_error($models)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($models->get_error_message()); ?></p>
            </div>
        <?php else : ?>
            <div class="straico-update-info">
                <p>
                    <?php
                    printf(
                        /* translators: %s: next update time */
                        esc_html__('Next scheduled update: %s', 'straico-integration'),
                        esc_html($status['model_update']['next_run'])
                    );
                    ?>
                </p>
                <form method="post" class="straico-force-update-form">
                    <?php wp_nonce_field('straico_force_model_update', 'straico_nonce'); ?>
                    <input type="hidden" name="action" value="straico_force_model_update">
                    <button type="submit" class="button">
                        <?php _e('Update Now', 'straico-integration'); ?>
                    </button>
                </form>
            </div>

            <?php if (!empty($models['data']['chat'])) : ?>
                <div class="straico-models-section">
                    <h2><?php _e('Chat Models', 'straico-integration'); ?></h2>
                    <table class="widefat straico-models-table">
                        <thead>
                            <tr>
                                <th><?php _e('Model Name', 'straico-integration'); ?></th>
                                <th><?php _e('Model ID', 'straico-integration'); ?></th>
                                <th><?php _e('Word Limit', 'straico-integration'); ?></th>
                                <th><?php _e('Max Output', 'straico-integration'); ?></th>
                                <th><?php _e('Pricing', 'straico-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models['data']['chat'] as $model) : ?>
                                <tr>
                                    <td><?php echo esc_html($model['name']); ?></td>
                                    <td>
                                        <code><?php echo esc_html($model['model']); ?></code>
                                    </td>
                                    <td>
                                        <?php echo number_format($model['word_limit']); ?>
                                        <?php _e('words', 'straico-integration'); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($model['max_output']); ?>
                                        <?php _e('tokens', 'straico-integration'); ?>
                                    </td>
                                    <td>
                                        <?php
                                        printf(
                                            /* translators: %s: number of coins */
                                            esc_html__('%s coins per 100 words', 'straico-integration'),
                                            number_format($model['pricing']['coins'], 2)
                                        );
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (isset($models['data']['image']) && is_array($models['data']['image']) && !empty($models['data']['image'][0]) && is_array($models['data']['image'][0])) : ?>
                <div class="straico-models-section">
                    <h2><?php _e('Image Generation Models', 'straico-integration'); ?></h2>
                    <table class="widefat straico-models-table">
                        <thead>
                            <tr>
                                <th><?php _e('Model Name', 'straico-integration'); ?></th>
                                <th><?php _e('Model ID', 'straico-integration'); ?></th>
                                <th><?php _e('Size Options', 'straico-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($models['data']['image'][0] as $model) : ?>
                                <tr>
                                    <td><?php echo esc_html($model['name']); ?></td>
                                    <td>
                                        <code><?php echo esc_html($model['model']); ?></code>
                                    </td>
                                    <td>
                                        <ul class="straico-size-options">
                                            <?php foreach (['square', 'landscape', 'portrait'] as $size_type) : ?>
                                                <?php if (isset($model['pricing'][$size_type])) : ?>
                                                    <li>
                                                        <?php
                                                        printf(
                                                            /* translators: 1: size type 2: dimensions 3: number of coins */
                                                            esc_html__('%1$s (%2$s): %3$s coins', 'straico-integration'),
                                                            ucfirst($size_type),
                                                            esc_html($model['pricing'][$size_type]['size']),
                                                            number_format($model['pricing'][$size_type]['coins'])
                                                        );
                                                        ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php
            // Get recent model changes
            $model_updates = new Straico_Model_Updates();
            $recent_changes = $model_updates->get_recent_changes(5);
            if (!empty($recent_changes)) :
            ?>
                <div class="straico-models-section">
                    <h2><?php _e('Recent Changes', 'straico-integration'); ?></h2>
                    <table class="widefat straico-changes-table">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'straico-integration'); ?></th>
                                <th><?php _e('Model', 'straico-integration'); ?></th>
                                <th><?php _e('Type', 'straico-integration'); ?></th>
                                <th><?php _e('Change', 'straico-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_changes as $change) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html(get_date_from_gmt($change->created_at)); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($change->model_name); ?>
                                        <br>
                                        <code><?php echo esc_html($change->model_id); ?></code>
                                    </td>
                                    <td>
                                        <?php echo esc_html(ucfirst($change->model_type)); ?>
                                    </td>
                                    <td>
                                        <?php if ($change->change_type === 'added') : ?>
                                            <span class="straico-change-added">
                                                <?php _e('Added', 'straico-integration'); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="straico-change-removed">
                                                <?php _e('Removed', 'straico-integration'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.straico-models-info {
    margin-top: 1em;
}

.straico-models-section {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-models-section h2 {
    margin-top: 0;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-update-info {
    display: flex;
    align-items: center;
    gap: 1em;
    margin-bottom: 2em;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
}

.straico-update-info p {
    margin: 0;
}

.straico-models-table {
    margin-top: 1em;
}

.straico-models-table code {
    font-size: 12px;
    background: #f0f0f1;
    padding: 2px 4px;
}

.straico-size-options {
    margin: 0;
    padding: 0;
    list-style: none;
}

.straico-size-options li {
    margin-bottom: 0.5em;
}

.straico-changes-table .straico-change-added {
    color: #46b450;
    font-weight: 600;
}

.straico-changes-table .straico-change-removed {
    color: #dc3232;
    font-weight: 600;
}

.straico-force-update-form {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.straico-force-update-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('<?php _e('Updating...', 'straico-integration'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: $form.serialize(),
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'straico-integration'); ?>');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>
