<?php
if (!defined('ABSPATH')) exit;

// Old shortcode and form removed, now only bog_payment_button function is used

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('bog-payment-style', BOG_PAYMENT_URL . 'assets/style.css');
}); 