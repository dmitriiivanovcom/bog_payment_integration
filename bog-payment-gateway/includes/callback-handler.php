<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function() {
    register_rest_route('bog-payment/v1', '/callback', [
        'methods' => 'POST',
        'callback' => 'bog_payment_callback_handler',
        'permission_callback' => '__return_true',
    ]);
});

function bog_payment_callback_handler($request) {
    // Get all request data
    $headers = $request->get_headers();
    $signature = $request->get_header('Callback-Signature');
    $request_body = $request->get_body();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $timestamp = current_time('mysql');
    
    // Log incoming request
    error_log(sprintf(
        "[BOG Payment] Incoming callback from IP: %s\n" .
        "Time: %s\n" .
        "Signature: %s\n" .
        "Headers: %s\n" .
        "Body: %s",
        $ip,
        $timestamp,
        $signature ?: 'not provided',
        json_encode($headers, JSON_PRETTY_PRINT),
        $request_body
    ));

    // Verify signature
    if (!bog_verify_callback_signature($signature, $request_body)) {
        error_log(sprintf(
            "[BOG Payment] Invalid signature from IP: %s\n" .
            "Time: %s\n" .
            "Body: %s",
            $ip,
            $timestamp,
            $request_body
        ));
        
        return new WP_Error(
            'invalid_signature',
            'Invalid callback signature',
            ['status' => 400]
        );
    }

    // Decode JSON
    $params = json_decode($request_body, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(sprintf(
            "[BOG Payment] Invalid JSON from IP: %s\n" .
            "Time: %s\n" .
            "Error: %s\n" .
            "Body: %s",
            $ip,
            $timestamp,
            json_last_error_msg(),
            $request_body
        ));
        
        return new WP_Error(
            'invalid_json',
            'Invalid JSON in callback',
            ['status' => 400]
        );
    }

    // Check data structure
    if (empty($params['event']) || $params['event'] !== 'order_payment' || empty($params['body'])) {
        error_log(sprintf(
            "[BOG Payment] Invalid data structure from IP: %s\n" .
            "Time: %s\n" .
            "Data: %s",
            $ip,
            $timestamp,
            json_encode($params, JSON_PRETTY_PRINT)
        ));
        
        return new WP_Error(
            'invalid_data',
            'Invalid callback data structure',
            ['status' => 400]
        );
    }

    // Extract data
    $order_id = sanitize_text_field($params['body']['order_id'] ?? '');
    $status = sanitize_text_field($params['body']['status'] ?? '');
    $zoned_request_time = sanitize_text_field($params['zoned_request_time'] ?? '');

    // Log successful processing
    error_log(sprintf(
        "[BOG Payment] Successfully processed callback\n" .
        "Time: %s\n" .
        "Order ID: %s\n" .
        "Status: %s\n" .
        "Event: %s\n" .
        "Full Data: %s",
        $timestamp,
        $order_id,
        $status,
        $params['event'],
        json_encode($params, JSON_PRETTY_PRINT)
    ));

    // Process data and update user meta and global log
    $user_id = bog_get_user_by_order_id($order_id);

    // Update user payment history
    if ($user_id) {
        $history = get_user_meta($user_id, 'bog_payment_history', true);
        if (!is_array($history)) $history = [];
        $history[] = [
            'order_id' => $order_id,
            'status' => $status,
            'timestamp' => time(),
            'zoned_request_time' => $zoned_request_time,
            'event' => $params['event'],
            'raw_data' => $params // Save all original data
        ];
        update_user_meta($user_id, 'bog_payment_history', $history);
        update_user_meta($user_id, 'bog_payment_status', $status);
        update_user_meta($user_id, 'bog_payment_status_changed', time());
    }

    // Update global transaction log
    $global_log = get_option('bog_global_transaction_log', []);
    if (!is_array($global_log)) $global_log = [];
    $global_log[] = [
        'order_id' => $order_id,
        'status' => $status,
        'timestamp' => time(),
        'zoned_request_time' => $zoned_request_time,
        'event' => $params['event'],
        'raw_data' => $params, // Save all original data
        'ip' => $ip
    ];
    update_option('bog_global_transaction_log', $global_log);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Callback processed successfully'
    ]);
}

// Example stub: find user_id by order_id
function bog_get_user_by_order_id($order_id) {
    // Example implementation:
    // $users = get_users(['meta_key' => 'last_bog_order_id', 'meta_value' => $order_id]);
    // return $users ? $users[0]->ID : false;
    return false;
} 
