<?php
if (!defined('ABSPATH')) exit;

function bog_payment_button($args, $return_full_response = false) {
    // Проверка обязательных параметров
    $required = ['amount', 'currency', 'description', 'name', 'order_id'];
    foreach ($required as $key) {
        if (empty($args[$key])) {
            return '<div class="bog-error">Missing required parameter: ' . esc_html($key) . '</div>';
        }
    }

    // Если отправлена форма (POST), инициируем платеж
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bog_payment_btn_nonce']) && wp_verify_nonce($_POST['bog_payment_btn_nonce'], 'bog_payment_btn')) {
        $pay_args = [
            'amount' => floatval($_POST['bog_amount']),
            'currency' => sanitize_text_field($_POST['bog_currency']),
            'description' => sanitize_text_field($_POST['bog_description']),
            'name' => sanitize_text_field($_POST['bog_name']),
            'order_id' => sanitize_text_field($_POST['bog_order_id']),
        ];
        $payment_url = bog_create_order($pay_args);
        if ($return_full_response) {
            return '<pre>' . esc_html(print_r($payment_url, true)) . '</pre>';
        }
        if ($payment_url) {
            wp_redirect($payment_url);
            exit;
        } else {
            return '<div class="bog-error">Error: Payment not created.</div>';
        }
    }

    // Генерируем HTML-кнопку
    ob_start();
    ?>
    <form method="post" style="display:inline;">
        <?php wp_nonce_field('bog_payment_btn', 'bog_payment_btn_nonce'); ?>
        <input type="hidden" name="bog_amount" value="<?php echo esc_attr($args['amount']); ?>">
        <input type="hidden" name="bog_currency" value="<?php echo esc_attr($args['currency']); ?>">
        <input type="hidden" name="bog_description" value="<?php echo esc_attr($args['description']); ?>">
        <input type="hidden" name="bog_name" value="<?php echo esc_attr($args['name']); ?>">
        <input type="hidden" name="bog_order_id" value="<?php echo esc_attr($args['order_id']); ?>">
        <button type="submit" style="background:#1a7f37;color:#fff;padding:10px 24px;border:none;border-radius:4px;font-size:16px;cursor:pointer;">
            Pay with BOG
        </button>
    </form>
    <?php
    return ob_get_clean();
} 