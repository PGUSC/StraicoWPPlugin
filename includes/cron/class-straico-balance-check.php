<?php
/**
 * Handles coin balance check cron jobs.
 *
 * This class manages the periodic checking of coin balance
 * and sends notifications when it falls below the threshold.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/cron
 */

class Straico_Balance_Check {

    /**
     * The options class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_Options    $options    Handles plugin options.
     */
    private $options;

    /**
     * The user API class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_User_API    $user_api    Handles user API interactions.
     */
    private $user_api;

    /**
     * The database manager class instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Straico_DB_Manager    $db_manager    Handles database operations.
     */
    private $db_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->options = new Straico_Options();
        $this->user_api = new Straico_User_API();
        $this->db_manager = new Straico_DB_Manager();

        // Register the cron action
        add_action('straico_coin_check_event', array($this, 'check_coin_balance'));
    }

    /**
     * Check the coin balance.
     *
     * @since    1.0.0
     */
    public function check_coin_balance() {
        // Skip if notifications are disabled
        if (!$this->options->is_low_coin_notification_enabled()) {
            return;
        }

        // Get the current balance
        $balance = $this->user_api->get_coin_balance();
        if (is_wp_error($balance)) {
            error_log(sprintf(
                '[Straico] Failed to fetch coin balance: %s',
                $balance->get_error_message()
            ));
            return;
        }

        // Get the threshold
        $threshold = $this->options->get_low_coin_threshold();

        // Check if balance is below threshold
        $is_below_threshold = $balance < $threshold;

        // Get the last notification status from transient
        $last_notification = get_transient('straico_last_balance_notification');
        $should_notify = $is_below_threshold && (!$last_notification || $last_notification['balance'] > $balance);

        // Log the balance check
        $this->db_manager->log_coin_balance($balance, $threshold, $should_notify);

        // Send notification if needed
        if ($should_notify) {
            $recipients = $this->options->get_low_coin_notification_emails();
            if (!empty($recipients)) {
                $notification_sent = $this->user_api->send_low_balance_notification(
                    $balance,
                    $threshold,
                    $recipients
                );

                if ($notification_sent) {
                    // Store notification status
                    set_transient('straico_last_balance_notification', array(
                        'balance' => $balance,
                        'timestamp' => time()
                    ), DAY_IN_SECONDS);
                }
            }
        }

        // If balance is above threshold, clear the last notification status
        if (!$is_below_threshold) {
            delete_transient('straico_last_balance_notification');
        }
    }

    /**
     * Force an immediate balance check.
     *
     * @since    1.0.0
     * @return   array|WP_Error    The check results or error on failure.
     */
    public function force_check() {
        // Get the current balance
        $balance = $this->user_api->get_coin_balance();
        if (is_wp_error($balance)) {
            return $balance;
        }

        // Get the threshold
        $threshold = $this->options->get_low_coin_threshold();

        // Check if balance is below threshold
        $is_below_threshold = $balance < $threshold;

        // Log the balance check
        $this->db_manager->log_coin_balance($balance, $threshold, false);

        return array(
            'balance' => $balance,
            'threshold' => $threshold,
            'is_below_threshold' => $is_below_threshold,
            'notifications_enabled' => $this->options->is_low_coin_notification_enabled(),
            'notification_emails' => $this->options->get_low_coin_notification_emails()
        );
    }

    /**
     * Get recent balance check history.
     *
     * @since    1.0.0
     * @param    int       $limit    Optional. Number of records to retrieve.
     * @return   array              Array of balance check records.
     */
    public function get_recent_checks($limit = 10) {
        return $this->db_manager->get_recent_coin_history($limit);
    }

    /**
     * Get the current notification status.
     *
     * @since    1.0.0
     * @return   array    The notification status information.
     */
    public function get_notification_status() {
        $last_notification = get_transient('straico_last_balance_notification');

        return array(
            'enabled' => $this->options->is_low_coin_notification_enabled(),
            'threshold' => $this->options->get_low_coin_threshold(),
            'check_frequency' => $this->options->get_coin_check_frequency(),
            'notification_emails' => $this->options->get_low_coin_notification_emails(),
            'last_notification' => $last_notification ? array(
                'balance' => $last_notification['balance'],
                'timestamp' => get_date_from_gmt(date('Y-m-d H:i:s', $last_notification['timestamp']))
            ) : null
        );
    }

    /**
     * Format balance check results for display.
     *
     * @since    1.0.0
     * @param    array    $check    The check results to format.
     * @return   string            Formatted check message.
     */
    public function format_check_message($check) {
        $message = sprintf(
            /* translators: 1: current balance 2: threshold */
            __('Current Balance: %.2f coins' . "\n" .
               'Threshold: %.2f coins' . "\n" .
               'Status: %s', 'straico-integration'),
            $check['balance'],
            $check['threshold'],
            $check['is_below_threshold'] ?
                __('Below threshold', 'straico-integration') :
                __('Above threshold', 'straico-integration')
        );

        if ($check['notifications_enabled']) {
            $message .= "\n\n" . __('Notifications are enabled for:', 'straico-integration') . "\n";
            $message .= implode("\n", $check['notification_emails']);
        } else {
            $message .= "\n\n" . __('Notifications are disabled', 'straico-integration');
        }

        return $message;
    }
}
