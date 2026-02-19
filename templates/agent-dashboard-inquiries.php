<?php
/**
 * Agent Dashboard Inquiries Section
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Show the agent inquiries using the shortcode method
echo \MalisafiMLS\Dashboard_Shortcodes::agent_inquiries([]);
