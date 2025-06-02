<?php
if (!defined('ABSPATH')) exit;

function bog_create_order($args) {
    $settings = get_option('bog_payment_settings');
    $client_id = $settings['client_id'] ?? '';
    $client_secret = $settings['client_secret'] ?? '';
    $test_mode = !empty($settings['test_mode']);

    // Получаем OAuth 2.0 токен по документации
    $token_url = 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';
    $auth = base64_encode($client_id . ':' . $client_secret);
    $response = wp_remote_post($token_url, [
        'headers' => [
            'Authorization' => 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'body' => http_build_query([
            'grant_type' => 'client_credentials',
        ]),
    ]);
    if (is_wp_error($response)) return false;
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $access_token = $body['access_token'] ?? '';
    if (!$access_token) return false;
    $options = get_option('bog_payment_settings');
    $order_id = $args['order_id'] ?? uniqid('bog_', true);
    $callback_url = home_url('/wp-json/bog-payment/v1/callback');
    $description = $args['description'] ?? '';
    $amount = floatval($args['amount']);
    $currency = $args['currency'] ?? 'GEL';
    $success_url = $args['success_url'] ?? '';
    if (empty($success_url)) {
        $success_url = $options['success_url'] ?? home_url();
    }
    $fail_url = $args['fail_url'] ?? '';
    if (empty($fail_url)) {
        $fail_url = $options['fail_url'] ?? home_url();
    }
    error_log('Order data: ');
    error_log('callback_url: ' . $callback_url);
    error_log('success_url: ' . $success_url);    
    error_log('fail_url: ' . $fail_url);  
    error_log('order_id: ' . $order_id);    
    error_log('currency: ' . $currency);
    error_log('amount: ' . $amount);
    error_log('description: ' . $description);


    $url = 'https://api.bog.ge/payments/v1/ecommerce/orders';

    $data = array(
        "callback_url" => $callback_url,
        "external_order_id" => $order_id,
        "purchase_units" => array(
            "currency" => $currency,
            "total_amount" => $amount,
            "basket" => array(
                array(
                    "quantity" => 1,
                    "unit_price" => $amount,
                    'product_id' => $order_id,
                    'description' => $description,
                )
            )
        ),
        "redirect_urls" => array(
            'success' => $success_url,
            'fail' => $fail_url,
        )
    );

    

    $jsonData = json_encode($data);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept-Language: ka',
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $response_url = false;
    if (curl_errno($ch)) {
        return false;
    } else {
        $rsponse_body = json_decode($response, true);
        $response_url = $rsponse_body['_links']['redirect']['href'];
    }

    curl_close($ch);

    return $response_url;        


    // if (is_wp_error($response)) return false;
    // $order_body = json_decode(wp_remote_retrieve_body($response), true);
    // return $order_body['_links']['redirect']['href'] ?? false;
}

function bog_payment_link_button($args, $return_full_response = false) {
    // Проверка обязательных параметров
    $required = ['amount', 'currency', 'description', 'currency', 'order_id'];
    foreach ($required as $key) {
        if (empty($args[$key])) {
            return '<div class="bog-error">Missing required parameter: ' . esc_html($key) . '</div>';
        }
    }

    $settings = get_option('bog_payment_settings');
    $button_style = $settings['button_style'] ?? 'background:#1a7f37;color:#fff;padding:10px 24px;border:none;border-radius:4px;font-size:16px;cursor:pointer;text-decoration:none;display:inline-block;';
    $error_style = $settings['error_style'] ?? 'color:#b30000;padding:10px;background:#ffe0e0;border:1px solid #a00;border-radius:4px;';

    $payment_url = bog_create_order($args);

    if ($return_full_response) {
        return '<pre>' . esc_html(print_r($payment_url, true)) . '</pre>';
    }

    if ($payment_url) {
        return '<a id="payment_btn_bog" href="' . esc_url($payment_url) . '" target="_blank" style="' . esc_attr($button_style) . '">Pay with BOG</a>';
    } else {
        return '<div class="bog-error" style="' . esc_attr($error_style) . '">Error: Payment not created.</div>';
    }
} 