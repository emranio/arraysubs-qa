<?php
/**
 * Issue #87: real WordPress REST checks using disposable manual-payment fixtures.
 * Run with WP-CLI eval-file; append route-only for the non-mutating baseline.
 * No saved settings, plugin activations, existing subscriptions or payments change.
 */
defined('ABSPATH') || exit;

$checks = [];
$fixtures = ['users' => [], 'product' => 0, 'subscriptions' => []];
$original_user = get_current_user_id();
$original_settings = get_option('arraysubs_settings', []);
$original_plugins = get_option('active_plugins', []);
$effective_settings = is_array($original_settings) ? $original_settings : [];
$failure = null;
$settings_filter = static function () use (&$effective_settings) { return $effective_settings; };
$freeze_settings = static function ($value, $old_value) { return $old_value; };
$suppress_mail = static function () { return true; };
$check = static function (bool $passed, string $label) use (&$checks): void {
    $checks[] = ['check' => $label, 'passed' => $passed];
    if (!$passed) {
        throw new RuntimeException($label);
    }
};
$request = static function (string $path, array $params = []): WP_REST_Response {
    $request = new WP_REST_Request('POST', '/arraysubs/v1/' . $path);
    $request->set_body_params($params);
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    return rest_do_request($request);
};
$policy = static function (bool $allow, bool $require_reason, bool $immediate) use (&$effective_settings, $original_settings): void {
    $effective_settings = is_array($original_settings) ? $original_settings : [];
    $effective_settings['customer_actions']['allow_cancellation'] = $allow;
    $effective_settings['cancellation']['require_reason'] = $require_reason;
    $effective_settings['cancellation']['cancel_immediately'] = $immediate;
};
$snapshot = static function (int $id): array {
    return ['status' => get_post_status($id), 'meta' => get_post_meta($id)];
};

