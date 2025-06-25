<?php
/**
 * Admin user information page template.
 *
 * This template displays the user's account information in the WordPress admin area.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/admin/partials/user-info
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get user information
$user_api = new Straico_User_API();
$user_info = $user_api->get_user_info();

// Get coin balance check status
$cron_manager = new Straico_Cron_Manager();
$status = $cron_manager->get_cron_status();

// Get recent balance history
$balance_check = new Straico_Balance_Check();
$recent_checks = $balance_check->get_recent_checks(5);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="straico-user-info">
        <?php if (is_wp_error($user_info)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($user_info->get_error_message()); ?></p>
            </div>
        <?php else : ?>
            <div class="straico-info-section">
                <h2><?php _e('Account Information', 'straico-integration'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Name', 'straico-integration'); ?></th>
                            <td>
                                <?php
                                printf(
                                    '%s %s',
                                    esc_html($user_info['data']['first_name']),
                                    esc_html($user_info['data']['last_name'])
                                );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Plan', 'straico-integration'); ?></th>
                            <td><?php echo esc_html($user_info['data']['plan']); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Coin Balance', 'straico-integration'); ?></th>
                            <td>
                                <span class="straico-coin-balance">
                                    <?php echo number_format($user_info['data']['coins'], 2); ?>
                                </span>
                                <?php _e('coins', 'straico-integration'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="straico-info-section">
                <h2><?php _e('Balance Check Settings', 'straico-integration'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Status', 'straico-integration'); ?></th>
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
                        </tr>
                        <?php if ($status['coin_check']['enabled']) : ?>
                            <tr>
                                <th scope="row"><?php _e('Next Check', 'straico-integration'); ?></th>
                                <td><?php echo esc_html($status['coin_check']['next_run']); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Check Frequency', 'straico-integration'); ?></th>
                                <td><?php echo esc_html($status['coin_check']['frequency']); ?></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Low Balance Threshold', 'straico-integration'); ?></th>
                                <td>
                                    <?php
                                    printf(
                                        /* translators: %s: threshold amount */
                                        esc_html__('%s coins', 'straico-integration'),
                                        number_format($status['coin_check']['threshold'], 2)
                                    );
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Notification Emails', 'straico-integration'); ?></th>
                                <td>
                                    <?php
                                    $emails = $status['coin_check']['enabled'] ? 
                                        get_option('straico_low_coin_notification_emails', array()) : array();
                                    if (!empty($emails)) {
                                        echo '<ul class="straico-email-list">';
                                        foreach ($emails as $email) {
                                            echo '<li>' . esc_html($email) . '</li>';
                                        }
                                        echo '</ul>';
                                    } else {
                                        _e('No notification emails configured.', 'straico-integration');
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <p class="description">
                    <?php
                    printf(
                        /* translators: %s: settings page URL */
                        __('You can configure these settings on the <a href="%s">Straico Settings</a> page.', 'straico-integration'),
                        esc_url(admin_url('admin.php?page=straico-settings'))
                    );
                    ?>
                </p>
            </div>

            <?php if (!empty($recent_checks)) : ?>
                <div class="straico-info-section">
                    <h2><?php _e('Recent Balance History', 'straico-integration'); ?></h2>
                    <table class="widefat straico-balance-history">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'straico-integration'); ?></th>
                                <th><?php _e('Balance', 'straico-integration'); ?></th>
                                <th><?php _e('Threshold', 'straico-integration'); ?></th>
                                <th><?php _e('Status', 'straico-integration'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_checks as $check) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html(get_date_from_gmt($check->created_at)); ?>
                                    </td>
                                    <td>
                                        <?php
                                        printf(
                                            /* translators: %s: balance amount */
                                            esc_html__('%s coins', 'straico-integration'),
                                            number_format($check->balance, 2)
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        printf(
                                            /* translators: %s: threshold amount */
                                            esc_html__('%s coins', 'straico-integration'),
                                            number_format($check->threshold, 2)
                                        );
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($check->balance < $check->threshold) : ?>
                                            <span class="straico-status-warning">
                                                <?php _e('Below Threshold', 'straico-integration'); ?>
                                            </span>
                                            <?php if ($check->notification_sent) : ?>
                                                <span class="straico-notification-sent">
                                                    <?php _e('(Notification Sent)', 'straico-integration'); ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <span class="straico-status-ok">
                                                <?php _e('Above Threshold', 'straico-integration'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="straico-info-section">
                <h2><?php _e('Force Balance Check', 'straico-integration'); ?></h2>
                <form method="post" class="straico-force-check-form">
                    <?php wp_nonce_field('straico_force_balance_check', 'straico_nonce'); ?>
                    <input type="hidden" name="action" value="straico_force_balance_check">
                    <button type="submit" class="button">
                        <?php _e('Check Balance Now', 'straico-integration'); ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.straico-user-info {
    margin-top: 1em;
}

.straico-info-section {
    margin: 2em 0;
    padding: 1em;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.straico-info-section h2 {
    margin-top: 0;
    padding-bottom: 0.5em;
    border-bottom: 1px solid #eee;
}

.straico-coin-balance {
    font-size: 1.2em;
    font-weight: 600;
}

.straico-status-enabled {
    color: #46b450;
    font-weight: 600;
}

.straico-status-disabled {
    color: #dc3232;
    font-weight: 600;
}

.straico-status-warning {
    color: #dc3232;
    font-weight: 600;
}

.straico-status-ok {
    color: #46b450;
    font-weight: 600;
}

.straico-notification-sent {
    color: #72777c;
    font-size: 0.9em;
    margin-left: 0.5em;
}

.straico-email-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.straico-email-list li {
    margin-bottom: 0.25em;
}

.straico-balance-history td {
    vertical-align: middle;
}

.straico-force-check-form {
    margin: 1em 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.straico-force-check-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button');
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('<?php _e('Checking...', 'straico-integration'); ?>');
        
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
