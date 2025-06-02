<?php
if (!defined('ABSPATH')) exit;

// Добавляем страницу настроек
add_action('admin_menu', function() {
    add_options_page(
        'BOG Payments',
        'BOG Payments',
        'manage_options',
        'bog-payment-settings',
        'bog_payment_settings_page'
    );
});

// Добавляем тестовую страницу для callback
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php?page=bog-payment-settings',
        'Test Callback',
        'Test Callback',
        'manage_options',
        'bog-payment-test-callback',
        'bog_payment_test_callback_page'
    );
});

// Регистрируем настройки
add_action('admin_init', function() {
    register_setting('bog_payment_settings_group', 'bog_payment_settings', [
        'sanitize_callback' => 'bog_payment_sanitize_settings',
    ]);
});

function bog_payment_settings_page() {
    $options = get_option('bog_payment_settings');
    $callback_url = home_url('/wp-json/bog-payment/v1/callback');
    $button_style = $options['button_style'] ?? 'background:#1a7f37;color:#fff;padding:10px 24px;border:none;border-radius:4px;font-size:16px;cursor:pointer;text-decoration:none;display:inline-block;';
    $error_style = $options['error_style'] ?? 'color:#b30000;padding:10px;background:#ffe0e0;border:1px solid #a00;border-radius:4px;';
    $success_url = $options['success_url'] ?? '';
    $fail_url = $options['fail_url'] ?? '';
    ?>
    <div class="wrap">
        <h1>BOG Payment Gateway Settings</h1>
        
        <!-- Payment Button Usage Example -->
        <div class="card" style="max-width: fit-content; margin: 20px 0; padding: 20px;">
            <h2>Payment Button Usage Example</h2>
            <p>To add a payment button to your page, use the following code:</p>
            <pre style="background: #f0f0f1; padding: 15px; border-radius: 4px;">
                &lt;?php
                // Payment button parameters example
                $payment_args = [
                    'amount' => 100.00,           // Payment amount
                    'currency' => 'GEL',          // Currency (GEL, USD, EUR)
                    'description' => 'Payment for order #123', // Payment description
                    'order_id' => 'order_123',    // Unique order ID
                    'success_url' => home_url('/?bog-payment-success=true'),
                    'fail_url' => home_url('/?bog-payment-fail=true'),
                ];

                // Display payment button
                echo bog_payment_link_button($payment_args);
                ?&gt;
            </pre>

            <p>Or use the shortcode:</p>
            <pre style="background: #f0f0f1; padding: 15px; border-radius: 4px;">
[bog_payment_button amount="100.00" currency="GEL" description="Payment for order #123" order_id="order_123" success_url="yourdomain.com/?bog-payment-success=true" fail_url="yourdomain.com/?bog-payment-fail=true"]</pre>

            <h3>Test Button</h3>
            <p>Try creating a test payment:</p>
            <?php
            // Create test button with unique order ID
            $test_args = [
                'amount' => 100.00,
                'currency' => 'GEL',
                'description' => 'Payment for order #123',
                'order_id' => 'order_123',
                'success_url' => home_url('/?bog-payment-success=true'),
                'fail_url' => home_url('/?bog-payment-fail=true'),
            ];
            echo bog_payment_link_button($test_args);
            ?>
        </div>

        <!-- Settings Form -->
        <form method="post" action="options.php" id="bog-payment-settings-form">
            <?php settings_fields('bog_payment_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Client ID</th>
                    <td>
                        <input type="text" name="bog_payment_settings[client_id]" value="<?php echo esc_attr($options['client_id'] ?? ''); ?>" class="regular-text" id="bog_client_id" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Client Secret</th>
                    <td>
                        <div style="position: relative; display: inline-block;">
                            <input type="password" name="bog_payment_settings[client_secret]" value="<?php echo esc_attr($options['client_secret'] ?? ''); ?>" class="regular-text" id="bog_client_secret" />
                            <button type="button" class="button" id="bog_toggle_secret" style="position: absolute; right: -40px; top: 0; padding: 0 8px; height: 30px;">
                                <span class="dashicons dashicons-visibility" style="line-height: 30px;"></span>
                            </button>
                        </div>
                        <button style="margin-left:40px;" type="button" class="button" id="bog-check-credentials">Check Connection</button>
                        <span id="bog-check-result" style="margin-left:10px;"></span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Button Style</th>
                    <td>
                        <textarea name="bog_payment_settings[button_style]" rows="2" class="large-text"><?php echo esc_textarea($button_style); ?></textarea>
                        <p class="description">CSS styles for the payment button (Pay with BOG).</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Error Style</th>
                    <td>
                        <textarea name="bog_payment_settings[error_style]" rows="2" class="large-text"><?php echo esc_textarea($error_style); ?></textarea>
                        <p class="description">CSS styles for warnings/error messages.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Callback URL</th>
                    <td>
                        <input type="text" value="<?php echo esc_attr($callback_url); ?>" class="regular-text" readonly onclick="this.select();" />
                        <p class="description">Copy this URL and specify it in your BOG settings.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Test Mode</th>
                    <td><input type="checkbox" name="bog_payment_settings[test_mode]" value="1" <?php checked(!empty($options['test_mode'])); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Success URL</th>
                    <td>
                        <input type="url" name="bog_payment_settings[success_url]" value="<?php echo esc_attr($success_url); ?>" class="regular-text" />
                        <p class="description">URL for redirect after successful payment. If not specified, the payment form page URL will be used.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Fail URL</th>
                    <td>
                        <input type="url" name="bog_payment_settings[fail_url]" value="<?php echo esc_attr($fail_url); ?>" class="regular-text" />
                        <p class="description">URL for redirect after failed payment. If not specified, the payment form page URL will be used.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <script>
        jQuery(document).ready(function($){
            // Функция для переключения видимости пароля
            $('#bog_toggle_secret').on('click', function() {
                var $secret = $('#bog_client_secret');
                var $icon = $(this).find('.dashicons');
                
                if ($secret.attr('type') === 'password') {
                    $secret.attr('type', 'text');
                    $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                } else {
                    $secret.attr('type', 'password');
                    $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                }
            });

            $('#bog-check-credentials').on('click', function(){
                var client_id = $('#bog_client_id').val();
                var client_secret = $('#bog_client_secret').val();
                var test_mode = $('input[name="bog_payment_settings[test_mode]"]').is(':checked') ? 1 : 0;
                var $result = $('#bog-check-result');
                $result.text('Checking...');
                $.post(ajaxurl, {
                    action: 'bog_check_credentials',
                    client_id: client_id,
                    client_secret: client_secret,
                    test_mode: test_mode,
                    _ajax_nonce: '<?php echo wp_create_nonce('bog_check_credentials'); ?>'
                }, function(response){
                    if(response.success) {
                        var resp = response.data && response.data.response ? response.data.response : response.data;
                        $result.css('color', 'green').html('Success!<br><pre>' + (resp ? JSON.stringify(resp, null, 2) : 'No data') + '</pre>');
                    } else {
                        var resp = response.data && response.data.response ? response.data.response : response.data;
                        $result.css('color', 'red').html('Error: ' + (response.data && response.data.message ? response.data.message : 'Authentication failed') + '<br><pre>' + (resp ? JSON.stringify(resp, null, 2) : 'No data') + '</pre>');
                    }
                });
            });
        });
        </script>
        <style>
        .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
            line-height: 1;
        }
        #bog_toggle_secret {
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            color: #50575e;
        }
        #bog_toggle_secret:hover {
            background: #e5e5e5;
            border-color: #999;
            color: #000;
        }
        </style>
    </div>
    <?php
}