try {
    $routes = rest_get_server()->get_routes();
    $check(!isset($routes['/arraysubs/v1/subscriptions/(?P<id>\d+)/cancel-with-reason']), 'Unused Pro cancellation route is not registered');
    $check(isset($routes['/arraysubs/v1/my-subscriptions/(?P<id>\d+)/cancel']), 'Canonical core customer cancellation route is registered');

    if (!in_array('route-only', $args ?? [], true)) {
        add_filter('pre_option_arraysubs_settings', $settings_filter);
        // Defensive: read-time legacy migrations cannot persist request-local policy.
        add_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX, 2);
        add_filter('pre_wp_mail', $suppress_mail);

        $admins = get_users(['role' => 'administrator', 'fields' => 'ID', 'number' => 1]);
        $check(!empty($admins), 'Administrator exists for invalid admin configuration check');
        $admin_id = (int) $admins[0];
        wp_set_current_user($admin_id);

        foreach (['owner', 'other'] as $role) {
            $token = strtolower(wp_generate_password(12, false, false));
            $user_id = wp_insert_user([
                'user_login' => 'cancel-qa-' . $role . '-' . $token,
                'user_email' => 'cancel-qa-' . $role . '-' . $token . '@example.invalid',
                'user_pass' => wp_generate_password(32),
                'role' => 'customer',
            ]);
            $check(!is_wp_error($user_id), 'Disposable ' . $role . ' customer created');
            $fixtures['users'][$role] = (int) $user_id;
        }

        $product = new WC_Product_Simple();
        $product->set_name('Issue 87 disposable manual-payment fixture');
        $product->set_status('draft');
        $product->set_regular_price('15');
        $product->set_virtual(true);
        $fixtures['product'] = $product->save();
        $check($fixtures['product'] > 0, 'Disposable draft product created');

        $create_subscription = static function () use (&$fixtures, $check): int {
            $id = arraysubs_create_subscription([
                'customer_id' => $fixtures['users']['owner'],
                'product_id' => $fixtures['product'],
                'status' => 'arraysubs-active',
                'subscription_price' => 15,
                'recurring_amount' => 15,
                'payment_method' => 'bacs',
                'payment_method_title' => 'QA manual payment; never charged',
                'next_payment_date' => gmdate('Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS),
            ]);
            $check(!is_wp_error($id) && $id > 0, 'Disposable active manual-payment subscription created');
            $fixtures['subscriptions'][] = (int) $id;
            return (int) $id;
        };

        $subscription_id = $create_subscription();
        $before = $snapshot($subscription_id);
        wp_set_current_user($fixtures['users']['owner']);
        $response = $request('subscriptions/' . $subscription_id . '/cancel-with-reason', ['reason' => 'other', 'cancel_type' => 'immediate']);
        $check($response->get_status() === 404 && $snapshot($subscription_id) === $before, 'Retired Pro route returns 404 without changing the subscription');

        $policy(true, true, false);
        foreach (['', '   '] as $reason) {
            $response = $request('my-subscriptions/' . $subscription_id . '/cancel', ['reason' => $reason]);
            $check($response->get_status() === 400 && ($response->get_data()['code'] ?? '') === 'reason_required', 'Missing or whitespace required reason returns a controlled 400');
            $check($snapshot($subscription_id) === $before, 'Rejected required reason leaves lifecycle and metadata unchanged');
        }

        $policy(false, false, true);
        $response = $request('my-subscriptions/' . $subscription_id . '/cancel', ['reason' => 'other']);
        $check($response->get_status() === 403 && $snapshot($subscription_id) === $before, 'Disabled customer cancellation cannot be bypassed');

        $policy(true, false, true);
        wp_set_current_user($fixtures['users']['other']);
        $check($request('my-subscriptions/' . $subscription_id . '/cancel', ['reason' => 'other'])->get_status() === 403, 'A different customer cannot cancel the fixture');
        wp_set_current_user(0);
        $check(in_array($request('my-subscriptions/' . $subscription_id . '/cancel')->get_status(), [401, 403], true), 'Anonymous cancellation is rejected');
        $check($snapshot($subscription_id) === $before, 'Denied ownership requests leave the fixture unchanged');

        wp_set_current_user($fixtures['users']['owner']);
        $policy(true, true, false);
        $response = $request('my-subscriptions/' . $subscription_id . '/cancel', ['reason' => 'other', 'cancel_type' => 'immediate']);
        $check($response->get_status() === 200 && get_post_status($subscription_id) === 'arraysubs-active' && arraysubs_is_waiting_cancellation($subscription_id), 'Valid required reason schedules cancellation according to merchant timing');
        $check(get_post_meta($subscription_id, '_cancellation_scheduled_date', true) === get_post_meta($subscription_id, '_next_payment_date', true), 'Client immediate override cannot shorten the paid period');
        $check(get_post_meta($subscription_id, '_cancellation_reason', true) === 'other', 'Canonical cancellation records the supplied reason');

        $optional_id = $create_subscription();
        $policy(true, false, true);
        $response = $request('my-subscriptions/' . $optional_id . '/cancel');
        $check($response->get_status() === 200 && get_post_status($optional_id) === 'arraysubs-cancelled', 'Optional blank reason still permits canonical immediate cancellation');

        if (isset($routes['/arraysubs/v1/cancellation/form-config'])) {
            wp_set_current_user($admin_id);
            $invalid_config = new WP_REST_Request('POST', '/arraysubs/v1/cancellation/form-config');
            $invalid_config->set_header('Content-Type', 'text/plain');
            $invalid_config->set_body('invalid config');
            $response = rest_do_request($invalid_config);
            $check($response->get_status() === 400 && ($response->get_data()['code'] ?? '') === 'invalid_config', 'Invalid admin cancellation form config returns WP_Error instead of TypeError');
        }
    }
} catch (Throwable $error) {
    $failure = get_class($error) . ': ' . $error->getMessage();
} finally {
    remove_filter('pre_option_arraysubs_settings', $settings_filter);
    remove_filter('pre_update_option_arraysubs_settings', $freeze_settings, PHP_INT_MAX);
    foreach ($fixtures['subscriptions'] as $id) {
        \ArraySubs\Supports\ActionScheduler::unscheduleAllForSubscription($id);
        wp_delete_post($id, true);
    }
    if ($fixtures['product']) {
        wp_delete_post($fixtures['product'], true);
    }
    if ($fixtures['users']) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($fixtures['users'] as $id) {
            wp_delete_user($id);
        }
    }
    remove_filter('pre_wp_mail', $suppress_mail);
    wp_set_current_user($original_user);
}

$settings_unchanged = get_option('arraysubs_settings', []) === $original_settings;
$plugins_unchanged = get_option('active_plugins', []) === $original_plugins;
echo wp_json_encode([
    'checks' => $checks,
    'failure' => $failure,
    'deleted_fixtures' => $fixtures,
    'saved_settings_unchanged' => $settings_unchanged,
    'active_plugins_unchanged' => $plugins_unchanged,
], JSON_PRETTY_PRINT) . PHP_EOL;
if ($failure !== null || !$settings_unchanged || !$plugins_unchanged) {
    WP_CLI::halt(1);
}
