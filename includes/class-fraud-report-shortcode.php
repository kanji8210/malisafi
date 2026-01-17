<?php
/**
 * Fraud Report Shortcode
 *
 * Displays fraud reporting form on frontend
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Fraud_Report_Shortcode {
    
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('malisafi_fraud_report', array($this, 'render_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        global $post;
        
        // Only enqueue when shortcode is present
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'malisafi_fraud_report')) {
            
            // jQuery UI Autocomplete
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css');
            
            // Custom styles
            wp_enqueue_style(
                'malisafi-fraud-report',
                MALISAFI_MLS_PLUGIN_URL . 'assets/css/fraud-report.css',
                array(),
                MALISAFI_MLS_VERSION
            );
            
            // Custom script
            wp_enqueue_script(
                'malisafi-fraud-report',
                MALISAFI_MLS_PLUGIN_URL . 'assets/js/fraud-report.js',
                array('jquery', 'jquery-ui-autocomplete'),
                MALISAFI_MLS_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('malisafi-fraud-report', 'malisafiFraudReport', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_fraud_report_nonce'),
                'isLoggedIn' => is_user_logged_in(),
                'i18n' => array(
                    'searching' => __('Searching...', 'malisafi-mls'),
                    'noResults' => __('No results found', 'malisafi-mls'),
                    'selectAgent' => __('Select an agent', 'malisafi-mls'),
                    'selectProperty' => __('Select a property', 'malisafi-mls'),
                    'submitting' => __('Submitting...', 'malisafi-mls'),
                    'success' => __('Report submitted successfully!', 'malisafi-mls'),
                    'error' => __('Failed to submit report. Please try again.', 'malisafi-mls')
                )
            ));
        }
    }

    /**
     * Render fraud report form
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Report Fraud', 'malisafi-mls'),
            'show_title' => 'yes'
        ), $atts);

        ob_start();
        ?>
        <div class="malisafi-fraud-report-container">
            <?php if ($atts['show_title'] === 'yes') : ?>
                <h2 class="fraud-report-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>

            <div class="fraud-report-description">
                <p><?php _e('Help us maintain a safe and trustworthy platform. If you suspect fraudulent activity, please provide details below.', 'malisafi-mls'); ?></p>
                <p class="privacy-note">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                    </svg>
                    <?php _e('Your information will be kept confidential.', 'malisafi-mls'); ?>
                </p>
            </div>

            <form id="malisafi-fraud-report-form" class="fraud-report-form">
                
                <!-- Report Type -->
                <div class="form-group">
                    <label for="report_type">
                        <?php _e('Type of Fraud', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <select name="report_type" id="report_type" required>
                        <option value=""><?php _e('Select reason...', 'malisafi-mls'); ?></option>
                        <option value="fake_listing"><?php _e('Fake Listing', 'malisafi-mls'); ?></option>
                        <option value="duplicate_property"><?php _e('Duplicate Property', 'malisafi-mls'); ?></option>
                        <option value="misleading_info"><?php _e('Misleading Information', 'malisafi-mls'); ?></option>
                        <option value="fake_agent"><?php _e('Fake Agent', 'malisafi-mls'); ?></option>
                        <option value="price_scam"><?php _e('Price Scam', 'malisafi-mls'); ?></option>
                        <option value="fake_photos"><?php _e('Fake or Misleading Photos', 'malisafi-mls'); ?></option>
                        <option value="contact_fraud"><?php _e('Contact Information Fraud', 'malisafi-mls'); ?></option>
                        <option value="identity_theft"><?php _e('Identity Theft', 'malisafi-mls'); ?></option>
                        <option value="spam"><?php _e('Spam', 'malisafi-mls'); ?></option>
                        <option value="other"><?php _e('Other', 'malisafi-mls'); ?></option>
                    </select>
                </div>

                <!-- Agent Autocomplete -->
                <div class="form-group">
                    <label for="agent_search">
                        <?php _e('Agent Involved (if applicable)', 'malisafi-mls'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="agent_search" 
                        class="autocomplete-input"
                        placeholder="<?php esc_attr_e('Start typing agent name...', 'malisafi-mls'); ?>"
                    />
                    <input type="hidden" name="agent_id" id="agent_id" />
                    <small class="help-text">
                        <?php _e('Type at least 2 characters to search', 'malisafi-mls'); ?>
                    </small>
                </div>

                <!-- Property Autocomplete -->
                <div class="form-group">
                    <label for="property_search">
                        <?php _e('Property Involved (if applicable)', 'malisafi-mls'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="property_search" 
                        class="autocomplete-input"
                        placeholder="<?php esc_attr_e('Start typing property name or address...', 'malisafi-mls'); ?>"
                    />
                    <input type="hidden" name="property_id" id="property_id" />
                    <small class="help-text">
                        <?php _e('Type at least 2 characters to search', 'malisafi-mls'); ?>
                    </small>
                </div>

                <div class="form-note">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                    </svg>
                    <?php _e('Please select at least one: Agent or Property', 'malisafi-mls'); ?>
                </div>

                <!-- Email (required if not logged in) -->
                <?php if (!is_user_logged_in()) : ?>
                <div class="form-group">
                    <label for="reporter_email">
                        <?php _e('Your Email', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="reporter_email" 
                        id="reporter_email"
                        placeholder="<?php esc_attr_e('your@email.com', 'malisafi-mls'); ?>"
                        required 
                    />
                    <small class="help-text">
                        <?php _e('We will use this to contact you if needed', 'malisafi-mls'); ?>
                    </small>
                </div>
                <?php else : ?>
                <input type="hidden" name="reporter_email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" />
                <?php endif; ?>

                <!-- Reason -->
                <div class="form-group">
                    <label for="reason">
                        <?php _e('Brief Description', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="reason" 
                        id="reason"
                        maxlength="500"
                        placeholder="<?php esc_attr_e('Summarize the issue in one sentence...', 'malisafi-mls'); ?>"
                        required 
                    />
                    <small class="help-text char-count">
                        <span id="char-count">0</span>/500 <?php _e('characters', 'malisafi-mls'); ?>
                    </small>
                </div>

                <!-- Details -->
                <div class="form-group">
                    <label for="details">
                        <?php _e('Detailed Information', 'malisafi-mls'); ?> <span class="required">*</span>
                    </label>
                    <textarea 
                        name="details" 
                        id="details"
                        rows="6"
                        placeholder="<?php esc_attr_e('Provide as much detail as possible. Include dates, screenshots description, communications, etc.', 'malisafi-mls'); ?>"
                        required
                    ></textarea>
                    <small class="help-text">
                        <?php _e('The more information you provide, the better we can investigate', 'malisafi-mls'); ?>
                    </small>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm6.787-8.201L1.591 6.602l4.339 2.76 7.494-7.493Z"/>
                        </svg>
                        <span class="btn-text"><?php _e('Submit Report', 'malisafi-mls'); ?></span>
                    </button>
                </div>

                <!-- Response Message -->
                <div id="form-response" class="form-response" style="display:none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize
Malisafi_Fraud_Report_Shortcode::get_instance();
