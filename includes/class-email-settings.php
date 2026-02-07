<?php
/**
 * Email Settings and Notifications
 *
 * Handles email verification settings and notification system
 *
 * @package MalisafiMLS
 * @since 1.0.0
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Email_Settings {

    /**
     * Initialize email settings
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('malisafi_inquiry_created', array(__CLASS__, 'send_agency_notification'), 10, 2);
        add_action('malisafi_user_registered', array(__CLASS__, 'send_verification_email'), 10, 4);
        add_action('init', array(__CLASS__, 'check_verification_link'));
        add_action('wp_ajax_malisafi_verify_email', array(__CLASS__, 'verify_email'));
        add_action('wp_ajax_nopriv_malisafi_verify_email', array(__CLASS__, 'verify_email'));
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'malisafi-dashboard',
            __('Email Settings', 'malisafi-mls'),
            __('Email Settings', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-email-settings',
            array(__CLASS__, 'settings_page')
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('malisafi_email_settings', 'malisafi_email_verification_enabled');
        register_setting('malisafi_email_settings', 'malisafi_admin_email');
        register_setting('malisafi_email_settings', 'malisafi_email_from_name');
        register_setting('malisafi_email_settings', 'malisafi_email_from_address');
        register_setting('malisafi_email_settings', 'malisafi_agency_notifications_enabled');
        register_setting('malisafi_email_settings', 'malisafi_verification_email_subject');
        register_setting('malisafi_email_settings', 'malisafi_verification_email_template');
        register_setting('malisafi_email_settings', 'malisafi_agency_notification_subject');
        register_setting('malisafi_email_settings', 'malisafi_agency_notification_template');
    }

    /**
     * Settings page content
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Malisafi MLS - Email Settings', 'malisafi-mls'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('malisafi_email_settings'); ?>
                <?php do_settings_sections('malisafi_email_settings'); ?>

                <table class="form-table">
                    <!-- General Email Settings -->
                    <tr>
                        <th colspan="2">
                            <h3><?php _e('General Email Settings', 'malisafi-mls'); ?></h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('From Name', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="text" name="malisafi_email_from_name" value="<?php echo esc_attr(get_option('malisafi_email_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                            <p class="description"><?php _e('Name that appears as the sender of emails.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('From Email Address', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="email" name="malisafi_email_from_address" value="<?php echo esc_attr(get_option('malisafi_email_from_address', get_option('admin_email'))); ?>" class="regular-text">
                            <p class="description"><?php _e('Email address that appears as the sender of emails.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Admin Email', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="email" name="malisafi_admin_email" value="<?php echo esc_attr(get_option('malisafi_admin_email', get_option('admin_email'))); ?>" class="regular-text">
                            <p class="description"><?php _e('Email address for admin notifications and user verification.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>

                    <!-- Email Verification Settings -->
                    <tr>
                        <th colspan="2">
                            <h3><?php _e('Email Verification Settings', 'malisafi-mls'); ?></h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Enable Email Verification', 'malisafi-mls'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="malisafi_email_verification_enabled" value="1" <?php checked(get_option('malisafi_email_verification_enabled'), '1'); ?>>
                                <?php _e('Require email verification for new user accounts', 'malisafi-mls'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Verification Email Subject', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="text" name="malisafi_verification_email_subject" value="<?php echo esc_attr(get_option('malisafi_verification_email_subject', __('Verify Your Email - {site_name}', 'malisafi-mls'))); ?>" class="regular-text">
                            <p class="description"><?php _e('Use {site_name} for the site name placeholder.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Verification Email Template', 'malisafi-mls'); ?></th>
                        <td>
                            <textarea name="malisafi_verification_email_template" rows="10" cols="50" class="large-text"><?php echo esc_textarea(get_option('malisafi_verification_email_template', self::get_default_verification_template())); ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {first_name}, {verification_link}, {site_name}, {site_url}', 'malisafi-mls'); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Agency Notification Settings -->
                    <tr>
                        <th colspan="2">
                            <h3><?php _e('Agency Notification Settings', 'malisafi-mls'); ?></h3>
                        </th>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Enable Agency Notifications', 'malisafi-mls'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="malisafi_agency_notifications_enabled" value="1" <?php checked(get_option('malisafi_agency_notifications_enabled', '1'), '1'); ?>>
                                <?php _e('Send email notifications to agencies when their agents receive inquiries', 'malisafi-mls'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Agency Notification Subject', 'malisafi-mls'); ?></th>
                        <td>
                            <input type="text" name="malisafi_agency_notification_subject" value="<?php echo esc_attr(get_option('malisafi_agency_notification_subject', __('New Property Inquiry for Your Agent - {site_name}', 'malisafi-mls'))); ?>" class="regular-text">
                            <p class="description"><?php _e('Use {site_name} for the site name placeholder.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Agency Notification Template', 'malisafi-mls'); ?></th>
                        <td>
                            <textarea name="malisafi_agency_notification_template" rows="10" cols="50" class="large-text"><?php echo esc_textarea(get_option('malisafi_agency_notification_template', self::get_default_agency_template())); ?></textarea>
                            <p class="description">
                                <?php _e('Available placeholders: {agency_name}, {agent_name}, {property_title}, {client_name}, {client_email}, {client_phone}, {message}, {property_url}, {site_name}', 'malisafi-mls'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get default verification email template
     */
    private static function get_default_verification_template() {
        return __('Hello {first_name},

Thank you for registering with {site_name}! To complete your account setup, please verify your email address by clicking the link below:

{verification_link}

This link will expire in 24 hours for security reasons.

If you did not create this account, please ignore this email.

Best regards,
The {site_name} Team

{site_url}', 'malisafi-mls');
    }

    /**
     * Get default agency notification template
     */
    private static function get_default_agency_template() {
        return __('Hello {agency_name} Team,

Your agent {agent_name} has received a new property inquiry:

Property: {property_title}
From: {client_name} ({client_email})
Phone: {client_phone}

Message:
{message}

Property URL: {property_url}

Please log in to your agency dashboard to view and manage this inquiry.

Best regards,
The {site_name} Team', 'malisafi-mls');
    }

    /**
     * Send verification email to new user
     */
    public static function send_verification_email($user_id, $user_role, $account_type, $user_data) {
        if (!get_option('malisafi_email_verification_enabled')) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        // Generate verification token
        $token = wp_generate_password(32, false);
        update_user_meta($user_id, '_malisafi_email_verification_token', $token);
        update_user_meta($user_id, '_malisafi_email_verified', '0');

        // Create verification link
        $verification_url = add_query_arg(array(
            'action' => 'verify_email',
            'token' => $token,
            'user_id' => $user_id
        ), home_url('/'));

        // Get email template
        $subject = get_option('malisafi_verification_email_subject', __('Verify Your Email - {site_name}', 'malisafi-mls'));
        $template = get_option('malisafi_verification_email_template', self::get_default_verification_template());

        // Replace placeholders
        $replacements = array(
            '{first_name}' => $user_data['first_name'] ?? $user->display_name,
            '{verification_link}' => $verification_url,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url('/')
        );

        $subject = str_replace('{site_name}', get_bloginfo('name'), $subject);
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Send email
        $headers = array(
            'From: ' . get_option('malisafi_email_from_name', get_bloginfo('name')) . ' <' . get_option('malisafi_email_from_address', get_option('admin_email')) . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );

        wp_mail($user->user_email, $subject, $message, $headers);
    }

    /**
     * Send agency notification when inquiry is created
     */
    public static function send_agency_notification($inquiry_id, $inquiry_data) {
        if (!get_option('malisafi_agency_notifications_enabled', '1')) {
            return;
        }

        if (empty($inquiry_data['agency_id'])) {
            return;
        }

        // Get agency info
        global $wpdb;
        $agency_table = $wpdb->prefix . 'mf_agencies';
        $agency = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $agency_table WHERE id = %d",
            $inquiry_data['agency_id']
        ));

        if (!$agency) {
            return;
        }

        // Get agent info
        $agent = get_user_by('id', $inquiry_data['agent_id']);
        if (!$agent) {
            return;
        }

        // Get property info
        $property = get_post($inquiry_data['property_id']);
        if (!$property) {
            return;
        }

        // Get email template
        $subject = get_option('malisafi_agency_notification_subject', __('New Property Inquiry for Your Agent - {site_name}', 'malisafi-mls'));
        $template = get_option('malisafi_agency_notification_template', self::get_default_agency_template());

        // Replace placeholders
        $replacements = array(
            '{agency_name}' => $agency->agency_name,
            '{agent_name}' => $agent->display_name,
            '{property_title}' => $property->post_title,
            '{client_name}' => $inquiry_data['client_name'] ?? 'Guest',
            '{client_email}' => $inquiry_data['client_email'],
            '{client_phone}' => $inquiry_data['client_phone'] ?? 'Not provided',
            '{message}' => $inquiry_data['message'],
            '{property_url}' => get_permalink($property->ID),
            '{site_name}' => get_bloginfo('name')
        );

        $subject = str_replace('{site_name}', get_bloginfo('name'), $subject);
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        // Send email
        $headers = array(
            'From: ' . get_option('malisafi_email_from_name', get_bloginfo('name')) . ' <' . get_option('malisafi_email_from_address', get_option('admin_email')) . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );

        $agency_email = $agency->agency_email ?: $agency->owner_email;
        wp_mail($agency_email, $subject, $message, $headers);
    }

    /**
     * Check for email verification link on page load
     */
    public static function check_verification_link() {
        if (isset($_GET['action']) && $_GET['action'] === 'verify_email') {
            self::verify_email();
        }
    }

    /**
     * Handle email verification
     */
    public static function verify_email() {
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

        if (empty($token) || !$user_id) {
            error_log('Malisafi: Invalid verification link - token: ' . $token . ', user_id: ' . $user_id);
            wp_die(__('Invalid verification link.', 'malisafi-mls'));
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('Malisafi: User not found for verification - user_id: ' . $user_id);
            wp_die(__('User not found.', 'malisafi-mls'));
        }

        $stored_token = get_user_meta($user_id, '_malisafi_email_verification_token', true);
        if (empty($stored_token) || $stored_token !== $token) {
            error_log('Malisafi: Invalid token - stored: ' . $stored_token . ', provided: ' . $token . ', user_id: ' . $user_id);
            wp_die(__('Invalid or expired verification token.', 'malisafi-mls'));
        }

        // Verify email
        update_user_meta($user_id, '_malisafi_email_verified', '1');
        delete_user_meta($user_id, '_malisafi_email_verification_token');
        
        error_log('Malisafi: Email verified successfully for user_id: ' . $user_id);

        // Send admin notification
        $admin_email = get_option('malisafi_admin_email', get_option('admin_email'));
        $subject = __('New User Email Verified - {site_name}', 'malisafi-mls');
        $subject = str_replace('{site_name}', get_bloginfo('name'), $subject);

        $message = sprintf(
            __('A new user has verified their email address:

Name: %s
Email: %s
Role: %s
Registration Date: %s

You can manage this user in the WordPress admin panel.',
            'malisafi-mls'),
            $user->display_name,
            $user->user_email,
            implode(', ', $user->roles),
            get_user_meta($user_id, '_malisafi_registration_date', true) ?: current_time('mysql')
        );

        $headers = array(
            'From: ' . get_option('malisafi_email_from_name', get_bloginfo('name')) . ' <' . get_option('malisafi_email_from_address', get_option('admin_email')) . '>',
            'Content-Type: text/plain; charset=UTF-8'
        );

        wp_mail($admin_email, $subject, $message, $headers);

        // Redirect to login with success message
        $login_url = Page_Manager::get_page_url('login');
        if (!$login_url) {
            $login_url = wp_login_url();
        }

        $redirect_url = add_query_arg('email_verified', '1', $login_url);
        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Check if user email is verified
     */
    public static function is_email_verified($user_id) {
        if (!get_option('malisafi_email_verification_enabled')) {
            return true; // If verification is disabled, consider all emails verified
        }

        return get_user_meta($user_id, '_malisafi_email_verified', true) === '1';
    }
}

// Initialize
Email_Settings::init();