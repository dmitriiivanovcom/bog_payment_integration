<?php
if (!defined('ABSPATH')) exit;

// Удаляю старый шорткод и форму, теперь используется только функция bog_payment_button

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('bog-payment-style', BOG_PAYMENT_URL . 'assets/style.css');
}); 