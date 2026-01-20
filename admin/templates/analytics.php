<?php
/**
 * Analytics Main Template  
 * Redirects to overview page
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Redirect to overview
include dirname(__FILE__) . '/analytics/overview.php';
