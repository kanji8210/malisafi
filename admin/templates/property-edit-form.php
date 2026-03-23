<?php
/**
 * Unified Admin Property Form Template
 * Redirects to the modern Wizard interface
 * 
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;

// Ensure scripts are enqueued (normally handled by the controller, but safe to call here)
\MalisafiMLS\Property_Submission::enqueue_assets();

echo '<div class="wrap malisafi-unified-wizard-admin">';
echo \MalisafiMLS\Property_Submission::render_submission_form(array());
echo '</div>';

/**
 * Simple fix for admin menu styles to accommodate the wizard
 */
?>
<style>
.malisafi-unified-wizard-admin .malisafi-property-wizard {
    margin-top: 20px;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
#adminmenuwrap {
    z-index: 10001;
}
/* Ensure the wizard doesn't conflict with admin styles */
.malisafi-property-wizard * {
    box-sizing: border-box;
}
</style>
