<?php
/**
 * Modal Template: Create Manual Suspicion
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="suspicion-modal-content">
    <h3><?php _e('Create Manual Fraud Suspicion', 'malisafi-mls'); ?></h3>
    
    <form id="manual-suspicion-form">
        <!-- Fraud Type -->
        <div class="form-field">
            <label for="fraud_type">
                <?php _e('Fraud Type', 'malisafi-mls'); ?> <span class="required">*</span>
            </label>
            <select name="fraud_type" id="fraud_type" required>
                <option value=""><?php _e('Select type...', 'malisafi-mls'); ?></option>
                <option value="duplicate_listing"><?php _e('Duplicate Listing', 'malisafi-mls'); ?></option>
                <option value="rapid_edits"><?php _e('Rapid Edits', 'malisafi-mls'); ?></option>
                <option value="suspicious_ip"><?php _e('Suspicious IP', 'malisafi-mls'); ?></option>
                <option value="spam_content"><?php _e('Spam Content', 'malisafi-mls'); ?></option>
                <option value="fake_images"><?php _e('Fake Images', 'malisafi-mls'); ?></option>
                <option value="price_manipulation"><?php _e('Price Manipulation', 'malisafi-mls'); ?></option>
                <option value="multiple_accounts"><?php _e('Multiple Accounts', 'malisafi-mls'); ?></option>
                <option value="identity_fraud"><?php _e('Identity Fraud', 'malisafi-mls'); ?></option>
            </select>
        </div>

        <!-- Agent Search -->
        <div class="form-field">
            <label for="suspicion_agent_search">
                <?php _e('Agent (optional)', 'malisafi-mls'); ?>
            </label>
            <input 
                type="text" 
                id="suspicion_agent_search" 
                class="widefat"
                placeholder="<?php esc_attr_e('Start typing agent name...', 'malisafi-mls'); ?>"
            />
            <input type="hidden" name="user_id" id="suspicion_user_id" />
            <p class="description">
                <?php _e('Type at least 2 characters to search. Leave empty if not related to a specific agent.', 'malisafi-mls'); ?>
            </p>
        </div>

        <!-- Property Search -->
        <div class="form-field">
            <label for="suspicion_property_search">
                <?php _e('Property (optional)', 'malisafi-mls'); ?>
            </label>
            <input 
                type="text" 
                id="suspicion_property_search" 
                class="widefat"
                placeholder="<?php esc_attr_e('Start typing property name...', 'malisafi-mls'); ?>"
            />
            <input type="hidden" name="property_id" id="suspicion_property_id" />
            <p class="description">
                <?php _e('Type at least 2 characters to search. Leave empty if not related to a specific property.', 'malisafi-mls'); ?>
            </p>
        </div>

        <!-- Confidence Score -->
        <div class="form-field">
            <label for="confidence_score">
                <?php _e('Confidence Score', 'malisafi-mls'); ?> <span class="required">*</span>
            </label>
            <div class="confidence-slider-container">
                <input 
                    type="range" 
                    name="confidence_score" 
                    id="confidence_score"
                    min="1"
                    max="100"
                    value="70"
                    required
                />
                <div class="confidence-display">
                    <span id="confidence-value">70</span>%
                    <span id="confidence-label" class="medium-risk"><?php _e('Medium Risk', 'malisafi-mls'); ?></span>
                </div>
            </div>
            <div class="confidence-guide">
                <span class="guide-item low">1-39: <?php _e('Low', 'malisafi-mls'); ?></span>
                <span class="guide-item medium">40-74: <?php _e('Medium', 'malisafi-mls'); ?></span>
                <span class="guide-item high">75-100: <?php _e('High', 'malisafi-mls'); ?></span>
            </div>
        </div>

        <!-- Investigation Notes -->
        <div class="form-field">
            <label for="notes">
                <?php _e('Investigation Notes', 'malisafi-mls'); ?> <span class="required">*</span>
            </label>
            <textarea 
                name="notes" 
                id="notes"
                rows="5"
                class="widefat"
                placeholder="<?php esc_attr_e('Describe the evidence and investigation details...', 'malisafi-mls'); ?>"
                required
            ></textarea>
            <p class="description">
                <?php _e('Provide detailed information about why this is suspicious, including evidence and investigation steps taken.', 'malisafi-mls'); ?>
            </p>
        </div>

        <!-- Hidden fields for report context -->
        <input type="hidden" name="report_id" id="report_id" value="" />

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Create Suspicion', 'malisafi-mls'); ?>
            </button>
            
            <button type="button" class="button button-secondary button-large" onclick="jQuery('#manual-suspicion-modal').dialog('close');">
                <?php _e('Cancel', 'malisafi-mls'); ?>
            </button>
        </div>

        <div id="suspicion-response" class="notice" style="display:none;"></div>
    </form>
</div>

<style>
.suspicion-modal-content {
    padding: 20px;
}

.suspicion-modal-content h3 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    color: #d63638;
}

.form-field {
    margin-bottom: 20px;
}

.form-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-field .required {
    color: #d63638;
}

.form-field input[type="text"],
.form-field select,
.form-field textarea {
    width: 100%;
}

.confidence-slider-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.confidence-slider-container input[type="range"] {
    flex: 1;
    height: 8px;
    cursor: pointer;
}

.confidence-display {
    min-width: 150px;
    text-align: center;
    padding: 10px 15px;
    background: #f0f0f1;
    border-radius: 4px;
    font-weight: 600;
}

.confidence-display #confidence-value {
    font-size: 24px;
    color: #2271b1;
}

.confidence-display #confidence-label {
    display: block;
    font-size: 12px;
    margin-top: 5px;
    text-transform: uppercase;
}

#confidence-label.low-risk {
    color: #00a32a;
}

#confidence-label.medium-risk {
    color: #dba617;
}

#confidence-label.high-risk {
    color: #d63638;
}

.confidence-guide {
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    font-size: 12px;
}

.confidence-guide .guide-item {
    padding: 5px 10px;
    border-radius: 3px;
    background: #f0f0f1;
}

.confidence-guide .guide-item.low {
    background: #d5f5e3;
    color: #00a32a;
}

.confidence-guide .guide-item.medium {
    background: #fef5e7;
    color: #dba617;
}

.confidence-guide .guide-item.high {
    background: #fadbd8;
    color: #d63638;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #dcdcde;
}

.form-actions .button-primary {
    display: flex;
    align-items: center;
    gap: 8px;
}

#suspicion-response {
    margin-top: 15px;
}
</style>