function bog_payment_sanitize_settings($input) {
    return [
        'client_id' => sanitize_text_field($input['client_id'] ?? ''),
        'client_secret' => sanitize_text_field($input['client_secret'] ?? ''),
        'button_style' => sanitize_textarea_field($input['button_style'] ?? ''),
        'error_style' => sanitize_textarea_field($input['error_style'] ?? ''),
        'test_mode' => !empty($input['test_mode']) ? 1 : 0,
        'success_url' => esc_url_raw($input['success_url'] ?? ''),
        'fail_url' => esc_url_raw($input['fail_url'] ?? ''),
    ];
}

add_action('wp_ajax_bog_check_credentials', function() {
    check_ajax_referer('bog_check_credentials');
    $client_id = sanitize_text_field($_POST['client_id'] ?? '');
    $client_secret = sanitize_text_field($_POST['client_secret'] ?? '');
    $test_mode = !empty($_POST['test_mode']);
    $token_url = $test_mode
        ? 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token'
        : 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';
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
    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => 'Ошибка соединения с API',
            'response' => $response->get_error_message(),
        ]);
    }
    $raw_body = wp_remote_retrieve_body($response);
    $body = json_decode($raw_body, true);
    if (!empty($body['access_token'])) {
        wp_send_json_success(['message' => 'Успешно!', 'response' => $body]);
    } else {
        $error = $body['error_description'] ?? ($body['error'] ?? 'Неверные данные');
        wp_send_json_error([
            'message' => $error,
            'response' => $body ?: $raw_body,
        ]);
    }
});

