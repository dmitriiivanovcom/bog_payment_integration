<?php
/*
Plugin Name: BOG Payment Gateway
Description: Fast, simple, and free WordPress plugin for BOG payment system integration.
Version: 1.0.0
Author: Dmitrii Ivanov
Author URI: https://github.com/dmitriiivanovcom
License: GPLv2 or later
Text Domain: bog-payment-gateway
*/

if (!defined('ABSPATH')) exit;

define('BOG_PAYMENT_PATH', plugin_dir_path(__FILE__));
define('BOG_PAYMENT_URL', plugin_dir_url(__FILE__));

// Include required files
require_once BOG_PAYMENT_PATH . 'includes/admin-settings.php';
require_once BOG_PAYMENT_PATH . 'includes/payment-handler.php';
require_once BOG_PAYMENT_PATH . 'includes/shortcode-form.php';
require_once BOG_PAYMENT_PATH . 'includes/callback-handler.php';
require_once BOG_PAYMENT_PATH . 'includes/payment-button.php';

// Register shortcode
add_shortcode('bog_payment_form', 'bog_payment_form_shortcode');

// Add shortcode for payment button
add_shortcode('bog_payment_button', function($atts) {
    $atts = shortcode_atts([
        'amount' => '',
        'currency' => 'GEL',
        'description' => '',
        'order_id' => '',
        'success_url' => '',
        'fail_url' => '',
    ], $atts, 'bog_payment_button');

    // Check required parameters
    if (empty($atts['amount']) || empty($atts['description']) || empty($atts['order_id'])) {
        return '<div class="bog-error">Error: required parameters missing (amount, description, order_id)</div>';
    }

    // Convert amount to float
    $atts['amount'] = floatval($atts['amount']);

    return bog_payment_link_button($atts);
}); 