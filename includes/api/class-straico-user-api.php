<?php
/**
 * Handles user-related API interactions with Straico.
 *
 * This class provides methods for retrieving user information including
 * coin balance and plan details.
 *
 * @since      1.0.0
 * @package    Straico_Integration
 * @subpackage Straico_Integration/includes/api
 */

class Straico_User_API extends Straico_API_Base {

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get user information including coin balance and plan details.
     *
     * @since    1.0.0
     * @return   array|WP_Error    User information or error on failure.
     */
    public function get_user_info() {
        return $this->get('v0/user');
    }

    /**
     * Check if the user's coin balance is below the specified threshold.
     *
     * @since    1.0.0
     * @param    float    $threshold    The threshold to check against.
     * @return   bool                   Whether the balance is below the threshold.
     */
    public function is_balance_below_threshold($threshold) {
        $response = $this->get_user_info();

        if (is_wp_error($response)) {
            return false;
        }

        if (!isset($response['data']['coins'])) {
            return false;
        }

        return floatval($response['data']['coins']) < floatval($threshold);
    }

    /**
     * Get the user's current coin balance.
     *
     * @since    1.0.0
     * @return   float|WP_Error    The coin balance or error on failure.
     */
    public function get_coin_balance() {
        $response = $this->get_user_info();

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['data']['coins'])) {
            return new WP_Error(
                'invalid_response',
                __('Coin balance not found in response', 'straico-integration')
            );
        }

        return floatval($response['data']['coins']);
    }

    /**
     * Get the user's current plan.
     *
     * @since    1.0.0
     * @return   string|WP_Error    The plan name or error on failure.
     */
    public function get_user_plan() {
        $response = $this->get_user_info();

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['data']['plan'])) {
            return new WP_Error(
                'invalid_response',
                __('Plan information not found in response', 'straico-integration')
            );
        }

        return $response['data']['plan'];
    }

    /**
     * Get the user's full name.
     *
     * @since    1.0.0
     * @return   string|WP_Error    The user's full name or error on failure.
     */
    public function get_user_name() {
        $response = $this->get_user_info();

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['data']['first_name']) || !isset($response['data']['last_name'])) {
            return new WP_Error(
                'invalid_response',
                __('User name not found in response', 'straico-integration')
            );
        }

        return trim($response['data']['first_name'] . ' ' . $response['data']['last_name']);
    }

    /**
     * Send a low balance notification email.
     *
     * @since    1.0.0
     * @param    float    $balance      Current balance.
     * @param    float    $threshold    Threshold that triggered the notification.
     * @param    array    $recipients   Email addresses to notify.
     * @return   bool                   Whether the email was sent successfully.
     */
    public function send_low_balance_notification($balance, $threshold, $recipients) {
        if (empty($recipients)) {
            return false;
        }

        $subject = sprintf(
            __('[Straico] Low Balance Alert - %.2f coins remaining', 'straico-integration'),
            $balance
        );

        $message = sprintf(
            /* translators: 1: current balance 2: threshold */
            __(
                'Your Straico coin balance has fallen below the notification threshold.' . "\n\n" .
                'Current Balance: %.2f coins' . "\n" .
                'Threshold: %.2f coins' . "\n\n" .
                'Please visit your Straico dashboard to add more coins to your account.',
                'straico-integration'
            ),
            $balance,
            $threshold
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($recipients, $subject, $message, $headers);
    }

    /**
     * Format user information for display.
     *
     * @since    1.0.0
     * @param    array    $user_info    Raw user information from API.
     * @return   array                  Formatted user information.
     */
    public function format_user_info($user_info) {
        if (!isset($user_info['data'])) {
            return array();
        }

        $data = $user_info['data'];
        return array(
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'coins' => number_format($data['coins'], 2),
            'plan' => $data['plan']
        );
    }
}
