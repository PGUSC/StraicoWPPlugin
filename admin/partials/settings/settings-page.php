<?php
/**
 * Admin settings page template.
 *
 * This template displays the plugin's settings page in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('straico-integration');
        do_settings_sections('straico-integration');
        ?>

        <div class="straico-settings-section">
            <h2><?php _e('API Settings', 'straico-integration'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="straico_api_key">
                            <?php _e('API Key', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="password" 
                               id="straico_api_key" 
                               name="straico_api_key" 
                               value="<?php echo esc_attr(get_option('straico_api_key')); ?>" 
                               class="regular-text"
                               autocomplete="off"
                        >
                        <p class="description">
                            <?php _e('Enter your Straico API key. You can find this in your Straico dashboard.', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="straico-settings-section">
            <h2><?php _e('Model Update Settings', 'straico-integration'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="straico_model_update_frequency">
                            <?php _e('Update Frequency', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="straico_model_update_frequency" 
                               name="straico_model_update_frequency" 
                               value="<?php echo esc_attr(get_option('straico_model_update_frequency', 12)); ?>" 
                               class="small-text"
                               min="1"
                               max="72"
                        >
                        <span class="description">
                            <?php _e('hours', 'straico-integration'); ?>
                        </span>
                        <p class="description">
                            <?php _e('How often to check for updates to the list of available LLM models (1-72 hours).', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Model Change Notifications', 'straico-integration'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label for="straico_notify_model_changes">
                                <input type="checkbox" 
                                       id="straico_notify_model_changes" 
                                       name="straico_notify_model_changes" 
                                       value="1" 
                                       <?php checked(get_option('straico_notify_model_changes')); ?>
                                >
                                <?php _e('Enable email notifications when models are added or removed', 'straico-integration'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="straico_model_notification_emails">
                            <?php _e('Notification Emails', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="straico_model_notification_emails" 
                                  name="straico_model_notification_emails" 
                                  rows="3" 
                                  class="large-text code"
                        ><?php echo esc_textarea(implode("\n", (array) get_option('straico_model_notification_emails', array()))); ?></textarea>
                        <p class="description">
                            <?php _e('Enter email addresses that should receive model change notifications (one per line).', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="straico-settings-section">
            <h2><?php _e('Low Coins Notification Settings', 'straico-integration'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Low Coins Notifications', 'straico-integration'); ?>
                    </th>
                    <td>
                        <fieldset>
                            <label for="straico_notify_low_coins">
                                <input type="checkbox" 
                                       id="straico_notify_low_coins" 
                                       name="straico_notify_low_coins" 
                                       value="1" 
                                       <?php checked(get_option('straico_notify_low_coins')); ?>
                                >
                                <?php _e('Enable email notifications when coin balance is low', 'straico-integration'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="straico_low_coin_threshold">
                            <?php _e('Low Coin Threshold', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="straico_low_coin_threshold" 
                               name="straico_low_coin_threshold" 
                               value="<?php echo esc_attr(get_option('straico_low_coin_threshold', 100)); ?>" 
                               class="regular-text"
                               min="1"
                               step="0.01"
                        >
                        <p class="description">
                            <?php _e('Send a notification when coin balance falls below this amount.', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="straico_coin_check_frequency">
                            <?php _e('Check Frequency', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="straico_coin_check_frequency" 
                               name="straico_coin_check_frequency" 
                               value="<?php echo esc_attr(get_option('straico_coin_check_frequency', 360)); ?>" 
                               class="small-text"
                               min="5"
                               max="1440"
                        >
                        <span class="description">
                            <?php _e('minutes', 'straico-integration'); ?>
                        </span>
                        <p class="description">
                            <?php _e('How often to check the coin balance (5-1440 minutes).', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="straico_low_coin_notification_emails">
                            <?php _e('Notification Emails', 'straico-integration'); ?>
                        </label>
                    </th>
                    <td>
                        <textarea id="straico_low_coin_notification_emails" 
                                  name="straico_low_coin_notification_emails" 
                                  rows="3" 
                                  class="large-text code"
                        ><?php echo esc_textarea(implode("\n", (array) get_option('straico_low_coin_notification_emails', array()))); ?></textarea>
                        <p class="description">
                            <?php _e('Enter email addresses that should receive low coin notifications (one per line).', 'straico-integration'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>

    <div class="straico-settings-section">
        <h2><?php _e('Cron Status', 'straico-integration'); ?></h2>
        <?php
        $cron_manager = new Straico_Cron_Manager();
        $status = $cron_manager->get_cron_status();
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Task', 'straico-integration'); ?></th>
                    <th><?php _e('Status', 'straico-integration'); ?></th>
                    <th><?php _e('Next Run', 'straico-integration'); ?></th>
                    <th><?php _e('Frequency', 'straico-integration'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php _e('Model Updates', 'straico-integration'); ?></td>
                    <td>
                        <?php if ($status['model_update']['enabled']) : ?>
                            <span class="straico-status-enabled">
                                <?php _e('Enabled', 'straico-integration'); ?>
                            </span>
                        <?php else : ?>
                            <span class="straico-status-disabled">
                                <?php _e('Disabled', 'straico-integration'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($status['model_update']['next_run']); ?></td>
                    <td><?php echo esc_html($status['model_update']['frequency']); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Coin Balance Check', 'straico-integration'); ?></td>
                    <td>
                        <?php if ($status['coin_check']['enabled']) : ?>
                            <span class="straico-status-enabled">
                                <?php _e('Enabled', 'straico-integration'); ?>
                            </span>
                        <?php else : ?>
                            <span class="straico-status-disabled">
                                <?php _e('Disabled', 'straico-integration'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($status['coin_check']['next_run']); ?></td>
                    <td>
                        <?php
                        echo esc_html($status['coin_check']['frequency']);
                        if ($status['coin_check']['enabled']) {
                            echo ' (' . sprintf(
                                /* translators: %s: threshold amount */
                                esc_html__('Threshold: %s coins', 'straico-integration'),
                                number_format($status['coin_check']['threshold'], 2)
                            ) . ')';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="description">
            <?php _e('Note: Changes to update frequencies will take effect after saving the settings.', 'straico-integration'); ?>
        </p>
    </div>
</div>

<style>
.straico-settings-section {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-settings-section h2 {
    margin-top: 0;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-status-enabled {
    color: #46b450;
    font-weight: 600;
}

.straico-status-disabled {
    color: #dc3232;
    font-weight: 600;
}

.form-table td fieldset label {
    margin: 0.25em 0 !important;
    display: block;
}
</style>