function bog_payment_test_callback_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $test_result = '';
    if (isset($_POST['test_callback'])) {
        check_admin_referer('bog_test_callback');
        
        // Создаем тестовые данные
        $test_data = [
            'event' => 'order_payment',
            'zoned_request_time' => current_time('mysql'),
            'body' => [
                'order_id' => 'test_' . time(),
                'status' => $_POST['test_status'] ?? 'SUCCESS',
                'industry' => 'ecommerce',
                'amount' => 100.00,
                'currency' => 'GEL'
            ]
        ];

        // Преобразуем в JSON
        $json_data = json_encode($test_data);
        
        // Создаем тестовую подпись (в реальном случае это будет подпись от BOG)
        $signature = base64_encode(hash_hmac('sha256', $json_data, 'test_secret', true));

        // Отправляем запрос на наш же callback endpoint
        $response = wp_remote_post(home_url('/wp-json/bog-payment/v1/callback'), [
            'headers' => [
                'Content-Type' => 'application/json',
                'Callback-Signature' => $signature
            ],
            'body' => $json_data,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            $test_result = 'Error: ' . $response->get_error_message();
        } else {
            $body = wp_remote_retrieve_body($response);
            $test_result = 'Response: ' . $body;
        }
    }
    ?>
    <div class="wrap">
        <h1>Test BOG Payment Callback</h1>
        
        <?php if ($test_result): ?>
            <div class="notice notice-info">
                <p><strong>Test Result:</strong></p>
                <pre><?php echo esc_html($test_result); ?></pre>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('bog_test_callback'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Test Status</th>
                    <td>
                        <select name="test_status">
                            <option value="SUCCESS">Success</option>
                            <option value="FAILED">Failed</option>
                            <option value="CANCELLED">Cancelled</option>
                        </select>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="test_callback" class="button button-primary" value="Send Test Callback">
            </p>
        </form>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>How to Test</h2>
            <p>This page allows you to send test callbacks to your endpoint. The test will:</p>
            <ol>
                <li>Generate a test order with random ID</li>
                <li>Create a test signature</li>
                <li>Send the request to your callback endpoint</li>
                <li>Show the response</li>
            </ol>
            <p>You can check the results in:</p>
            <ul>
                <li>WordPress debug log (wp-content/debug.log)</li>
                <li>User meta data (if user is found by order_id)</li>
                <li>Global transaction log in WordPress options</li>
            </ul>
        </div>
    </div>
    <?php
} 